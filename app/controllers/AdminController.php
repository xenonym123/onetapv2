<?php
// The admin side, which also covers what an operator needs: fleet, fares per unit, capacity.

class AdminController extends Controller
{
    private array $user;

    private const RANGES = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    public function __construct()
    {
        $this->user = $this->requireRole('admin');
    }

    public function home(): void
    {
        $today = date('Y-m-d 00:00:00');
        $fleet = Jeepney::fleet();
        $this->dashboard('admin/home', [
            'title'    => 'Analytics',
            'fleet'    => $fleet,
            'onRoad'   => count(array_filter($fleet, fn($u) => $u['state'] === 'on_trip')),
            'riding'   => array_sum(array_map(fn($u) => (int) $u['onboard'], $fleet)),
            'today'    => Fare::totalsSince($today),
            'yday'     => Fare::totalsSince(date('Y-m-d 00:00:00', strtotime('-1 day'))),
            'reloads'  => Reload::totalSince($today),
            'reloadsN' => Reload::countSince($today),
            'users'    => User::countByRole(),
            'activity' => ActivityLog::latest('', 6),
        ]);
    }

    // ---- users -------------------------------------------------------------
    public function users(): void
    {
        $role = in_array($this->input('role'), ['passenger', 'driver', 'admin'], true) ? $this->input('role') : '';
        $this->dashboard('admin/users', [
            'title'  => 'User management',
            'users'  => User::listing($role, $this->input('q')),
            'role'   => $role,
            'q'      => $this->input('q'),
            'counts' => User::countByRole(),
        ]);
    }

    public function exportUsers(): void
    {
        $rows = array_map(fn($u) => [$u['user_id'], $u['full_name'], $u['username'], $u['role_name'], $u['email'], $u['phone'],
            $u['balance'] ?? '', $u['status'], $u['created_at']], User::listing());
        $this->csv('1tap-users-' . date('Y-m-d') . '.csv',
            ['ID', 'Name', 'Username', 'Role', 'Email', 'Phone', 'Wallet balance', 'Status', 'Created'], $rows);
    }

    public function newUser(): void
    {
        $this->dashboard('admin/user-form', ['title' => 'Add user', 'account' => null, 'errors' => $_SESSION['errors'] ?? []]);
        unset($_SESSION['errors']);
    }

    public function createUser(): void
    {
        $this->verifyCsrf();
        $d = $this->userInput();
        $errors = AuthController::validate($d, null);
        if ($errors) {
            $_SESSION['errors'] = $errors;
            remember_input(['full_name', 'email', 'phone', 'username', 'role']);
            $this->redirect('/admin/users/new');
        }
        $id = Model::transaction(function () use ($d) {
            $id = User::create($d);
            if ($d['role'] === 'passenger') {
                Wallet::create($id);
            }
            return $id;
        });
        forget_input();
        ActivityLog::add($this->user['id'], 'user_added', "Added {$d['role']} account {$d['username']}");
        flash('success', "{$d['full_name']} can now sign in as {$d['username']}.");
        $this->redirect('/admin/users');
    }

    public function editUser(string $id): void
    {
        $account = User::find((int) $id);
        if (!$account) {
            $this->redirect('/admin/users');
        }
        $this->dashboard('admin/user-form', [
            'title'   => 'Edit ' . $account['full_name'],
            'account' => $account,
            'wallet'  => Wallet::forUser((int) $id),
            'errors'  => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
    }

    public function updateUser(string $id): void
    {
        $this->verifyCsrf();
        $id = (int) $id;
        $account = User::find($id);
        if (!$account) {
            $this->redirect('/admin/users');
        }
        $d = $this->userInput();
        if ($id === $this->user['id']) {
            $d['role'] = 'admin'; // you can't demote yourself
        }
        $errors = AuthController::validate($d, null, $id, false);
        if ($errors) {
            $_SESSION['errors'] = $errors;
            remember_input(['full_name', 'email', 'phone', 'username', 'role']);
            $this->redirect("/admin/users/$id");
        }
        User::updateDetails($id, $d);
        User::setRole($id, $d['role']);
        if ($d['role'] === 'passenger' && !Wallet::forUser($id)) {
            Wallet::create($id);
        }
        if ($d['password'] !== '') {
            User::setPassword($id, $d['password']);
        }
        forget_input();
        ActivityLog::add($this->user['id'], 'user_updated', "Updated account {$d['username']}" . ($d['password'] !== '' ? ' and reset the password' : ''));
        flash('success', "Saved {$d['full_name']}.");
        $this->redirect('/admin/users');
    }

    /** Deactivate instead of delete, so their rides and fares stay on record. */
    public function toggleUser(string $id): void
    {
        $this->verifyCsrf();
        $id = (int) $id;
        $account = User::find($id);
        if ($account && $id !== $this->user['id']) {
            $new = $account['status'] === 'active' ? 'inactive' : 'active';
            User::setStatus($id, $new);
            ActivityLog::add($this->user['id'], 'user_status', ($new === 'active' ? 'Reactivated ' : 'Deactivated ') . $account['username']);
            flash('success', $account['full_name'] . ($new === 'active' ? ' can sign in again.' : ' is deactivated.'));
        }
        $this->back('/admin/users');
    }

    private function userInput(): array
    {
        $role = $this->input('role');
        return [
            'full_name' => $this->input('full_name'),
            'email'     => $this->input('email'),
            'phone'     => preg_replace('/[\s-]/', '', $this->input('phone')),
            'username'  => strtolower($this->input('username')),
            'password'  => $_POST['password'] ?? '',
            'role'      => in_array($role, ['passenger', 'driver', 'admin'], true) ? $role : 'passenger',
        ];
    }

    // ---- fleet ---------------------------------------------------------------
    public function tracking(): void
    {
        $fleet = Jeepney::fleet();
        $blocked = Fare::totalsSince(date('Y-m-d 00:00:00'));
        $this->dashboard('admin/tracking', [
            'title'   => 'Jeepney tracking',
            'leaflet' => true,
            'fleet'   => $fleet,
            'routes'  => Route::all(),
            'onRoad'  => count(array_filter($fleet, fn($u) => $u['state'] === 'on_trip')),
            'riding'  => array_sum(array_map(fn($u) => (int) $u['onboard'], $fleet)),
            'today'   => $blocked,
        ]);
    }

    public function fleet(): void
    {
        $this->json(['units' => Jeepney::fleet()]);
    }

    public function addUnit(): void
    {
        $this->verifyCsrf();
        $code = strtoupper(preg_replace('/\s+/', ' ', $this->input('unit_code')));
        $plate = strtoupper($this->input('plate_no'));
        $cap = (int) $this->input('capacity', '25');
        if (!preg_match('/^[A-Z]{2,4} ?\d{2,4}$/', $code)) {
            flash('error', 'Unit codes look like BCN 110.');
        } elseif (Jeepney::codeTaken($code)) {
            flash('error', "$code is already in the fleet.");
        } elseif ($plate === '' || $cap < 10 || $cap > 40) {
            flash('error', 'Add a plate number and a capacity between 10 and 40.');
        } else {
            Jeepney::create($code, $plate, $cap, $this->user['id']);
            ActivityLog::add($this->user['id'], 'unit_added', "Added unit $code ($plate, $cap seats)");
            flash('success', "$code is in the fleet.");
        }
        $this->redirect('/admin/tracking#fleet');
    }

    public function unitStatus(string $id): void
    {
        $this->verifyCsrf();
        $unit = Jeepney::find((int) $id);
        $status = $this->input('status');
        if ($unit && in_array($status, ['active', 'breakdown', 'offline'], true)) {
            Jeepney::setStatus((int) $id, $status);
            ActivityLog::add($this->user['id'], 'unit_status', "{$unit['unit_code']} set to $status");
            flash('success', "{$unit['unit_code']} is now marked $status.");
        }
        $this->redirect('/admin/tracking#fleet');
    }

    public function addRoute(): void
    {
        $this->verifyCsrf();
        $origin = $this->input('origin');
        $dest = $this->input('destination');
        $fare = (float) $this->input('base_fare');
        if ($origin === '' || $dest === '' || $fare < 1 || $fare > 200) {
            flash('error', 'Add a start, an end and a fare between ₱1 and ₱200.');
        } else {
            Route::create($origin, $dest, $fare);
            ActivityLog::add($this->user['id'], 'route_added', "Added route $origin – $dest at " . peso($fare));
            flash('success', "Route $origin – $dest added.");
        }
        $this->redirect('/admin/tracking#routes');
    }

    public function routeFare(string $id): void
    {
        $this->verifyCsrf();
        $route = Route::find((int) $id);
        $fare = (float) $this->input('base_fare');
        if ($route && $fare >= 1 && $fare <= 200) {
            Route::updateFare((int) $id, $fare);
            ActivityLog::add($this->user['id'], 'fare_changed', "{$route['route_name']} fare set to " . peso($fare));
            flash('success', "{$route['route_name']} now costs " . peso($fare) . '.');
        }
        $this->redirect('/admin/tracking#routes');
    }

    // ---- reports and logs ---------------------------------------------------------
    private function range(): int
    {
        $d = (int) $this->input('days', '7');
        return isset(self::RANGES[$d]) ? $d : 7;
    }

    public function reports(): void
    {
        $days = $this->range();
        $from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
        $this->dashboard('admin/reports', [
            'title'   => 'Fare reports',
            'days'    => $days,
            'ranges'  => self::RANGES,
            'summary' => Report::summary($from),
            'units'   => Report::byUnit($from),
            'routes'  => Report::byRoute($from),
            'daily'   => Report::daily($from),
            'from'    => $from,
        ]);
    }

    public function exportReport(): void
    {
        $days = $this->range();
        $from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
        $rows = array_map(fn($u) => [$u['unit_code'], $u['trips'], $u['passengers'], number_format($u['revenue'], 2, '.', ''),
            $u['blocked_full'], $u['avg_peak'] ? round($u['avg_peak'] / $u['capacity'] * 100) . '%' : ''], Report::byUnit($from));
        $this->csv("1tap-fares-per-unit-{$days}d.csv", ['Unit', 'Trips', 'Paid passengers', 'Revenue (PHP)', 'Scans blocked at full', 'Average peak load'], $rows);
    }

    public function logs(): void
    {
        $group = isset(ActivityLog::GROUPS[$this->input('type')]) ? $this->input('type') : '';
        $page = max(1, (int) $this->input('page', '1'));
        $per = 40;
        $total = ActivityLog::count($group);
        $this->dashboard('admin/logs', [
            'title'  => 'System logs',
            'logs'   => ActivityLog::latest($group, $per, ($page - 1) * $per),
            'group'  => $group,
            'groups' => ActivityLog::GROUPS,
            'page'   => $page,
            'pages'  => max(1, (int) ceil($total / $per)),
            'total'  => $total,
        ]);
    }
}

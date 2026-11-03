<?php

class PassengerController extends Controller
{
    private array $user;
    private array $wallet;

    public function __construct()
    {
        $this->user = $this->requireRole('passenger');
        $wallet = Wallet::forUser($this->user['id']);
        if (!$wallet) {
            Wallet::create($this->user['id']); // accounts made before wallets existed
            $wallet = Wallet::forUser($this->user['id']);
        }
        $this->wallet = $wallet;
    }

    public function home(): void
    {
        $fleet = array_values(array_filter(Jeepney::fleet(), fn($u) => $u['state'] === 'on_trip'));
        usort($fleet, fn($a, $b) => $b['seats_left'] <=> $a['seats_left']);

        $recent = [];
        foreach (Fare::forWallet($this->wallet['wallet_id'], 5) as $f) {
            $recent[] = ['when' => $f['scanned_at'], 'label' => $f['route_name'], 'amount' => -$f['amount'], 'status' => $f['status']];
        }
        foreach (Reload::forWallet($this->wallet['wallet_id'], 5) as $r) {
            $recent[] = ['when' => $r['reloaded_at'], 'label' => 'Wallet reload · ' . config('reload.methods')[$r['method']], 'amount' => +$r['amount'], 'status' => 'reload'];
        }
        usort($recent, fn($a, $b) => strcmp($b['when'], $a['when']));

        $this->dashboard('passenger/home', [
            'title'   => 'Home',
            'wallet'  => $this->wallet,
            'month'   => Fare::walletMonth($this->wallet['wallet_id']),
            'next'    => $fleet[0] ?? null,
            'recent'  => array_slice($recent, 0, 5),
            'methods' => config('reload.methods'),
        ]);
    }

    public function qr(): void
    {
        $this->dashboard('passenger/qr', ['title' => 'My QR', 'wallet' => $this->wallet]);
    }

    /** Called by the page every 30 seconds to draw the next QR. */
    public function qrToken(): void
    {
        $data = Wallet::payload($this->wallet);
        $data['balance'] = peso($this->wallet['balance']);
        if ($this->wallet['status'] === 'frozen') {
            $data['payload'] = null;
        }
        $this->json($data);
    }

    public function freeze(): void
    {
        $this->verifyCsrf();
        Wallet::freeze($this->wallet['wallet_id']);
        ActivityLog::add($this->user['id'], 'qr_frozen', 'Froze ride QR');
        flash('success', 'Your QR is frozen. No one can pay with it until you unfreeze it.');
        $this->back('/passenger/qr');
    }

    public function unfreeze(): void
    {
        $this->verifyCsrf();
        Wallet::unfreeze($this->wallet['wallet_id']);
        ActivityLog::add($this->user['id'], 'qr_unfrozen', 'Unfroze ride QR with a new code');
        flash('success', 'Your QR works again. It has a brand-new code, so old screenshots are useless.');
        $this->back('/passenger/qr');
    }

    public function track(): void
    {
        $this->dashboard('passenger/track', ['title' => 'Track a jeepney', 'routes' => Route::all(), 'leaflet' => true]);
    }

    public function fleet(): void
    {
        $units = array_map(fn($u) => array_intersect_key($u, array_flip(
            ['unit_code', 'route_name', 'capacity', 'onboard', 'seats_left', 'state', 'latitude', 'longitude', 'seen'])), Jeepney::fleet());
        $this->json(['units' => $units]);
    }

    public function rides(): void
    {
        $this->dashboard('passenger/rides', [
            'title' => 'Ride history',
            'rides' => Fare::forWallet($this->wallet['wallet_id'], 200),
            'month' => Fare::walletMonth($this->wallet['wallet_id']),
        ]);
    }

    public function reloadForm(): void
    {
        $this->dashboard('passenger/reload', [
            'title'   => 'Reload wallet',
            'wallet'  => $this->wallet,
            'history' => Reload::forWallet($this->wallet['wallet_id'], 50),
            'methods' => config('reload.methods'),
        ]);
    }

    /** In the demo the payment is assumed to go through; a real build would call GCash or Maya here. */
    public function reload(): void
    {
        $this->verifyCsrf();
        $amount = round((float) $this->input('amount'), 2);
        $method = $this->input('method', 'gcash');
        $min = config('reload.min');
        $max = config('reload.max');

        if (!isset(config('reload.methods')[$method])) {
            flash('error', 'Pick how you are paying for the reload.');
        } elseif ($amount < $min || $amount > $max) {
            flash('error', 'Reload between ' . peso($min, 0) . ' and ' . peso($max, 0) . '.');
        } else {
            Model::transaction(function () use ($amount, $method) {
                Reload::create($this->wallet['wallet_id'], $amount, $method);
                Wallet::credit($this->wallet['wallet_id'], $amount);
            });
            ActivityLog::add($this->user['id'], 'reload', 'Reloaded ' . peso($amount) . ' via ' . config('reload.methods')[$method]);
            flash('success', peso($amount) . ' added to your wallet.');
        }
        $this->back('/passenger/reload');
    }

    public function profile(): void
    {
        $this->dashboard('passenger/profile', [
            'title'   => 'Profile',
            'account' => User::find($this->user['id']),
            'wallet'  => $this->wallet,
            'errors'  => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
    }

    /** Any change needs the current password. */
    public function updateProfile(): void
    {
        $this->verifyCsrf();
        $account = User::find($this->user['id']);
        $d = [
            'full_name' => $this->input('full_name'),
            'email'     => $this->input('email'),
            'phone'     => preg_replace('/[\s-]/', '', $this->input('phone')),
            'username'  => strtolower($this->input('username')),
            'password'  => $_POST['new_password'] ?? '',
        ];
        $errors = AuthController::validate($d, $_POST['new_password_confirm'] ?? '', $this->user['id'], false);
        if (!password_verify($_POST['current_password'] ?? '', $account['password_hash'])) {
            $errors['current_password'] = 'Your current password is wrong.';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            remember_input(['full_name', 'email', 'phone', 'username']);
            $this->redirect('/passenger/profile');
        }

        User::updateDetails($this->user['id'], $d);
        if ($d['password'] !== '') {
            User::setPassword($this->user['id'], $d['password']);
            ActivityLog::add($this->user['id'], 'password_changed', 'Changed password');
        }
        ActivityLog::add($this->user['id'], 'profile_updated', 'Updated profile details');
        Auth::refreshName($d['full_name'], $d['username']);
        forget_input();
        flash('success', 'Your profile is saved.');
        $this->redirect('/passenger/profile');
    }
}

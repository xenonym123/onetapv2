<?php

class DriverController extends Controller
{
    private array $user;

    public function __construct()
    {
        $this->user = $this->requireRole('driver');
    }

    private function trip(): ?array
    {
        return Trip::activeForDriver($this->user['id']);
    }

    public function home(): void
    {
        $today = date('Y-m-d 00:00:00');
        $this->dashboard('driver/home', [
            'title'     => 'Driver dashboard',
            'trip'      => $this->trip(),
            'totals'    => Fare::driverTotals($this->user['id'], $today),
            'yesterday' => Fare::driverTotals($this->user['id'], date('Y-m-d 00:00:00', strtotime('-1 day'))),
            'trips'     => Trip::todayForDriver($this->user['id']),
            'units'     => Jeepney::available(),
            'routes'    => Route::all(),
        ]);
    }

    public function startTrip(): void
    {
        $this->verifyCsrf();
        $unitId = (int) $this->input('unit_id');
        $routeId = (int) $this->input('route_id');
        $unit = Jeepney::find($unitId);

        if ($this->trip()) {
            flash('error', 'End your current trip before starting a new one.');
        } elseif (!$unit || $unit['status'] !== 'active' || Trip::unitBusy($unitId)) {
            flash('error', "That unit can't go out right now. Pick another one.");
        } elseif (!Route::find($routeId)) {
            flash('error', 'Pick a route.');
        } else {
            $tripId = Trip::start($this->user['id'], $unitId, $routeId);
            ActivityLog::add($this->user['id'], 'trip_start', "Started trip #$tripId on {$unit['unit_code']}");
            flash('success', "Trip started on {$unit['unit_code']}. Open the QR scanner to take fares.");
        }
        $this->redirect('/driver');
    }

    public function endTrip(): void
    {
        $this->verifyCsrf();
        if ($trip = $this->trip()) {
            Trip::end((int) $trip['trip_id']);
            $s = Fare::tripStats((int) $trip['trip_id']);
            ActivityLog::add($this->user['id'], 'trip_end', "Ended trip #{$trip['trip_id']} · {$s['paid']} paid fares · " . peso($s['collected']));
            flash('success', 'Trip ended. ' . (int) $s['paid'] . ' paid fares, ' . peso($s['collected']) . ' collected.');
        }
        $this->redirect('/driver');
    }

    public function count(): void
    {
        $this->dashboard('driver/count', ['title' => 'Passenger count', 'trip' => $this->trip(), 'live' => $this->liveData()]);
    }

    public function countData(): void
    {
        $this->json($this->liveData());
    }

    /** The camera simulator: someone got on (+1) or off (-1). */
    public function countChange(): void
    {
        $this->verifyCsrf();
        $trip = $this->trip();
        if (!$trip) {
            $this->json(['ok' => false, 'message' => 'Start a trip first.'], 409);
        }
        $delta = (int) ($this->jsonBody()['delta'] ?? 0) >= 0 ? 1 : -1;
        $last = PassengerLog::latest((int) $trip['trip_id']);
        if ($delta > 0 && (int) $last['onboard'] >= (int) $trip['capacity']) {
            $this->json(['ok' => false, 'message' => 'The unit is already full.'] + $this->liveData(), 409);
        }
        PassengerLog::change((int) $trip['trip_id'], $delta);
        $this->json(['ok' => true] + $this->liveData());
    }

    private function liveData(): array
    {
        $trip = $this->trip();
        if (!$trip) {
            return ['trip' => null];
        }
        $id = (int) $trip['trip_id'];
        $log = PassengerLog::latest($id);
        $stats = Fare::tripStats($id);
        $alerts = array_map(fn($f) => [
            'time'   => fmt_time($f['scanned_at']),
            'name'   => first_name($f['full_name']),
            'status' => $f['status'],
            'amount' => peso($f['amount']),
        ], Fare::recentOnTrip($id, 8));

        return [
            'trip'       => ['id' => $id, 'unit' => $trip['unit_code'], 'route' => $trip['route_name'], 'fare' => (float) $trip['base_fare'],
                             'started' => fmt_time($trip['started_at'])],
            'capacity'   => (int) $trip['capacity'],
            'onboard'    => (int) $log['onboard'],
            'boarded'    => (int) $log['boarded'],
            'alighted'   => (int) $log['alighted'],
            'seats_left' => max(0, (int) $trip['capacity'] - (int) $log['onboard']),
            'full'       => (int) $log['onboard'] >= (int) $trip['capacity'],
            'paid'       => (int) $stats['paid'],
            'collected'  => peso($stats['collected']),
            'blocked'    => (int) $stats['blocked'],
            'alerts'     => $alerts,
        ];
    }

    public function scan(): void
    {
        $this->dashboard('driver/scan', ['title' => 'QR scanner', 'trip' => $this->trip(), 'live' => $this->liveData()]);
    }

    /**
     * The scanner on the jeepney. Takes the fare from the passenger's wallet,
     * or blocks it when the unit is full, the QR is frozen, or the balance is too low.
     */
    public function scanSubmit(): void
    {
        $this->verifyCsrf();
        $trip = $this->trip();
        if (!$trip) {
            $this->json(['ok' => false, 'result' => 'error', 'message' => 'Start a trip first, then scan.'], 409);
        }
        $tripId = (int) $trip['trip_id'];
        $fare = (float) $trip['base_fare'];
        $wallet = Wallet::fromPayload((string) ($this->jsonBody()['code'] ?? ''));

        if (!$wallet) {
            $this->json(['ok' => false, 'result' => 'invalid', 'message' => "Invalid or expired QR. Ask the passenger to open a fresh one."] + $this->liveData());
        }
        $name = first_name($wallet['full_name']);
        $walletId = (int) $wallet['wallet_id'];
        $log = PassengerLog::latest($tripId);

        if ($wallet['status'] === 'frozen' || $wallet['user_status'] !== 'active') {
            $result = ['blocked_frozen', "$name's QR is frozen. Fare not taken."];
        } elseif (Fare::alreadyPaid($walletId, $tripId)) {
            $this->json(['ok' => false, 'result' => 'duplicate', 'message' => "$name already paid on this trip."] + $this->liveData());
        } elseif ((int) $log['onboard'] >= (int) $trip['capacity']) {
            $result = ['blocked_full', "Unit full ({$trip['capacity']}/{$trip['capacity']}). $name can't board, fare not taken."];
        } else {
            $paid = Model::transaction(function () use ($walletId, $tripId, $fare) {
                if (!Wallet::debit($walletId, $fare)) {
                    return false;
                }
                Fare::record($walletId, $tripId, $fare, 'paid');
                PassengerLog::change($tripId, +1);
                return true;
            });
            if ($paid) {
                ActivityLog::add($this->user['id'], 'fare_paid', peso($fare) . " from $name on {$trip['unit_code']}");
                $this->json(['ok' => true, 'result' => 'paid', 'message' => "Paid " . peso($fare) . ". Welcome aboard, $name!"] + $this->liveData());
            }
            $result = ['blocked_balance', "$name's balance is too low for " . peso($fare) . '.'];
        }

        Fare::record($walletId, $tripId, $fare, $result[0]);
        ActivityLog::add($this->user['id'], 'fare_blocked', "{$trip['unit_code']}: {$result[1]}");
        $this->json(['ok' => false, 'result' => $result[0], 'message' => $result[1]] + $this->liveData());
    }

    /** The driver's phone sends its location while a trip is running. */
    public function gps(): void
    {
        $this->verifyCsrf();
        $trip = $this->trip();
        $b = $this->jsonBody();
        $lat = (float) ($b['lat'] ?? 0);
        $lng = (float) ($b['lng'] ?? 0);
        if ($trip && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && ($lat != 0 || $lng != 0)) {
            GpsPing::record((int) $trip['unit_id'], $lat, $lng);
            $this->json(['ok' => true]);
        }
        $this->json(['ok' => false], 409);
    }

    public function earnings(): void
    {
        $id = $this->user['id'];
        $this->dashboard('driver/earnings', [
            'title' => 'Earnings',
            'today' => Fare::driverTotals($id, date('Y-m-d 00:00:00')),
            'week'  => Fare::driverTotals($id, date('Y-m-d 00:00:00', strtotime('monday this week'))),
            'month' => Fare::driverTotals($id, date('Y-m-01 00:00:00')),
            'days'  => Fare::driverByDay($id, 14),
        ]);
    }

    public function route(): void
    {
        $this->dashboard('driver/route', [
            'title'  => 'Routes',
            'stats'  => Trip::routeStatsForDriver($this->user['id']),
            'routes' => Route::all(),
            'trip'   => $this->trip(),
        ]);
    }
}

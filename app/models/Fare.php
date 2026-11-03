<?php
// fare_transactions: one row per QR scan, paid or blocked.

class Fare extends Model
{
    public static function record(int $walletId, int $tripId, float $amount, string $status): int
    {
        return self::insert("INSERT INTO fare_transactions (wallet_id, trip_id, amount, status, scanned_at) VALUES (?, ?, ?, ?, ?)",
            [$walletId, $tripId, $amount, $status, self::now()]);
    }

    public static function alreadyPaid(int $walletId, int $tripId): bool
    {
        return (bool) self::value("SELECT 1 FROM fare_transactions WHERE wallet_id = ? AND trip_id = ? AND status = 'paid'",
            [$walletId, $tripId]);
    }

    /** Ride history for a passenger. */
    public static function forWallet(int $walletId, int $limit = 100): array
    {
        return self::rows(
            "SELECT f.*, r.route_name, j.unit_code FROM fare_transactions f
             JOIN trips t ON t.trip_id = f.trip_id JOIN routes r ON r.route_id = t.route_id
             JOIN jeepneys j ON j.unit_id = t.unit_id
             WHERE f.wallet_id = ? ORDER BY f.scanned_at DESC LIMIT " . (int) $limit, [$walletId]);
    }

    public static function walletMonth(int $walletId): array
    {
        return self::one(
            "SELECT COUNT(*) AS rides, COALESCE(SUM(amount), 0) AS spent FROM fare_transactions
             WHERE wallet_id = ? AND status = 'paid' AND scanned_at >= ?", [$walletId, date('Y-m-01 00:00:00')]);
    }

    /** Totals for one driver from a date onward. */
    public static function driverTotals(int $driverId, string $from): array
    {
        return self::one(
            "SELECT COUNT(f.txn_id) AS passengers, COALESCE(SUM(f.amount), 0) AS earnings, COUNT(DISTINCT t.trip_id) AS trips
             FROM trips t LEFT JOIN fare_transactions f ON f.trip_id = t.trip_id AND f.status = 'paid'
             WHERE t.driver_id = ? AND t.started_at >= ?", [$driverId, $from]);
    }

    public static function driverByDay(int $driverId, int $days = 14): array
    {
        return self::rows(
            "SELECT DATE(t.started_at) AS day, COUNT(DISTINCT t.trip_id) AS trips, COUNT(f.txn_id) AS passengers,
                    COALESCE(SUM(f.amount), 0) AS earnings
             FROM trips t LEFT JOIN fare_transactions f ON f.trip_id = t.trip_id AND f.status = 'paid'
             WHERE t.driver_id = ? AND t.started_at >= ?
             GROUP BY DATE(t.started_at) ORDER BY day DESC", [$driverId, date('Y-m-d', strtotime("-$days days"))]);
    }

    public static function tripStats(int $tripId): array
    {
        return self::one(
            "SELECT SUM(status = 'paid') AS paid, COALESCE(SUM(CASE WHEN status = 'paid' THEN amount END), 0) AS collected,
                    SUM(status = 'blocked_full') AS blocked_full, SUM(status <> 'paid') AS blocked
             FROM fare_transactions WHERE trip_id = ?", [$tripId]);
    }

    /** Recent scans on a trip, newest first, for the driver's alerts list. */
    public static function recentOnTrip(int $tripId, int $limit = 8): array
    {
        return self::rows(
            "SELECT f.*, u.full_name FROM fare_transactions f JOIN wallets w ON w.wallet_id = f.wallet_id
             JOIN users u ON u.user_id = w.user_id WHERE f.trip_id = ? ORDER BY f.scanned_at DESC LIMIT " . (int) $limit, [$tripId]);
    }

    public static function totalsSince(string $from): array
    {
        return self::one(
            "SELECT SUM(status = 'paid') AS paid, COALESCE(SUM(CASE WHEN status = 'paid' THEN amount END), 0) AS revenue,
                    SUM(status = 'blocked_full') AS blocked_full, SUM(status <> 'paid') AS blocked
             FROM fare_transactions WHERE scanned_at >= ?", [$from]);
    }
}

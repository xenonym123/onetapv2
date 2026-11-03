<?php
// A trip is one run of one jeepney, on one route, with one driver.

class Trip extends Model
{
    private const SELECT = "SELECT t.*, j.unit_code, j.capacity, j.plate_no, r.route_name, r.base_fare, u.full_name AS driver_name
                            FROM trips t JOIN jeepneys j ON j.unit_id = t.unit_id JOIN routes r ON r.route_id = t.route_id
                            JOIN users u ON u.user_id = t.driver_id";

    public static function activeForDriver(int $driverId): ?array
    {
        return self::one(self::SELECT . " WHERE t.driver_id = ? AND t.ended_at IS NULL ORDER BY t.started_at DESC LIMIT 1", [$driverId]);
    }

    public static function start(int $driverId, int $unitId, int $routeId): int
    {
        return self::transaction(function () use ($driverId, $unitId, $routeId) {
            $tripId = self::insert("INSERT INTO trips (unit_id, route_id, driver_id, started_at) VALUES (?, ?, ?, ?)",
                [$unitId, $routeId, $driverId, self::now()]);
            PassengerLog::record($tripId, 0, 0, 0);
            return $tripId;
        });
    }

    public static function end(int $tripId): void
    {
        self::run("UPDATE trips SET ended_at = ? WHERE trip_id = ? AND ended_at IS NULL", [self::now(), $tripId]);
    }

    public static function unitBusy(int $unitId): bool
    {
        return (bool) self::value("SELECT 1 FROM trips WHERE unit_id = ? AND ended_at IS NULL", [$unitId]);
    }

    /** Today's trips for a driver, with passengers and fares. */
    public static function todayForDriver(int $driverId): array
    {
        return self::rows(
            "SELECT t.trip_id, t.started_at, t.ended_at, r.route_name, j.unit_code,
                    COUNT(f.txn_id) AS passengers, COALESCE(SUM(f.amount), 0) AS collected
             FROM trips t JOIN routes r ON r.route_id = t.route_id JOIN jeepneys j ON j.unit_id = t.unit_id
             LEFT JOIN fare_transactions f ON f.trip_id = t.trip_id AND f.status = 'paid'
             WHERE t.driver_id = ? AND t.started_at >= ?
             GROUP BY t.trip_id, t.started_at, t.ended_at, r.route_name, j.unit_code ORDER BY t.started_at DESC",
            [$driverId, date('Y-m-d 00:00:00')]);
    }

    /** Per-route performance for one driver over the last 30 days. */
    public static function routeStatsForDriver(int $driverId): array
    {
        return self::rows(
            "SELECT r.route_name, r.base_fare, COUNT(DISTINCT t.trip_id) AS trips,
                    COUNT(f.txn_id) / NULLIF(COUNT(DISTINCT t.trip_id), 0) AS avg_passengers,
                    AVG(j.capacity) AS capacity
             FROM trips t JOIN routes r ON r.route_id = t.route_id JOIN jeepneys j ON j.unit_id = t.unit_id
             LEFT JOIN fare_transactions f ON f.trip_id = t.trip_id AND f.status = 'paid'
             WHERE t.driver_id = ? AND t.started_at >= ?
             GROUP BY r.route_id, r.route_name, r.base_fare ORDER BY trips DESC",
            [$driverId, date('Y-m-d', strtotime('-30 days'))]);
    }
}

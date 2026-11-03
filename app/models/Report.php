<?php
// The operator numbers on the admin side: fares per unit and per route, load, peak hour.

class Report extends Model
{
    public static function summary(string $from): array
    {
        $row = self::one(
            "SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount END), 0) AS revenue, SUM(status = 'paid') AS paid,
                    SUM(status = 'blocked_full') AS blocked_full
             FROM fare_transactions WHERE scanned_at >= ?", [$from]);
        $row['avg_fare'] = $row['paid'] ? $row['revenue'] / $row['paid'] : 0;
        $peak = self::one(
            "SELECT HOUR(scanned_at) AS h, COUNT(*) AS n FROM fare_transactions
             WHERE status = 'paid' AND scanned_at >= ? GROUP BY HOUR(scanned_at) ORDER BY n DESC LIMIT 1", [$from]);
        $row['peak_hour'] = $peak ? (int) $peak['h'] : null;
        $row['reloads'] = Reload::totalSince($from);
        return $row;
    }

    /** Per jeepney: trips, passengers, revenue, blocked, and how full it ran on average. */
    public static function byUnit(string $from): array
    {
        return self::rows(
            "SELECT j.unit_code, j.capacity, COUNT(DISTINCT t.trip_id) AS trips,
                    COALESCE(SUM(f.status = 'paid'), 0) AS passengers,
                    COALESCE(SUM(CASE WHEN f.status = 'paid' THEN f.amount END), 0) AS revenue,
                    COALESCE(SUM(f.status = 'blocked_full'), 0) AS blocked_full,
                    (SELECT AVG(peak) FROM (SELECT t2.unit_id, MAX(pl.onboard) AS peak FROM trips t2
                        JOIN passenger_logs pl ON pl.trip_id = t2.trip_id WHERE t2.started_at >= ? GROUP BY t2.trip_id, t2.unit_id) x
                     WHERE x.unit_id = j.unit_id) AS avg_peak
             FROM jeepneys j LEFT JOIN trips t ON t.unit_id = j.unit_id AND t.started_at >= ?
             LEFT JOIN fare_transactions f ON f.trip_id = t.trip_id
             GROUP BY j.unit_id, j.unit_code, j.capacity ORDER BY revenue DESC", [$from, $from]);
    }

    public static function byRoute(string $from): array
    {
        return self::rows(
            "SELECT r.route_name, r.base_fare, COUNT(DISTINCT t.trip_id) AS trips,
                    COALESCE(SUM(f.status = 'paid'), 0) AS passengers,
                    COALESCE(SUM(CASE WHEN f.status = 'paid' THEN f.amount END), 0) AS revenue
             FROM routes r LEFT JOIN trips t ON t.route_id = r.route_id AND t.started_at >= ?
             LEFT JOIN fare_transactions f ON f.trip_id = t.trip_id
             GROUP BY r.route_id, r.route_name, r.base_fare ORDER BY revenue DESC", [$from]);
    }

    /** Revenue per day for the chart on the reports page. */
    public static function daily(string $from): array
    {
        return self::rows(
            "SELECT DATE(scanned_at) AS day, COALESCE(SUM(amount), 0) AS revenue, COUNT(*) AS paid
             FROM fare_transactions WHERE status = 'paid' AND scanned_at >= ? GROUP BY DATE(scanned_at) ORDER BY day", [$from]);
    }
}

<?php

class Jeepney extends Model
{
    public static function all(): array
    {
        return self::rows("SELECT * FROM jeepneys ORDER BY unit_code");
    }

    public static function find(int $id): ?array
    {
        return self::one("SELECT * FROM jeepneys WHERE unit_id = ?", [$id]);
    }

    /** Units a driver can start a trip on: active and not already out on a trip. */
    public static function available(): array
    {
        return self::rows("SELECT * FROM jeepneys j WHERE j.status = 'active'
                          AND NOT EXISTS (SELECT 1 FROM trips t WHERE t.unit_id = j.unit_id AND t.ended_at IS NULL)
                          ORDER BY unit_code");
    }

    public static function create(string $code, string $plate, int $capacity, int $operatorId): int
    {
        return self::insert("INSERT INTO jeepneys (operator_id, unit_code, plate_no, capacity, status) VALUES (?, ?, ?, ?, 'active')",
            [$operatorId, $code, $plate, $capacity]);
    }

    public static function setStatus(int $id, string $status): void
    {
        self::run("UPDATE jeepneys SET status = ? WHERE unit_id = ?", [$status, $id]);
    }

    public static function codeTaken(string $code): bool
    {
        return (bool) self::value("SELECT 1 FROM jeepneys WHERE unit_code = ?", [$code]);
    }

    /** Every unit with its current trip, live count and last GPS point. Feeds both maps. */
    public static function fleet(): array
    {
        $rows = self::rows(
            "SELECT j.unit_id, j.unit_code, j.plate_no, j.capacity, j.status,
                    t.trip_id, r.route_name, u.full_name AS driver_name,
                    (SELECT onboard FROM passenger_logs pl WHERE pl.trip_id = t.trip_id ORDER BY pl.count_id DESC LIMIT 1) AS onboard,
                    g.latitude, g.longitude, g.recorded_at
             FROM jeepneys j
             LEFT JOIN trips t ON t.unit_id = j.unit_id AND t.ended_at IS NULL
             LEFT JOIN routes r ON r.route_id = t.route_id
             LEFT JOIN users u ON u.user_id = t.driver_id
             LEFT JOIN gps_pings g ON g.ping_id = (SELECT MAX(ping_id) FROM gps_pings gp WHERE gp.unit_id = j.unit_id)
             ORDER BY j.unit_code");
        foreach ($rows as &$row) {
            $row['onboard'] = $row['trip_id'] ? (int) $row['onboard'] : null;
            $row['seats_left'] = $row['trip_id'] ? max(0, (int) $row['capacity'] - (int) $row['onboard']) : null;
            // breakdown / offline as set by the admin; otherwise on a trip or parked
            $row['state'] = $row['status'] !== 'active' ? $row['status'] : ($row['trip_id'] ? 'on_trip' : 'parked');
            $row['latitude'] = $row['latitude'] !== null ? (float) $row['latitude'] : null;
            $row['longitude'] = $row['longitude'] !== null ? (float) $row['longitude'] : null;
            $row['seen'] = time_ago($row['recorded_at']);
        }
        return $rows;
    }
}

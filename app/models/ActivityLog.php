<?php
// What feeds the admin's System Logs page.

class ActivityLog extends Model
{
    /** Groups used by the filter on the logs page. */
    public const GROUPS = [
        'login'  => ['Logins', ['login', 'logout', 'login_failed', 'register']],
        'money'  => ['Reloads and fares', ['reload', 'fare_paid', 'fare_blocked']],
        'trips'  => ['Trips and units', ['trip_start', 'trip_end', 'unit_status', 'unit_added', 'route_added', 'fare_changed']],
        'admin'  => ['Account changes', ['user_added', 'user_updated', 'user_status', 'profile_updated', 'password_changed', 'qr_frozen', 'qr_unfrozen']],
    ];

    public static function add(?int $userId, string $action, string $details = ''): void
    {
        self::insert("INSERT INTO activity_logs (user_id, action, details, logged_at) VALUES (?, ?, ?, ?)",
            [$userId, $action, mb_substr($details, 0, 255), self::now()]);
    }

    public static function latest(string $group = '', int $limit = 60, int $offset = 0): array
    {
        [$where, $params] = self::filter($group);
        return self::rows("SELECT l.*, u.full_name, u.username FROM activity_logs l LEFT JOIN users u ON u.user_id = l.user_id
                          $where ORDER BY l.log_id DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset, $params);
    }

    public static function count(string $group = ''): int
    {
        [$where, $params] = self::filter($group);
        return (int) self::value("SELECT COUNT(*) FROM activity_logs l $where", $params);
    }

    private static function filter(string $group): array
    {
        if (!isset(self::GROUPS[$group])) {
            return ['', []];
        }
        $actions = self::GROUPS[$group][1];
        return ['WHERE l.action IN (' . implode(',', array_fill(0, count($actions), '?')) . ')', $actions];
    }
}

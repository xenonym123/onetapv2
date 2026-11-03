<?php

class User extends Model
{
    private const SELECT = "SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id";

    public static function find(int $id): ?array
    {
        return self::one(self::SELECT . " WHERE u.user_id = ?", [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return self::one(self::SELECT . " WHERE u.username = ?", [$username]);
    }

    public static function usernameTaken(string $username, int $exceptId = 0): bool
    {
        return (bool) self::value("SELECT 1 FROM users WHERE username = ? AND user_id <> ?", [$username, $exceptId]);
    }

    public static function create(array $d): int
    {
        $roleId = self::value("SELECT role_id FROM roles WHERE role_name = ?", [$d['role']]);
        return self::insert(
            "INSERT INTO users (role_id, full_name, email, phone, username, password_hash, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'active', ?)",
            [$roleId, $d['full_name'], $d['email'], $d['phone'], $d['username'],
             password_hash($d['password'], PASSWORD_DEFAULT), self::now()]
        );
    }

    public static function updateDetails(int $id, array $d): void
    {
        self::run(
            "UPDATE users SET full_name = ?, email = ?, phone = ?, username = ? WHERE user_id = ?",
            [$d['full_name'], $d['email'], $d['phone'], $d['username'], $id]
        );
    }

    public static function setRole(int $id, string $role): void
    {
        self::run("UPDATE users SET role_id = (SELECT role_id FROM roles WHERE role_name = ?) WHERE user_id = ?", [$role, $id]);
    }

    public static function setPassword(int $id, string $password): void
    {
        self::run("UPDATE users SET password_hash = ? WHERE user_id = ?", [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        self::run("UPDATE users SET status = ? WHERE user_id = ?", [$status, $id]);
    }

    /** User Management table: everyone, with wallet balance for passengers. */
    public static function listing(string $role = '', string $search = ''): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone, u.status, u.created_at, r.role_name,
                       w.balance, w.status AS wallet_status,
                       (SELECT MAX(login_at) FROM login_sessions ls WHERE ls.user_id = u.user_id) AS last_login
                FROM users u JOIN roles r ON r.role_id = u.role_id
                LEFT JOIN wallets w ON w.user_id = u.user_id WHERE 1 = 1";
        $params = [];
        if ($role !== '') {
            $sql .= " AND r.role_name = ?";
            $params[] = $role;
        }
        if ($search !== '') {
            $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            array_push($params, "%$search%", "%$search%", "%$search%");
        }
        return self::rows($sql . " ORDER BY r.role_id, u.full_name", $params);
    }

    public static function countByRole(): array
    {
        $rows = self::rows("SELECT r.role_name, COUNT(u.user_id) AS n FROM roles r
                           LEFT JOIN users u ON u.role_id = r.role_id AND u.status = 'active' GROUP BY r.role_name");
        return array_column($rows, 'n', 'role_name');
    }

    public static function drivers(): array
    {
        return self::rows("SELECT u.user_id, u.full_name FROM users u JOIN roles r ON r.role_id = u.role_id
                          WHERE r.role_name = 'driver' AND u.status = 'active' ORDER BY u.full_name");
    }
}

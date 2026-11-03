<?php
// Who is logged in. The role always comes from the database, never from the login tab.

class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int) $user['user_id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role_name'],
        ];
        $_SESSION['login_session_id'] = LoginSession::start((int) $user['user_id']);
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['login_session_id'])) {
            LoginSession::end((int) $_SESSION['login_session_id']);
        }
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function refreshName(string $fullName, string $username): void
    {
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['username'] = $username;
    }

    /** Where each role lands after signing in. */
    public static function home(?string $role = null): string
    {
        return match ($role ?? self::role()) {
            'admin'     => '/admin',
            'driver'    => '/driver',
            'passenger' => '/passenger',
            default     => '/login',
        };
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals(self::csrfToken(), $token);
    }
}

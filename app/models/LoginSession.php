<?php

class LoginSession extends Model
{
    public static function start(int $userId): int
    {
        return self::insert("INSERT INTO login_sessions (user_id, login_at) VALUES (?, ?)", [$userId, self::now()]);
    }

    public static function end(int $sessionId): void
    {
        self::run("UPDATE login_sessions SET logout_at = ? WHERE session_id = ? AND logout_at IS NULL", [self::now(), $sessionId]);
    }
}

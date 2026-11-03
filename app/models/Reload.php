<?php

class Reload extends Model
{
    public static function create(int $walletId, float $amount, string $method): int
    {
        return self::insert("INSERT INTO reloads (wallet_id, amount, method, reloaded_at) VALUES (?, ?, ?, ?)",
            [$walletId, $amount, $method, self::now()]);
    }

    public static function forWallet(int $walletId, int $limit = 50): array
    {
        return self::rows("SELECT * FROM reloads WHERE wallet_id = ? ORDER BY reloaded_at DESC LIMIT " . (int) $limit, [$walletId]);
    }

    public static function totalSince(string $from): float
    {
        return (float) self::value("SELECT COALESCE(SUM(amount), 0) FROM reloads WHERE reloaded_at >= ?", [$from]);
    }

    public static function countSince(string $from): int
    {
        return (int) self::value("SELECT COUNT(*) FROM reloads WHERE reloaded_at >= ?", [$from]);
    }
}

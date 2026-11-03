<?php
// A passenger's wallet. Its qr_token is the secret that signs the ride QR.
// The QR text changes every 30 seconds: 1TAP-<wallet>-<time window>-<signature>.

class Wallet extends Model
{
    public static function forUser(int $userId): ?array
    {
        return self::one("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    }

    public static function find(int $walletId): ?array
    {
        return self::one("SELECT w.*, u.full_name, u.status AS user_status FROM wallets w
                          JOIN users u ON u.user_id = w.user_id WHERE w.wallet_id = ?", [$walletId]);
    }

    public static function create(int $userId): int
    {
        return self::insert(
            "INSERT INTO wallets (user_id, qr_token, balance, status, updated_at) VALUES (?, ?, 0, 'active', ?)",
            [$userId, self::newToken(), self::now()]
        );
    }

    private static function newToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    private static function window(): int
    {
        return intdiv(time(), (int) config('app.qr_window', 30));
    }

    private static function sign(array $wallet, int $window): string
    {
        return substr(hash_hmac('sha256', $wallet['wallet_id'] . '.' . $window, $wallet['qr_token']), 0, 12);
    }

    /** The text inside the QR right now, and how many seconds until it changes. */
    public static function payload(array $wallet): array
    {
        $size = (int) config('app.qr_window', 30);
        $w = self::window();
        return [
            'payload'    => sprintf('1TAP-%d-%d-%s', $wallet['wallet_id'], $w, self::sign($wallet, $w)),
            'expires_in' => $size - (time() % $size),
            'status'     => $wallet['status'],
        ];
    }

    /** Checks a scanned QR. Accepts this window and the one before, so a code scanned as it flips still works. */
    public static function fromPayload(string $text): ?array
    {
        if (!preg_match('/^1TAP-(\d+)-(\d+)-([a-f0-9]{12})$/', trim($text), $m)) {
            return null;
        }
        $wallet = self::find((int) $m[1]);
        $window = (int) $m[2];
        $now = self::window();
        if (!$wallet || $window > $now || $window < $now - 1) {
            return null;
        }
        return hash_equals(self::sign($wallet, $window), $m[3]) ? $wallet : null;
    }

    public static function credit(int $walletId, float $amount): void
    {
        self::run("UPDATE wallets SET balance = balance + ?, updated_at = ? WHERE wallet_id = ?", [$amount, self::now(), $walletId]);
    }

    /** Takes the fare only if the balance covers it. Returns false otherwise. */
    public static function debit(int $walletId, float $amount): bool
    {
        return self::run(
            "UPDATE wallets SET balance = balance - ?, updated_at = ? WHERE wallet_id = ? AND balance >= ?",
            [$amount, self::now(), $walletId, $amount]
        ) === 1;
    }

    public static function freeze(int $walletId): void
    {
        self::run("UPDATE wallets SET status = 'frozen', updated_at = ? WHERE wallet_id = ?", [self::now(), $walletId]);
    }

    /** Unfreezing also issues a new token, so any old screenshot of the QR stops working. */
    public static function unfreeze(int $walletId): void
    {
        self::run("UPDATE wallets SET status = 'active', qr_token = ?, updated_at = ? WHERE wallet_id = ?",
            [self::newToken(), self::now(), $walletId]);
    }

    public static function totalBalance(): float
    {
        return (float) self::value("SELECT COALESCE(SUM(balance), 0) FROM wallets");
    }
}

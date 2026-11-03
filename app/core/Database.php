<?php
// One shared PDO connection for the whole request.

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $db = config('db');
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
            try {
                self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // Keep MySQL's clock on Philippine time so NOW() matches PHP.
                self::$pdo->exec("SET time_zone = '+08:00'");
            } catch (PDOException $e) {
                http_response_code(500);
                $detail = config('app.debug') ? e($e->getMessage()) : '';
                exit("<h1>Can't reach the database</h1><p>Start MySQL in XAMPP and import database/onetap_db.sql, "
                    . "then check app/config/config.php.</p><p><small>$detail</small></p>");
            }
        }
        return self::$pdo;
    }
}

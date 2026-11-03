<?php
// Base model: small helpers around prepared statements. All SQL lives in the model classes.

abstract class Model
{
    protected static function db(): PDO
    {
        return Database::pdo();
    }

    protected static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected static function rows(string $sql, array $params = []): array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected static function value(string $sql, array $params = [])
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $v = $stmt->fetchColumn();
        return $v === false ? null : $v;
    }

    /** Runs INSERT/UPDATE/DELETE. Returns affected rows. */
    protected static function run(string $sql, array $params = []): int
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::db()->lastInsertId();
    }

    public static function transaction(callable $fn)
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $result = $fn();
            $db->commit();
            return $result;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    protected static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

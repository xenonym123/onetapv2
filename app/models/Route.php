<?php

class Route extends Model
{
    public static function all(): array
    {
        return self::rows("SELECT * FROM routes ORDER BY route_name");
    }

    public static function find(int $id): ?array
    {
        return self::one("SELECT * FROM routes WHERE route_id = ?", [$id]);
    }

    public static function create(string $origin, string $destination, float $fare): int
    {
        return self::insert("INSERT INTO routes (route_name, origin, destination, base_fare) VALUES (?, ?, ?, ?)",
            ["$origin – $destination", $origin, $destination, $fare]);
    }

    public static function updateFare(int $id, float $fare): void
    {
        self::run("UPDATE routes SET base_fare = ? WHERE route_id = ?", [$fare, $id]);
    }
}

<?php

class GpsPing extends Model
{
    public static function record(int $unitId, float $lat, float $lng): void
    {
        self::insert("INSERT INTO gps_pings (unit_id, latitude, longitude, recorded_at) VALUES (?, ?, ?, ?)",
            [$unitId, $lat, $lng, self::now()]);
    }
}

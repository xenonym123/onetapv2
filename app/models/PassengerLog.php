<?php
// Camera counts. Each row is a running total for the trip: boarded, alighted, onboard now.

class PassengerLog extends Model
{
    public static function latest(int $tripId): array
    {
        return self::one("SELECT * FROM passenger_logs WHERE trip_id = ? ORDER BY count_id DESC LIMIT 1", [$tripId])
            ?? ['boarded' => 0, 'alighted' => 0, 'onboard' => 0, 'counted_at' => null];
    }

    public static function record(int $tripId, int $boarded, int $alighted, int $onboard): void
    {
        self::insert("INSERT INTO passenger_logs (trip_id, boarded, alighted, onboard, counted_at) VALUES (?, ?, ?, ?, ?)",
            [$tripId, $boarded, $alighted, $onboard, self::now()]);
    }

    /** Someone got on (+1) or off (-1). Never goes below zero. */
    public static function change(int $tripId, int $delta): array
    {
        $last = self::latest($tripId);
        $boarded = (int) $last['boarded'] + max(0, $delta);
        $alighted = (int) $last['alighted'] + max(0, -$delta);
        if ($delta < 0 && (int) $last['onboard'] === 0) {
            return $last;
        }
        $onboard = max(0, (int) $last['onboard'] + $delta);
        self::record($tripId, $boarded, $alighted, $onboard);
        return ['boarded' => $boarded, 'alighted' => $alighted, 'onboard' => $onboard, 'counted_at' => self::now()];
    }
}

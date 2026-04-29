<?php

namespace App\Support;

class TimeFmt
{
    /**
     * Convert decimal hours to HH:MM
     * e.g. 2.5 => 02:30, 0.25 => 00:15
     */
    public static function hoursToHm($hours): string
    {
        if ($hours === null || $hours === '') return '00:00';

        $hours = (float) $hours;

        // convert to minutes and round to nearest minute
        $minutes = (int) round($hours * 60);

        if ($minutes < 0) $minutes = 0;

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Convert HH:MM to decimal hours (optional utility for future)
     * e.g. 02:30 => 2.5
     */
    public static function hmToHours(?string $hm): float
    {
        if (!$hm) return 0.0;

        $parts = explode(':', trim($hm));
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);

        return round(($h * 60 + $m) / 60, 4);
    }
}

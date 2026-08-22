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

    /**
     * Convert decimal hours to a period-total label - "191hrs" for a whole
     * number of hours, "191h 24m" when there's a remainder. Distinct from
     * hoursToHm() (which is for a single day/punch, e.g. "08:45"): report
     * TOTALS read better as "Xhrs"/"Xh Ym" than as a colon-separated clock
     * time once you're summing a whole period.
     *
     * Handles negative input as-is (e.g. "-5h 24m" for a variance/deficit
     * total) rather than clamping to zero - unlike hoursToHm(), a total
     * genuinely can be negative (actual hours vs. an expected-hours target).
     */
    public static function hoursToTotalLabel($hours): string
    {
        if ($hours === null || $hours === '') return '0hrs';

        $hours = (float) $hours;
        $totalMinutes = (int) round($hours * 60);
        $sign = $totalMinutes < 0 ? '-' : '';
        $minutes = abs($totalMinutes);

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m === 0 ? "{$sign}{$h}hrs" : "{$sign}{$h}h {$m}m";
    }
}

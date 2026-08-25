<?php

namespace App\Support;

class TimeFmt
{

    public static function hoursToHm($hours): string
    {
        if ($hours === null || $hours === '') return '00:00';

        $hours = (float) $hours;

        $minutes = (int) round($hours * 60);

        if ($minutes < 0) $minutes = 0;

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    public static function hmToHours(?string $hm): float
    {
        if (!$hm) return 0.0;

        $parts = explode(':', trim($hm));
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);

        return round(($h * 60 + $m) / 60, 4);
    }

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

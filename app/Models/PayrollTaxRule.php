<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PayrollTaxRule extends Model
{
    protected $fillable = [
        'country',
        'jurisdiction',
        'rule_type',
        'lower_limit',
        'upper_limit',
        'rate',
        'fixed_amount',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'lower_limit' => 'float',
        'upper_limit' => 'float',
        'rate' => 'float',
        'fixed_amount' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public static function activeRules(string $country, string $ruleType, ?Carbon $date = null, ?string $jurisdiction = null)
    {
        $date = $date ?: now();

        return static::where('country', $country)
            ->where('rule_type', $ruleType)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->where(function ($q) use ($jurisdiction) {
                $q->whereNull('jurisdiction');
                if ($jurisdiction) {
                    $q->orWhere('jurisdiction', $jurisdiction);
                }
            })
            ->orderByDesc('effective_from')
            ->get();
    }

    public static function bands(string $country, string $ruleType, ?Carbon $date = null, ?string $jurisdiction = null)
    {
        return static::activeRules($country, $ruleType, $date, $jurisdiction)
            ->sortBy('lower_limit')
            ->values();
    }

    public static function flatRule(string $country, string $ruleType, ?Carbon $date = null, ?string $jurisdiction = null): ?self
    {
        return static::activeRules($country, $ruleType, $date, $jurisdiction)->first();
    }
}

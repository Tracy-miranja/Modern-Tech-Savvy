<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessCurrency extends Model
{
    protected $fillable = [
        'business_id',
        'currency_code',
        'currency_name',
        'symbol',
        'decimal_places',
        'is_primary',
        'rate_mode',
        'manual_rate',
        'auto_rate',
        'rate_fetched_at',
    ];

    protected $casts = [
        'is_primary'       => 'boolean',
        'decimal_places'   => 'integer',
        'manual_rate'      => 'float',
        'auto_rate'        => 'float',
        'rate_fetched_at'  => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function getEffectiveRateAttribute(): ?float
    {
        if ($this->rate_mode === 'manual') {
            return $this->manual_rate;
        }
        return $this->auto_rate;
    }

    public function getRateModeLabel(): string
    {
        return $this->rate_mode === 'manual' ? 'Manual' : 'Automatic';
    }

    public static function knownCurrencies(): array
    {
        return [
            'USD' => ['name' => 'United States Dollar',  'symbol' => '$',   'decimals' => 2],
            'EUR' => ['name' => 'Euro',                   'symbol' => '€',   'decimals' => 2],
            'GBP' => ['name' => 'British Pound',          'symbol' => '£',   'decimals' => 2],
            'KES' => ['name' => 'Kenyan Shilling',        'symbol' => 'KSh', 'decimals' => 2],
            'UGX' => ['name' => 'Ugandan Shilling',       'symbol' => 'USh', 'decimals' => 0],
            'TZS' => ['name' => 'Tanzanian Shilling',     'symbol' => 'TSh', 'decimals' => 2],
            'RWF' => ['name' => 'Rwandan Franc',          'symbol' => 'Fr',  'decimals' => 0],
            'ETB' => ['name' => 'Ethiopian Birr',         'symbol' => 'Br',  'decimals' => 2],
            'ZAR' => ['name' => 'South African Rand',     'symbol' => 'R',   'decimals' => 2],
            'NGN' => ['name' => 'Nigerian Naira',         'symbol' => '₦',   'decimals' => 2],
            'GHS' => ['name' => 'Ghanaian Cedi',          'symbol' => 'GH₵', 'decimals' => 2],
            'INR' => ['name' => 'Indian Rupee',           'symbol' => '₹',   'decimals' => 2],
            'CAD' => ['name' => 'Canadian Dollar',        'symbol' => 'CA$', 'decimals' => 2],
            'AUD' => ['name' => 'Australian Dollar',      'symbol' => 'A$',  'decimals' => 2],
            'AED' => ['name' => 'UAE Dirham',             'symbol' => 'د.إ', 'decimals' => 2],
            'JPY' => ['name' => 'Japanese Yen',           'symbol' => '¥',   'decimals' => 0],
            'CNY' => ['name' => 'Chinese Yuan',           'symbol' => '¥',   'decimals' => 2],
            'CHF' => ['name' => 'Swiss Franc',            'symbol' => 'Fr',  'decimals' => 2],
            'SEK' => ['name' => 'Swedish Krona',          'symbol' => 'kr',  'decimals' => 2],
            'NOK' => ['name' => 'Norwegian Krone',        'symbol' => 'kr',  'decimals' => 2],
        ];
    }
}

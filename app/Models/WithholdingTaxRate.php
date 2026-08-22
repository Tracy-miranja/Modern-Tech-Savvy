<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingTaxRate extends Model
{
    protected $fillable = [
        'payment_type',
        'label',
        'residency',
        'rate',
        'is_final_tax',
        'is_active'
    ];

    // Helper: get rate for a given type + residency
    public static function getRate(string $paymentType, string $residency): float
    {
        return static::where('payment_type', $paymentType)
            ->where('residency', $residency)
            ->where('is_active', true)
            ->value('rate') ?? 5.00; // fallback to 5% if not found
    }
}

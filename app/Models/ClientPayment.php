<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPayment extends Model
{
    protected $fillable = [
        'client_business_id',
        'module_id',
        'amount',
        'currency',
        'payment_method',
        'reference',
        'period_start',
        'period_end',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function clientBusiness()
    {
        return $this->belongsTo(Business::class, 'client_business_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}

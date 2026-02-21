<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Overtime extends Model
{
    use HasFactory, HasStatuses, LogsActivity;

    protected $fillable = [
        'attendance_id',
        'employee_id',
        'business_id',
        'location_id',
        'date',
        'overtime_hours',
        'overtime_type',
        'rate',
        'total_pay',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'overtime_hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'total_pay' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}

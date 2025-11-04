<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'business_id',
        'date',
        'clock_in',
        'clock_out',
        'overtime_hours',
        'is_absent',
        'remarks',
        'logged_by',
        'device_mac',
        'punch_latitude',
        'punch_longitude',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'is_absent' => 'boolean',
        'overtime_hours' => 'float',
        'punch_latitude' => 'float',
        'punch_longitude' => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}

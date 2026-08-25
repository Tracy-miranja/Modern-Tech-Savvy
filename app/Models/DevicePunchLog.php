<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicePunchLog extends Model
{
    protected $fillable = [
        'biometric_device_id',
        'device_pin',
        'employee_id',
        'attendance_id',
        'punched_at',
        'status',
        'message',
        'raw_payload',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricDeviceEnrollment extends Model
{
    protected $fillable = [
        'biometric_device_id',
        'employee_id',
        'device_pin',
    ];

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

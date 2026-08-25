<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BiometricDevice extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'name',
        'vendor',
        'serial_number',
        'webhook_token',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function enrollments()
    {
        return $this->hasMany(BiometricDeviceEnrollment::class);
    }

    public function punchLogs()
    {
        return $this->hasMany(DevicePunchLog::class);
    }

    public static function generateWebhookToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('webhook_token', $token)->exists());

        return $token;
    }

    public function webhookUrl(): string
    {
        return route('device.webhook', ['vendor' => $this->vendor, 'token' => $this->webhook_token]);
    }
}

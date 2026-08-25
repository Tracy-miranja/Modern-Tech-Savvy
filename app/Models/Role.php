<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'display_name', 'guard_name', 'business_id', 'is_custom'];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public const PLATFORM_ROLES = ['super-admin', 'krest-admin'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeBusinessAssignable($query)
    {
        return $query->whereNotIn('name', array_merge(self::PLATFORM_ROLES, ['applicant', 'admin', 'business-admin']));
    }

    public function scopeVisibleTo($query, int $businessId)
    {
        return $query->where(function ($q) use ($businessId) {
            $q->whereNull('business_id')->orWhere('business_id', $businessId);
        });
    }

    public function getDisplayNameAttribute($value): string
    {
        return $value ?: Str::title(str_replace(['-', '_'], ' ', $this->name));
    }

    public static function generateUniqueName(int $businessId, string $displayName): string
    {
        $base = 'custom-' . $businessId . '-' . Str::slug($displayName);
        $name = $base;
        $suffix = 1;

        while (self::where('name', $name)->where('guard_name', 'web')->exists()) {
            $suffix++;
            $name = $base . '-' . $suffix;
        }

        return $name;
    }
}
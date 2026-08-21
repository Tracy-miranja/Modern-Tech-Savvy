<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'business_id'];

    /**
     * Platform-governance roles: never assignable, visible, or selectable
     * from within a business's own scope (Roles Management, the employee
     * create/edit role dropdown, etc.) - only super-admin can grant these,
     * via php artisan make:super-admin or the Amsol Admins page.
     */
    public const PLATFORM_ROLES = ['super-admin', 'amsol-admin'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Roles a business may see/assign to its own employees - excludes
     * platform-governance roles and the legacy 'applicant' role.
     */
    public function scopeBusinessAssignable($query)
    {
        return $query->whereNotIn('name', array_merge(self::PLATFORM_ROLES, ['applicant']));
    }
}

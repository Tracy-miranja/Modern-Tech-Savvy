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

    /**
     * Platform-governance roles: never assignable, visible, or selectable
     * from within a business's own scope (Roles Management, the employee
     * create/edit role dropdown, etc.) - only super-admin can grant these,
     * via php artisan make:super-admin or the Platform Admins page.
     */
    public const PLATFORM_ROLES = ['super-admin', 'krest-admin'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Roles a business may see/assign to its own employees - excludes
     * platform-governance roles, the legacy 'applicant' role, 'admin'
     * (a narrow, non-business CRM-cleanup role - delete-lead/
     * delete-contact-submission only - kept out of business-facing role
     * pickers the same way DashboardController and
     * OrganizationStructureController already exclude it by name, so this
     * scope doesn't disagree with the rest of the app), and 'business-admin'
     * itself - that role is only ever meant to exist once per business (the
     * account that registered/created it - see RegisteredUserController and
     * BusinessController's "Add Business" flow), and is only ever meant to
     * be extended to a second person through the dedicated Account Sharing
     * request/approve flow (ClientController::requestAccess()/
     * grantAccess()), which has its own audit trail and email confirmation
     * that a generic Roles Management grant would bypass entirely.
     */
    public function scopeBusinessAssignable($query)
    {
        return $query->whereNotIn('name', array_merge(self::PLATFORM_ROLES, ['applicant', 'admin', 'business-admin']));
    }

    /**
     * Narrows to roles a SPECIFIC business should ever see: every fixed
     * platform-seeded role (business_id null - shared by every business)
     * plus this business's own custom roles. Without this, one business's
     * custom role would be visible to every other business too, since
     * business_id was a column nothing actually filtered on before this.
     */
    public function scopeVisibleTo($query, int $businessId)
    {
        return $query->where(function ($q) use ($businessId) {
            $q->whereNull('business_id')->orWhere('business_id', $businessId);
        });
    }

    /**
     * What the UI shows - the business's own chosen label for a custom
     * role, or a formatted version of the fixed role's internal name
     * ('head-of-department' -> 'Head Of Department') when none is set.
     */
    public function getDisplayNameAttribute($value): string
    {
        return $value ?: Str::title(str_replace(['-', '_'], ' ', $this->name));
    }

    /**
     * A Spatie-unique internal name for a new custom role, derived from
     * the business's chosen display name - roles.name+guard_name is
     * globally unique across the whole platform (confirmed in the
     * permission-tables migration, not scoped to business_id), so two
     * businesses picking the same display name ("Team Lead") would
     * otherwise collide; looping with a numeric suffix the same way this
     * app's employee-identity fields already guarantee uniqueness.
     */
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
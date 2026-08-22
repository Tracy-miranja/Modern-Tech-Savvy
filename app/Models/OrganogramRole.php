<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A company-defined role (HOD, Supervisor, Line Manager, ED, ...) used by
 * the "main organogram" template to compute a default reporting line for
 * employees who haven't been manually reassigned.
 *
 * Seniority is expressed as an explicit reporting edge
 * (reports_to_role_id) rather than a numeric ladder - this is what lets
 * two roles be peers (neither reports to the other) or non-linear (an ED
 * that outranks, or is a peer of, the MD) instead of assuming every role
 * strictly outranks or is outranked by every other. `level` is kept only
 * as a rough display-order hint for the tree view; it is not
 * authoritative for resolving who reports to whom.
 *
 * spatie_role_name optionally maps this position to a real Spatie
 * permission role - see OrganogramPosition and Employee::syncSpatieRoleFromPositions().
 */
class OrganogramRole extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'level',
        'reports_to_role_id',
        'spatie_role_name',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'organogram_role_id');
    }

    public function positions()
    {
        return $this->hasMany(OrganogramPosition::class, 'organogram_role_id');
    }

    /**
     * The role this role reports to, if any (null = top of the chain,
     * e.g. an MD/CEO with no one above them in this business).
     */
    public function reportsToRole()
    {
        return $this->belongsTo(self::class, 'reports_to_role_id');
    }

    /**
     * Roles that report directly to this one.
     */
    public function directReportRoles()
    {
        return $this->hasMany(self::class, 'reports_to_role_id');
    }

    /**
     * True if setting reports_to_role_id = $roleId on this role would
     * create a cycle (the role reporting to itself, directly or via a
     * chain) - mirrors Employee::wouldCreateCycle() for the same reason.
     */
    public function wouldCreateCycle(int $roleId): bool
    {
        if ($roleId === $this->id) {
            return true;
        }

        $current = self::find($roleId);
        $seen = [];
        while ($current && $current->reports_to_role_id && !in_array($current->id, $seen, true)) {
            if ((int) $current->reports_to_role_id === (int) $this->id) {
                return true;
            }
            $seen[] = $current->id;
            $current = $current->reportsToRole;
        }

        return false;
    }
}

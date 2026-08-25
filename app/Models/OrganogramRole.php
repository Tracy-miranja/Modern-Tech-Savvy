<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function reportsToRole()
    {
        return $this->belongsTo(self::class, 'reports_to_role_id');
    }

    public function directReportRoles()
    {
        return $this->hasMany(self::class, 'reports_to_role_id');
    }

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

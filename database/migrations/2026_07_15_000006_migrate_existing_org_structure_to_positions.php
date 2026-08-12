<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-time data migration: converts the old
     * employees.organogram_role_id + department_id pairing into the new
     * Position model (one position per employee+role, covering that
     * employee's single department), and derives each role's
     * reports_to_role_id from the old level ordering (the next
     * more-senior level, per business) so existing reporting behavior is
     * preserved as an editable default rather than silently lost.
     */
    public function up(): void
    {
        // Derive reports_to_role_id from level, per business.
        $roles = DB::table('organogram_roles')->orderBy('business_id')->orderBy('level')->get();
        $rolesByBusiness = $roles->groupBy('business_id');

        foreach ($rolesByBusiness as $businessRoles) {
            $sorted = $businessRoles->sortBy('level')->values();
            foreach ($sorted as $i => $role) {
                if ($role->reports_to_role_id) {
                    continue; // already set, don't override
                }
                // The next more-senior role (smaller level) is the closest
                // preceding entry in this level-sorted list.
                $senior = null;
                for ($j = $i - 1; $j >= 0; $j--) {
                    if ($sorted[$j]->level < $role->level) {
                        $senior = $sorted[$j];
                        break;
                    }
                }
                if ($senior) {
                    DB::table('organogram_roles')->where('id', $role->id)
                        ->update(['reports_to_role_id' => $senior->id]);
                }
            }
        }

        // Convert employee role+department assignments into positions.
        $employees = DB::table('employees')
            ->whereNotNull('organogram_role_id')
            ->whereNotNull('department_id')
            ->get(['id', 'business_id', 'organogram_role_id', 'department_id']);

        foreach ($employees as $employee) {
            $positionId = DB::table('organogram_positions')->where([
                'organogram_role_id' => $employee->organogram_role_id,
                'employee_id' => $employee->id,
            ])->value('id');

            if (!$positionId) {
                $positionId = DB::table('organogram_positions')->insertGetId([
                    'business_id' => $employee->business_id,
                    'organogram_role_id' => $employee->organogram_role_id,
                    'employee_id' => $employee->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $exists = DB::table('organogram_position_department')->where([
                'organogram_position_id' => $positionId,
                'department_id' => $employee->department_id,
            ])->exists();

            if (!$exists) {
                DB::table('organogram_position_department')->insert([
                    'organogram_position_id' => $positionId,
                    'department_id' => $employee->department_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible data backfill - nothing to undo structurally.
    }
};

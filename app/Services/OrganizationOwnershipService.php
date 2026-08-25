<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OrganogramPosition;
use App\Models\OrganogramRole;
use Illuminate\Support\Collection;

class OrganizationOwnershipService
{

    public function computeDepartmentOwnership(Business $business): array
    {
        $roles = OrganogramRole::where('business_id', $business->id)->get(['id', 'reports_to_role_id']);
        $distances = $this->roleDistancesFromRoot($roles);

        $positions = OrganogramPosition::where('business_id', $business->id)
            ->with(['employee.user:id,name', 'employee:id,user_id,employee_code', 'role:id,name', 'departments:id'])
            ->get();

        $departments = Department::where('business_id', $business->id)->get(['id']);

        $ownership = [];
        foreach ($departments as $department) {
            $covering = $positions->filter(fn (OrganogramPosition $p) => $p->departments->contains('id', $department->id));

            if ($covering->isEmpty()) {
                $ownership[$department->id] = ['executive' => null, 'hod' => null];
                continue;
            }

            $withDistance = $covering->map(fn (OrganogramPosition $p) => [
                'position' => $p,
                'distance' => $distances[$p->organogram_role_id] ?? PHP_INT_MAX,
            ]);

            $executive = $withDistance->sortBy('distance')->first();
            $hod = $withDistance->sortByDesc('distance')->first();

            $ownership[$department->id] = [
                'executive' => $this->positionInfo($executive['position']),
                'hod' => $this->positionInfo($hod['position']),
            ];
        }

        return $ownership;
    }

    public function rootRoles(Business $business): Collection
    {
        $roles = OrganogramRole::where('business_id', $business->id)->get(['id', 'name', 'reports_to_role_id']);
        $roleIds = $roles->pluck('id');

        $roots = $roles->filter(
            fn (OrganogramRole $r) => is_null($r->reports_to_role_id) || !$roleIds->contains($r->reports_to_role_id)
        );

        $positions = OrganogramPosition::whereIn('organogram_role_id', $roots->pluck('id'))
            ->with(['employee.user:id,name', 'employee:id,user_id,employee_code'])
            ->get();

        return $roots->map(fn (OrganogramRole $role) => [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'holders' => $positions->where('organogram_role_id', $role->id)
                ->map(fn (OrganogramPosition $p) => $this->holderName($p))
                ->values(),
        ])->values();
    }

    public function executiveCards(Business $business): array
    {
        $ownership = $this->computeDepartmentOwnership($business);
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        $positions = OrganogramPosition::where('business_id', $business->id)
            ->with(['employee.user:id,name', 'employee:id,user_id,employee_code'])
            ->get();

        $byRole = [];
        foreach ($departments as $department) {
            $executive = $ownership[$department->id]['executive'] ?? null;
            if (!$executive) {
                continue;
            }

            $roleId = $executive['role_id'];
            if (!isset($byRole[$roleId])) {
                $byRole[$roleId] = [
                    'role_id' => $roleId,
                    'role_name' => $executive['role_name'],
                    'holders' => $positions->where('organogram_role_id', $roleId)
                        ->map(fn (OrganogramPosition $p) => $this->holderName($p))
                        ->values(),
                    'departments' => [],
                ];
            }

            $byRole[$roleId]['departments'][] = [
                'id' => $department->id,
                'name' => $department->name,
                'people_count' => Employee::where('business_id', $business->id)->inDepartment($department->id)->count(),
            ];
        }

        return array_values($byRole);
    }

    public function crossFunctionalCount(Business $business): int
    {

        return Employee::where('business_id', $business->id)
            ->whereNotNull('functional_manager_id')
            ->with('functionalManager:id,department_id')
            ->get(['id', 'department_id', 'functional_manager_id'])
            ->filter(fn (Employee $e) => optional($e->functionalManager)->department_id !== $e->department_id)
            ->count();
    }

    private function roleDistancesFromRoot(Collection $roles): array
    {
        $roleIds = $roles->pluck('id');

        $roots = $roles->filter(
            fn (OrganogramRole $r) => is_null($r->reports_to_role_id) || !$roleIds->contains($r->reports_to_role_id)
        )->pluck('id');

        $distance = [];
        foreach ($roots as $rootId) {
            $distance[$rootId] = 0;
        }

        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($roles as $role) {
                if (isset($distance[$role->id])) {
                    continue;
                }
                if ($role->reports_to_role_id !== null && isset($distance[$role->reports_to_role_id])) {
                    $distance[$role->id] = $distance[$role->reports_to_role_id] + 1;
                    $changed = true;
                }
            }
        }

        return $distance;
    }

    private function positionInfo(OrganogramPosition $position): array
    {
        return [
            'position_id' => $position->id,
            'employee_id' => $position->employee_id,
            'role_id' => $position->organogram_role_id,
            'role_name' => $position->role->name ?? 'N/A',
            'name' => $this->holderName($position),
        ];
    }

    private function holderName(OrganogramPosition $position): string
    {
        $name = optional(optional($position->employee)->user)->name ?? 'N/A';
        $code = optional($position->employee)->employee_code;

        return $code ? trim($name . ' (' . $code . ')') : $name;
    }
}

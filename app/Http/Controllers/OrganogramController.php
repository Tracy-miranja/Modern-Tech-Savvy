<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Services\OrganizationOwnershipService;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\DB;

class OrganogramController extends Controller
{
    use HandleTransactions;

    private const NODE_COLUMNS = ['id', 'user_id', 'department_id', 'manager_id', 'manager_override', 'functional_manager_id', 'organogram_role_id', 'employee_code'];
    private const NODE_RELATIONS = [
        'user:id,name', 'department:id,name', 'organogramRole:id,name',
        'employmentDetails.jobCategory',
        'functionalManager.user:id,name', 'functionalManager:id,user_id,department_id',
        'manager.user:id,name',
    ];

    public function index(Business $business)
    {
        return view('organogram.index', ['business' => $business]);
    }

    /**
     * One level of the business's employee tree - lazy-loaded, not the
     * whole company at once (a 1000+-employee business would otherwise
     * mean one huge JSON payload and one huge synchronous DOM build, see
     * GUIDE plan org-structure redesign). No `parent_id` returns the root
     * nodes only (no manager, or an out-of-business/orphaned manager
     * reference); with `parent_id`, returns just that employee's direct
     * reports. Each node carries `has_children`/`direct_reports_count` so
     * the frontend knows whether to show an expand toggle without having
     * fetched the children yet.
     */
    public function fetch(Business $business, Request $request, OrganizationOwnershipService $ownershipService)
    {
        $ownership = $ownershipService->computeDepartmentOwnership($business);
        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;

        if ($parentId === null) {
            $roots = Employee::where('business_id', $business->id)
                ->whereNull('manager_id')
                ->with(self::NODE_RELATIONS)
                ->get(self::NODE_COLUMNS);

            // Orphaned reference: manager_id is set but doesn't resolve to an
            // employee actually in this business - rare, but without this a
            // stale manager_id would silently vanish the employee from the
            // chart instead of surfacing them as a root.
            $orphans = Employee::where('business_id', $business->id)
                ->whereNotNull('manager_id')
                ->whereNotExists(function ($q) use ($business) {
                    $q->select(DB::raw(1))
                        ->from('employees as m')
                        ->whereColumn('m.id', 'employees.manager_id')
                        ->where('m.business_id', $business->id);
                })
                ->with(self::NODE_RELATIONS)
                ->get(self::NODE_COLUMNS);

            $roots = $roots->concat($orphans);

            return RequestResponse::ok('Organogram fetched successfully.', $this->nodesWithChildCounts($business, $roots, $ownership));
        }

        $manager = Employee::where('business_id', $business->id)->find($parentId);
        if (!$manager) {
            return RequestResponse::badRequest('Employee not found in this business.', 404);
        }

        $directReports = Employee::where('business_id', $business->id)
            ->where('manager_id', $parentId)
            ->with(self::NODE_RELATIONS)
            ->get(self::NODE_COLUMNS);

        return RequestResponse::ok('Organogram fetched successfully.', $this->nodesWithChildCounts($business, $directReports, $ownership));
    }

    /**
     * Employees matching $request->q (name) and/or the department/
     * executive-owner filters, each with its ancestor_ids chain (root-
     * first) so the frontend can expand every ancestor along the path and
     * land on the match, without ever loading the whole tree to do it.
     * The executive-owner filter is resolved via OrganizationOwnershipService
     * (a role has no direct FK to employees - it's whichever departments
     * that role is the derived executive owner of).
     */
    public function search(Business $business, Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $departmentId = $request->filled('department_id') ? (int) $request->input('department_id') : null;
        $executiveRoleId = $request->filled('executive_role_id') ? (int) $request->input('executive_role_id') : null;

        if ($q === '' && $departmentId === null && $executiveRoleId === null) {
            return RequestResponse::ok('No query.', []);
        }

        $query = Employee::where('business_id', $business->id)->with('user:id,name');

        if ($q !== '') {
            $query->whereHas('user', fn ($sub) => $sub->where('name', 'like', '%' . $q . '%'));
        }

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        if ($executiveRoleId !== null) {
            $ownership = app(OrganizationOwnershipService::class)->computeDepartmentOwnership($business);
            $ownedDepartmentIds = collect($ownership)
                ->filter(fn ($o) => ($o['executive']['role_id'] ?? null) === $executiveRoleId)
                ->keys();
            $query->whereIn('department_id', $ownedDepartmentIds);
        }

        // A free-text search is meant to be a handful of matches; a bare
        // filter (no text typed) can reasonably reveal more of the tree at
        // once since it's an explicit, deliberate narrowing action.
        $matches = $query->limit($q !== '' ? 10 : 200)->get(['id', 'user_id', 'manager_id']);

        $results = $matches->map(function (Employee $employee) use ($business) {
            $chain = [];
            $seen = [];
            $currentManagerId = $employee->manager_id;

            while ($currentManagerId && !in_array($currentManagerId, $seen, true)) {
                $seen[] = $currentManagerId;
                $chain[] = $currentManagerId;
                $currentManagerId = Employee::where('business_id', $business->id)->where('id', $currentManagerId)->value('manager_id');
            }

            return [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'ancestor_ids' => array_reverse($chain),
            ];
        });

        return RequestResponse::ok('Search results.', $results->values());
    }

    /**
     * One DB query for direct-report counts across every given employee
     * (not N+1) - lets the frontend show an expand toggle for a node
     * without having fetched its children yet. $ownership is
     * OrganizationOwnershipService::computeDepartmentOwnership()'s result,
     * computed once per request (not per node) and passed in - it reflects
     * the whole business's role/position structure, not just these nodes.
     */
    private function nodesWithChildCounts(Business $business, $employees, array $ownership = []): array
    {
        if ($employees->isEmpty()) {
            return [];
        }

        $counts = Employee::where('business_id', $business->id)
            ->whereIn('manager_id', $employees->pluck('id'))
            ->select('manager_id', DB::raw('count(*) as cnt'))
            ->groupBy('manager_id')
            ->pluck('cnt', 'manager_id');

        return $employees->map(function (Employee $employee) use ($counts, $ownership) {
            $count = (int) ($counts[$employee->id] ?? 0);
            $functionalManager = $employee->functionalManager;
            $isCrossFunctional = $functionalManager && (int) $functionalManager->department_id !== (int) $employee->department_id;
            $owners = $ownership[$employee->department_id] ?? ['executive' => null, 'hod' => null];

            return [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'employee_code' => $employee->employee_code,
                'department' => $employee->department->name ?? null,
                'organogram_role' => $employee->organogramRole->name ?? null,
                'job_category' => $this->jobCategoryName($employee),
                'manager_id' => $employee->manager_id,
                'manager_name' => optional(optional($employee->manager)->user)->name,
                'manager_override' => (bool) $employee->manager_override,
                'functional_manager_id' => $employee->functional_manager_id,
                'functional_manager_name' => optional(optional($functionalManager)->user)->name,
                'is_cross_functional' => $isCrossFunctional,
                'hod' => $owners['hod']['name'] ?? null,
                'executive_owner' => $owners['executive']['role_name'] ?? null,
                'has_children' => $count > 0,
                'direct_reports_count' => $count,
            ];
        })->values()->all();
    }

    /**
     * job_category_id isn't a real column on employees at all -
     * Employee::getJobCategoryIdAttribute() is a pure accessor falling
     * back to employmentDetails->job_category_id, so jobCategory() can't
     * be eager-loaded directly (nothing to collect FK values from up
     * front); it lazy-loads through the accessor once employmentDetails
     * is available, which NODE_RELATIONS already eager-loads.
     */
    private function jobCategoryName(Employee $employee): ?string
    {
        return optional($employee->jobCategory)->name
            ?? optional(optional($employee->employmentDetails)->jobCategory)->name;
    }

    /**
     * Flat employee list for the "assign manager" dropdown.
     */
    public function employeeOptions(Business $business)
    {
        $employees = Employee::where('business_id', $business->id)
            ->with('user:id,name')
            ->get(['id', 'user_id', 'employee_code'])
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'name' => trim(($e->user->name ?? 'N/A') . ' (' . $e->employee_code . ')'),
            ]);

        return RequestResponse::ok('Employees fetched successfully.', $employees);
    }

    /**
     * Options for the toolbar's "Executive owner" / "Department" filter
     * dropdowns - executive owners come from OrganizationOwnershipService
     * (derived from the role chain, not a stored list), departments are
     * only the ones actually covered by at least one position.
     */
    public function filterOptions(Business $business, OrganizationOwnershipService $ownership)
    {
        $cards = $ownership->executiveCards($business);

        $executives = collect($cards)->map(fn ($card) => [
            'role_id' => $card['role_id'],
            'role_name' => $card['role_name'],
        ])->values();

        $departments = \App\Models\Department::where('business_id', $business->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return RequestResponse::ok('Filter options fetched.', [
            'executives' => $executives,
            'departments' => $departments,
        ]);
    }

    /**
     * Flat (not lazy) employee list for the Table View - a table
     * inherently needs the whole filtered set at once, unlike the tree's
     * on-demand branch loading. Bounded by the same q/department_id/
     * executive_role_id filters search() uses.
     */
    public function table(Business $business, Request $request, OrganizationOwnershipService $ownershipService)
    {
        $q = trim((string) $request->input('q', ''));
        $departmentId = $request->filled('department_id') ? (int) $request->input('department_id') : null;
        $executiveRoleId = $request->filled('executive_role_id') ? (int) $request->input('executive_role_id') : null;

        $query = Employee::where('business_id', $business->id)
            ->with(self::NODE_RELATIONS);

        if ($q !== '') {
            $query->whereHas('user', fn ($sub) => $sub->where('name', 'like', '%' . $q . '%'));
        }

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        $ownership = $ownershipService->computeDepartmentOwnership($business);

        if ($executiveRoleId !== null) {
            $ownedDepartmentIds = collect($ownership)
                ->filter(fn ($o) => ($o['executive']['role_id'] ?? null) === $executiveRoleId)
                ->keys();
            $query->whereIn('department_id', $ownedDepartmentIds);
        }

        $employees = $query->get(self::NODE_COLUMNS);

        $rows = $employees->map(function (Employee $employee) use ($ownership) {
            $functionalManager = $employee->functionalManager;
            $isCrossFunctional = $functionalManager && (int) $functionalManager->department_id !== (int) $employee->department_id;
            $owners = $ownership[$employee->department_id] ?? ['executive' => null, 'hod' => null];

            return [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'employee_code' => $employee->employee_code,
                'position' => $employee->organogramRole->name ?? null,
                'job_category' => $this->jobCategoryName($employee),
                'department' => $employee->department->name ?? null,
                'line_manager' => optional(optional($employee->manager)->user)->name,
                'hod' => $owners['hod']['name'] ?? null,
                'executive_owner' => $owners['executive']['role_name'] ?? null,
                'functional_manager_id' => $employee->functional_manager_id,
                'functional_manager_name' => optional(optional($functionalManager)->user)->name,
                'is_cross_functional' => $isCrossFunctional,
            ];
        })->values();

        return RequestResponse::ok('Organogram table fetched.', $rows);
    }

    public function assignManager(Request $request, Business $business)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'manager_id' => 'nullable|integer|exists:employees,id',
            // Optional dotted-line/matrix manager, set in the same request
            // as the line manager since both live on the same "Assign
            // Manager" modal - see functionalManager()'s docblock.
            'functional_manager_id' => 'nullable|integer|exists:employees,id',
        ]);

        return $this->handleTransaction(function () use ($validated, $business) {
            $employee = Employee::where('business_id', $business->id)
                ->where('id', $validated['employee_id'])
                ->first();

            if (!$employee) {
                return RequestResponse::badRequest('Employee not found in this business.', 404);
            }

            $managerId = $validated['manager_id'] ?? null;

            if ($managerId !== null) {
                if ($managerId === $employee->id) {
                    return RequestResponse::badRequest('An employee cannot be their own manager.', 422);
                }

                $manager = Employee::where('business_id', $business->id)->where('id', $managerId)->first();
                if (!$manager) {
                    return RequestResponse::badRequest('Selected manager is not in this business.', 422);
                }

                if ($employee->wouldCreateCycle($managerId)) {
                    return RequestResponse::badRequest('That assignment would create a reporting cycle.', 422);
                }
            }

            $functionalManagerId = $validated['functional_manager_id'] ?? null;

            if ($functionalManagerId !== null) {
                if ($functionalManagerId === $employee->id) {
                    return RequestResponse::badRequest('An employee cannot be their own functional manager.', 422);
                }

                $functionalManager = Employee::where('business_id', $business->id)->where('id', $functionalManagerId)->first();
                if (!$functionalManager) {
                    return RequestResponse::badRequest('Selected functional manager is not in this business.', 422);
                }

                // A flat dotted-line pointer, not a hierarchy edge - no
                // wouldCreateCycle() check needed the way the line-manager
                // chain requires one.
            }

            // A manual reassignment here always wins over the org-structure
            // template - it stays pinned even if the template's default for
            // this employee's department/role later changes, and it never
            // affects anyone else's reporting line.
            $employee->manager_id = $managerId;
            $employee->manager_override = true;
            $employee->functional_manager_id = $functionalManagerId;
            $employee->save();

            return RequestResponse::ok('Manager assignment updated successfully.');
        });
    }

    /**
     * Clears a manual override and re-derives the manager from the
     * org-structure template (department + organogram role).
     */
    public function resetManagerToTemplate(Request $request, Business $business)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
        ]);

        return $this->handleTransaction(function () use ($validated, $business) {
            $employee = Employee::where('business_id', $business->id)
                ->where('id', $validated['employee_id'])
                ->first();

            if (!$employee) {
                return RequestResponse::badRequest('Employee not found in this business.', 404);
            }

            $employee->manager_override = false;
            $employee->manager_id = $employee->computeTemplateManagerId();
            $employee->save();

            return RequestResponse::ok('Reset to the organization structure default.', $employee->fresh());
        });
    }

    /**
     * An employee's own reporting line - used by the leave/portal side so a
     * line manager can see who they manage without needing HR-level access.
     */
    public function myTeam(Business $business)
    {
        $employee = auth()->user()->activeEmployee();

        // No hard 403 here: an account with no Employee record for this
        // business (e.g. a platform admin previewing the portal via
        // Switch Account) just has nothing to show - same graceful-empty
        // pattern every other myaccount page already follows, rather than
        // being the one page that hard-aborts.
        $directReports = ($employee && (int) $employee->business_id === (int) $business->id)
            ? $employee->directReports()->with('user:id,name', 'department:id,name')->get()
            : collect();

        return view('organogram.my-team', [
            'business' => $business,
            'employee' => $employee,
            'directReports' => $directReports,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OrganogramPosition;
use App\Models\OrganogramRole;
use App\Models\Team;
use App\Services\OrganizationOwnershipService;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * The "main organogram" template, configured in Organization Setup:
 * company-defined roles (HOD, Supervisor, ED, ...) with explicit
 * reporting edges (not a numeric ladder, since real orgs have peers and
 * non-linear structures), optionally mapped to a Spatie permission role.
 * A Position is the actual assignment - an employee holding a role,
 * covering one or more departments and/or teams. The employee-level
 * organogram (OrganogramController) uses all of this to compute each
 * employee's default reporting line.
 */
class OrganizationStructureController extends Controller
{
    use HandleTransactions;

    public function index(Business $business)
    {
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('organization-structure.index', ['business' => $business, 'departments' => $departments]);
    }

    /**
     * Stats + executive-ownership diagram for the new Overview tab - see
     * OrganizationOwnershipService's docblock for how "executive owner"
     * and "HOD" are derived (purely from the existing role reporting
     * chain + position coverage, no new "department owner" field).
     */
    public function fetchOverview(Business $business, OrganizationOwnershipService $ownership)
    {
        $stats = [
            'employees' => Employee::where('business_id', $business->id)->count(),
            'departments' => Department::where('business_id', $business->id)->count(),
            'executives' => count($ownership->executiveCards($business)),
            'cross_functional' => $ownership->crossFunctionalCount($business),
        ];

        return RequestResponse::ok('Overview fetched successfully.', [
            'stats' => $stats,
            'roots' => $ownership->rootRoles($business),
            'executives' => $ownership->executiveCards($business),
        ]);
    }

    /* ----------------
       Roles (catalog + reporting edges)
    -----------------*/

    public function fetchRoles(Business $business)
    {
        $roles = OrganogramRole::where('business_id', $business->id)
            ->withCount(['employees', 'positions'])
            ->with('reportsToRole:id,name')
            ->orderBy('level')
            ->get();

        return RequestResponse::ok('Roles fetched successfully.', $roles);
    }

    /**
     * The role hierarchy (reports_to_role_id edges) as a nested tree, each
     * role carrying its own position holders (with department/team
     * coverage) - powers the visual "Organization Tree" tab. Mirrors
     * OrganogramController::fetch()'s same "roots = no parent, or a parent
     * outside this set" pattern, just built from roles instead of
     * employees.
     */
    public function fetchRoleTree(Business $business)
    {
        $roles = OrganogramRole::where('business_id', $business->id)
            ->withCount('employees')
            ->orderBy('level')
            ->get(['id', 'name', 'level', 'reports_to_role_id']);

        $positions = OrganogramPosition::where('business_id', $business->id)
            ->with(['employee.user:id,name', 'departments:id,name', 'teams:id,name'])
            ->get();

        $positionNode = function (OrganogramPosition $position) {
            return [
                'position_id' => $position->id,
                'employee_id' => $position->employee_id,
                'name' => trim(($position->employee->user->name ?? 'N/A') . ' (' . $position->employee->employee_code . ')'),
                'coverage' => $position->departments->pluck('name')
                    ->merge($position->teams->pluck('name'))
                    ->values(),
            ];
        };

        $byParent = $roles->groupBy('reports_to_role_id');

        $node = function (OrganogramRole $role) use (&$build, $positions, $positionNode) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'employees_count' => $role->employees_count,
                'positions' => $positions->where('organogram_role_id', $role->id)->values()->map($positionNode)->values(),
                'children' => $build($role->id),
            ];
        };

        $build = function ($parentId) use (&$node, $byParent) {
            return $byParent->get($parentId, collect())->map($node)->values();
        };

        $roleIds = $roles->pluck('id');
        $roots = $roles->filter(
            fn (OrganogramRole $r) => is_null($r->reports_to_role_id) || !$roleIds->contains($r->reports_to_role_id)
        );

        $tree = $roots->map($node)->values();

        return RequestResponse::ok('Role tree fetched successfully.', $tree);
    }

    public function fetchAvailableSpatieRoles()
    {
        $roles = SpatieRole::whereNotIn('name', ['super-admin', 'krest-admin', 'admin', 'applicant', 'business-admin'])
            ->orderBy('name')
            ->pluck('name');

        return RequestResponse::ok('Spatie roles fetched successfully.', $roles);
    }

    public function storeRole(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1',
            'reports_to_role_id' => 'nullable|integer|exists:organogram_roles,id',
            'spatie_role_name' => 'nullable|string|max:255',
        ]);

        $exists = OrganogramRole::where('business_id', $business->id)
            ->where('name', $validated['name'])
            ->exists();
        if ($exists) {
            return RequestResponse::badRequest('A role with that name already exists.', 422);
        }

        if (!empty($validated['reports_to_role_id'])) {
            $target = OrganogramRole::where('business_id', $business->id)->find($validated['reports_to_role_id']);
            if (!$target) {
                return RequestResponse::badRequest('The selected "reports to" role is not in this business.', 422);
            }
        }

        $role = OrganogramRole::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'level' => $validated['level'],
            'reports_to_role_id' => $validated['reports_to_role_id'] ?? null,
            'spatie_role_name' => $validated['spatie_role_name'] ?? null,
        ]);

        return RequestResponse::created('Role created successfully.', $role);
    }

    public function updateRole(Request $request, Business $business, OrganogramRole $role)
    {
        if ((int) $role->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Role not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1',
            'reports_to_role_id' => 'nullable|integer|exists:organogram_roles,id',
            'spatie_role_name' => 'nullable|string|max:255',
        ]);

        if (!empty($validated['reports_to_role_id'])) {
            if ((int) $validated['reports_to_role_id'] === (int) $role->id) {
                return RequestResponse::badRequest('A role cannot report to itself.', 422);
            }
            $target = OrganogramRole::where('business_id', $business->id)->find($validated['reports_to_role_id']);
            if (!$target) {
                return RequestResponse::badRequest('The selected "reports to" role is not in this business.', 422);
            }
            if ($role->wouldCreateCycle((int) $validated['reports_to_role_id'])) {
                return RequestResponse::badRequest('That would create a reporting cycle between roles.', 422);
            }
        }

        $role->update($validated);

        return RequestResponse::ok('Role updated successfully.', $role->fresh());
    }

    public function destroyRole(Business $business, OrganogramRole $role)
    {
        if ((int) $role->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Role not found for this business.', 404);
        }

        $role->delete();

        return RequestResponse::ok('Role deleted successfully.');
    }

    /* ----------------
       Teams (optional grouping inside a department)
    -----------------*/

    public function fetchTeams(Business $business)
    {
        $teams = Team::where('business_id', $business->id)
            ->withCount('employees')
            ->with('department:id,name')
            ->orderBy('name')
            ->get();

        return RequestResponse::ok('Teams fetched successfully.', $teams);
    }

    public function storeTeam(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
        ]);

        $department = Department::where('business_id', $business->id)->find($validated['department_id']);
        if (!$department) {
            return RequestResponse::badRequest('Department not found in this business.', 404);
        }

        $team = Team::create([
            'business_id' => $business->id,
            'department_id' => $department->id,
            'name' => $validated['name'],
        ]);

        return RequestResponse::created('Team created successfully.', $team);
    }

    public function destroyTeam(Business $business, Team $team)
    {
        if ((int) $team->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Team not found for this business.', 404);
        }

        $team->delete();

        return RequestResponse::ok('Team deleted successfully.');
    }

    /* ----------------
       Positions (the actual assignments)
    -----------------*/

    /**
     * Every department, its teams, and whoever holds a position covering
     * each - powers the visual org tree and the assignment panel.
     */
    public function fetchAssignments(Business $business)
    {
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $teams = Team::where('business_id', $business->id)->get(['id', 'name', 'department_id']);
        $roles = OrganogramRole::where('business_id', $business->id)->orderBy('level')->get(['id', 'name', 'level']);

        $positions = OrganogramPosition::where('business_id', $business->id)
            ->with(['employee.user:id,name', 'role:id,name', 'departments:id', 'teams:id'])
            ->get();

        $positionNode = function (OrganogramPosition $position) {
            return [
                'position_id' => $position->id,
                'employee_id' => $position->employee_id,
                'name' => trim(($position->employee->user->name ?? 'N/A') . ' (' . $position->employee->employee_code . ')'),
                'role_id' => $position->organogram_role_id,
                'role_name' => $position->role->name ?? 'N/A',
            ];
        };

        $tree = $departments->map(function (Department $department) use ($teams, $positions, $positionNode) {
            $departmentTeams = $teams->where('department_id', $department->id)->values();

            return [
                'id' => $department->id,
                'name' => $department->name,
                'positions' => $positions
                    ->filter(fn (OrganogramPosition $p) => $p->departments->contains('id', $department->id))
                    ->values()
                    ->map($positionNode),
                'teams' => $departmentTeams->map(function (Team $team) use ($positions, $positionNode) {
                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'positions' => $positions
                            ->filter(fn (OrganogramPosition $p) => $p->teams->contains('id', $team->id))
                            ->values()
                            ->map($positionNode),
                    ];
                })->values(),
            ];
        });

        return RequestResponse::ok('Assignments fetched successfully.', [
            'tree' => $tree,
            'roles' => $roles,
        ]);
    }

    /**
     * Assigns an employee to a role, covering one or more departments
     * and/or teams. Also sets the employee's own rung (organogram_role_id)
     * to this role if it differs, grants any Spatie permission role
     * mapped to it, and resyncs everyone whose default manager depends on
     * this slot (a newly-filled or reassigned position).
     */
    public function storePosition(Request $request, Business $business)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'organogram_role_id' => 'required|integer|exists:organogram_roles,id',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'integer|exists:departments,id',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'integer|exists:teams,id',
        ]);

        return $this->handleTransaction(function () use ($validated, $business) {
            $employee = Employee::where('business_id', $business->id)->find($validated['employee_id']);
            if (!$employee) {
                return RequestResponse::badRequest('Employee not found in this business.', 404);
            }

            $role = OrganogramRole::where('business_id', $business->id)->find($validated['organogram_role_id']);
            if (!$role) {
                return RequestResponse::badRequest('Role not found in this business.', 404);
            }

            $departmentIds = Department::where('business_id', $business->id)
                ->whereIn('id', $validated['department_ids'])->pluck('id');
            if ($departmentIds->count() !== count($validated['department_ids'])) {
                return RequestResponse::badRequest('One or more departments are not in this business.', 422);
            }

            $teamIds = collect();
            if (!empty($validated['team_ids'])) {
                $teamIds = Team::where('business_id', $business->id)
                    ->whereIn('id', $validated['team_ids'])->pluck('id');
                if ($teamIds->count() !== count($validated['team_ids'])) {
                    return RequestResponse::badRequest('One or more teams are not in this business.', 422);
                }
            }

            $position = OrganogramPosition::firstOrCreate([
                'business_id' => $business->id,
                'organogram_role_id' => $role->id,
                'employee_id' => $employee->id,
            ]);

            $position->departments()->sync($departmentIds);
            $position->teams()->sync($teamIds);

            if ((int) $employee->organogram_role_id !== (int) $role->id) {
                $employee->organogram_role_id = $role->id;
                $employee->save();
            }

            $employee->syncSpatieRoleFromPositions();

            $this->resyncDepartments($business, $departmentIds->all());
            $this->resyncTeams($business, $teamIds->all());

            return RequestResponse::ok('Position assigned successfully.', $position->fresh(['departments', 'teams']));
        });
    }

    /**
     * Removes a position entirely, then resyncs anyone who was reporting
     * through it so they fall back to whoever else (if anyone) now holds
     * that role over their department/team.
     */
    public function destroyPosition(Business $business, OrganogramPosition $position)
    {
        if ((int) $position->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Position not found for this business.', 404);
        }

        return $this->handleTransaction(function () use ($business, $position) {
            $departmentIds = $position->departments()->pluck('departments.id')->all();
            $teamIds = $position->teams()->pluck('teams.id')->all();

            $position->delete();

            $this->resyncDepartments($business, $departmentIds);
            $this->resyncTeams($business, $teamIds);

            return RequestResponse::ok('Position removed successfully.');
        });
    }

    /**
     * Recomputes manager_id for every employee in the business who hasn't
     * been manually reassigned - run after editing roles/reporting edges
     * or position assignments so the tree catches up with the template.
     *
     * Only touches employees who have an organogram_role_id (their own
     * rung on the ladder) - without one, computeTemplateManagerId() has
     * nothing to resolve from and is a no-op anyway. Regular employees get
     * a rung via bulkAssignRole() (department/team-wide), not by editing
     * each one individually.
     */
    public function syncAll(Business $business)
    {
        $employees = Employee::where('business_id', $business->id)
            ->where('manager_override', false)
            ->whereNotNull('organogram_role_id')
            ->get();

        foreach ($employees as $employee) {
            $employee->syncManagerFromTemplate();
        }

        return RequestResponse::ok("Synced {$employees->count()} employee(s) to the organization structure.");
    }

    /**
     * Assigns an organogram role (the employee's own rung, e.g. "Staff")
     * to every employee in a department (optionally narrowed to a team)
     * in one go, then resyncs their manager_id - this is how rank-and-file
     * employees actually get onto the org structure, since nobody sets
     * organogram_role_id on hundreds of people one at a time via the
     * employee form. By default only fills employees with no role yet, so
     * it never clobbers a position holder's own rung or a previous manual
     * choice unless "overwrite_existing" is explicitly set.
     */
    public function bulkAssignRole(Request $request, Business $business)
    {
        $validated = $request->validate([
            'organogram_role_id' => 'required|integer|exists:organogram_roles,id',
            'department_id' => 'required|integer|exists:departments,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'overwrite_existing' => 'nullable|boolean',
        ]);

        $role = OrganogramRole::where('business_id', $business->id)->find($validated['organogram_role_id']);
        if (!$role) {
            return RequestResponse::badRequest('Role not found in this business.', 404);
        }

        $department = Department::where('business_id', $business->id)->find($validated['department_id']);
        if (!$department) {
            return RequestResponse::badRequest('Department not found in this business.', 404);
        }

        $team = null;
        if (!empty($validated['team_id'])) {
            $team = Team::where('business_id', $business->id)
                ->where('department_id', $department->id)
                ->find($validated['team_id']);
            if (!$team) {
                return RequestResponse::badRequest('Team not found in this department.', 404);
            }
        }

        return $this->handleTransaction(function () use ($business, $role, $department, $team, $validated) {
            $query = Employee::where('business_id', $business->id)
                ->inDepartment($department->id);

            if ($team) {
                $query->where('team_id', $team->id);
            }

            if (empty($validated['overwrite_existing'])) {
                $query->whereNull('organogram_role_id');
            }

            $employees = $query->get();

            foreach ($employees as $employee) {
                $employee->organogram_role_id = $role->id;
                $employee->save();
                $employee->syncManagerFromTemplate();
            }

            return RequestResponse::ok(
                $employees->count() . ' employee(s) assigned the "' . $role->name . '" role.'
            );
        });
    }

    private function resyncDepartments(Business $business, array $departmentIds): void
    {
        if (empty($departmentIds)) {
            return;
        }

        foreach ($departmentIds as $departmentId) {
            Employee::where('business_id', $business->id)
                ->inDepartment($departmentId)
                ->where('manager_override', false)
                ->whereNotNull('organogram_role_id')
                ->get()
                ->each(fn (Employee $employee) => $employee->syncManagerFromTemplate());
        }
    }

    private function resyncTeams(Business $business, array $teamIds): void
    {
        if (empty($teamIds)) {
            return;
        }

        Employee::where('business_id', $business->id)
            ->whereIn('team_id', $teamIds)
            ->where('manager_override', false)
            ->whereNotNull('organogram_role_id')
            ->get()
            ->each(fn (Employee $employee) => $employee->syncManagerFromTemplate());
    }

    /* ----------------
       Structure canvas (Figma-style drag/connect blueprint editor - see
       GUIDE plan org-structure redesign). Deliberately scoped to the small
       template graph (departments, roles, job categories - dozens of
       nodes), never the 1000+-employee organogram - see
       OrganogramCanvasNode's migration docblock for the full rationale.
    -----------------*/

    /**
     * Nodes + edges for the canvas. Auto-syncs a node for every
     * department/role/job-category that doesn't have one yet (so newly
     * created departments/roles/job categories always show up without a
     * manual "add to canvas" step), then returns everything with role-role
     * edges materialized from reports_to_role_id merged alongside any
     * custom-drawn edges.
     */
    public function canvasGraph(Business $business)
    {
        $this->syncCanvasNodes($business);

        $nodes = \App\Models\OrganogramCanvasNode::where('business_id', $business->id)->get();

        $departments = Department::where('business_id', $business->id)->get(['id', 'name'])->keyBy('id');
        $roles = OrganogramRole::where('business_id', $business->id)->get(['id', 'name', 'level', 'reports_to_role_id'])->keyBy('id');
        $jobCategories = \App\Models\JobCategory::where('business_id', $business->id)->get(['id', 'name'])->keyBy('id');

        $nodePayload = $nodes->map(function ($node) use ($departments, $roles, $jobCategories) {
            $label = match ($node->node_type) {
                'department' => optional($departments->get($node->ref_id))->name,
                'role' => optional($roles->get($node->ref_id))->name,
                'job_category' => optional($jobCategories->get($node->ref_id))->name,
                default => null,
            };

            return [
                'id' => $node->id,
                'node_type' => $node->node_type,
                'ref_id' => $node->ref_id,
                'label' => $label ?? '(deleted)',
                'level' => $node->node_type === 'role' ? optional($roles->get($node->ref_id))->level : null,
                'pos_x' => $node->pos_x,
                'pos_y' => $node->pos_y,
            ];
        })->filter(fn ($n) => $n['label'] !== '(deleted)')->values();

        $nodesByRoleRefId = $nodes->where('node_type', 'role')->keyBy('ref_id');

        // Role-role edges mirror the real reports_to_role_id - always
        // present on the canvas, not just when someone happened to draw them.
        $roleEdges = $roles->filter(fn ($r) => $r->reports_to_role_id && $nodesByRoleRefId->has($r->id) && $nodesByRoleRefId->has($r->reports_to_role_id))
            ->map(fn ($r) => [
                'id' => 'role-' . $r->id,
                'from_node_id' => $nodesByRoleRefId->get($r->id)->id,
                'to_node_id' => $nodesByRoleRefId->get($r->reports_to_role_id)->id,
                'source' => 'reports_to_role_id',
            ])->values();

        $customEdges = \App\Models\OrganogramCanvasEdge::where('business_id', $business->id)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'from_node_id' => $e->from_node_id,
                'to_node_id' => $e->to_node_id,
                'source' => 'custom',
            ]);

        return RequestResponse::ok('Canvas graph fetched.', [
            'nodes' => $nodePayload,
            'edges' => $roleEdges->concat($customEdges)->values(),
        ]);
    }

    private function syncCanvasNodes(Business $business): void
    {
        $existing = \App\Models\OrganogramCanvasNode::where('business_id', $business->id)
            ->get()
            ->groupBy('node_type')
            ->map(fn ($group) => $group->pluck('ref_id')->flip());

        $toSync = [
            'department' => Department::where('business_id', $business->id)->pluck('id'),
            'role' => OrganogramRole::where('business_id', $business->id)->pluck('id'),
            'job_category' => \App\Models\JobCategory::where('business_id', $business->id)->pluck('id'),
        ];

        foreach ($toSync as $type => $ids) {
            $have = $existing->get($type, collect());
            foreach ($ids as $id) {
                if (!$have->has($id)) {
                    \App\Models\OrganogramCanvasNode::create([
                        'business_id' => $business->id,
                        'node_type' => $type,
                        'ref_id' => $id,
                    ]);
                }
            }
        }
    }

    public function updateCanvasNodePosition(Request $request, Business $business, \App\Models\OrganogramCanvasNode $node)
    {
        if ((int) $node->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Node not found for this business.', 404);
        }

        $validated = $request->validate([
            'pos_x' => 'required|integer',
            'pos_y' => 'required|integer',
        ]);

        $node->update($validated);

        return RequestResponse::ok('Position saved.');
    }

    public function storeCanvasEdge(Request $request, Business $business)
    {
        $validated = $request->validate([
            'from_node_id' => 'required|integer|exists:organogram_canvas_nodes,id',
            'to_node_id' => 'required|integer|different:from_node_id|exists:organogram_canvas_nodes,id',
        ]);

        return $this->handleTransaction(function () use ($validated, $business) {
            $from = \App\Models\OrganogramCanvasNode::where('business_id', $business->id)->find($validated['from_node_id']);
            $to = \App\Models\OrganogramCanvasNode::where('business_id', $business->id)->find($validated['to_node_id']);

            if (!$from || !$to) {
                return RequestResponse::badRequest('Both nodes must belong to this business.', 404);
            }

            // Role -> role edges ARE the real reporting line, not just a
            // picture of one - drawing this connection sets
            // reports_to_role_id (from = child, to = the role it reports
            // to), same direction the tree view already renders top-down.
            if ($from->node_type === 'role' && $to->node_type === 'role') {
                $childRole = OrganogramRole::where('business_id', $business->id)->find($from->ref_id);
                $parentRole = OrganogramRole::where('business_id', $business->id)->find($to->ref_id);

                if (!$childRole || !$parentRole) {
                    return RequestResponse::badRequest('Role not found for this business.', 404);
                }
                if ($childRole->wouldCreateCycle($parentRole->id)) {
                    return RequestResponse::badRequest('That connection would create a reporting cycle.');
                }

                $childRole->update(['reports_to_role_id' => $parentRole->id]);

                return RequestResponse::ok('Connection saved.', ['source' => 'reports_to_role_id']);
            }

            $edge = \App\Models\OrganogramCanvasEdge::firstOrCreate([
                'business_id' => $business->id,
                'from_node_id' => $from->id,
                'to_node_id' => $to->id,
            ]);

            return RequestResponse::created('Connection saved.', ['id' => $edge->id, 'source' => 'custom']);
        });
    }

    public function destroyCanvasEdge(Request $request, Business $business, string $edgeId)
    {
        // Role-role edges are addressed as "role-{roleId}" (see canvasGraph()) -
        // removing one clears reports_to_role_id, same field a real drawn
        // connection sets, rather than deleting a row that doesn't exist.
        if (str_starts_with($edgeId, 'role-')) {
            $roleId = (int) substr($edgeId, 5);
            $role = OrganogramRole::where('business_id', $business->id)->find($roleId);
            if (!$role) {
                return RequestResponse::badRequest('Role not found for this business.', 404);
            }
            $role->update(['reports_to_role_id' => null]);

            return RequestResponse::ok('Connection removed.');
        }

        $edge = \App\Models\OrganogramCanvasEdge::where('business_id', $business->id)->find((int) $edgeId);
        if (!$edge) {
            return RequestResponse::badRequest('Connection not found.', 404);
        }
        $edge->delete();

        return RequestResponse::ok('Connection removed.');
    }
}

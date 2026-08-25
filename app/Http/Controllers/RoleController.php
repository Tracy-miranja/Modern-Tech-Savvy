<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Business;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    use HandleTransactions;

    public function index()
    {
        return view('roles.index', ['page' => 'Roles Management']);
    }

    public function fetch(Request $request)
    {
        $businessSlug = session('active_business_slug');
        if (!$businessSlug) {
            return RequestResponse::badRequest('No active business selected.');
        }
        $business = Business::findBySlug($businessSlug);

        $query = Role::with('permissions')
            ->businessAssignable()
            ->visibleTo($business->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('filter')) {
            $filter = $request->input('filter');
            $query->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%$filter%")
                    ->orWhere('display_name', 'like', "%$filter%");
            });
        }

        $roles = $query->get();
        $rolesTable = view('roles._table', compact('roles', 'businessSlug'))->render();
        return RequestResponse::ok('Ok', $rolesTable);
    }

    public function modulesForMatrix()
    {
        $businessSlug = session('active_business_slug');
        if (!$businessSlug) {
            return RequestResponse::badRequest('No active business selected.');
        }
        $business = Business::findBySlug($businessSlug);

        $modules = collect(\Database\Seeders\ModuleActionPermissionSeeder::MODULES)->map(fn ($slug) => [
            'slug' => $slug,
            'label' => \Illuminate\Support\Str::title(str_replace('-', ' ', $slug)),
            'active' => $business->hasModule(\Database\Seeders\ModuleActionPermissionSeeder::MODULE_SUBSCRIPTION_GATE[$slug] ?? $slug),
        ])->values();

        return RequestResponse::ok('Modules fetched.', [
            'modules' => $modules,
            'actions' => \Database\Seeders\ModuleActionPermissionSeeder::ACTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $businessSlug = session('active_business_slug');
        if (!$businessSlug) {
            return RequestResponse::badRequest('No active business selected.');
        }
        $business = Business::findBySlug($businessSlug);

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $exists = Role::where('business_id', $business->id)
            ->where('is_custom', true)
            ->where('display_name', $validated['display_name'])
            ->exists();
        if ($exists) {
            return RequestResponse::badRequest('A custom role with that name already exists.');
        }

        return $this->handleTransaction(function () use ($validated, $business) {
            $role = Role::create([
                'name' => Role::generateUniqueName($business->id, $validated['display_name']),
                'display_name' => $validated['display_name'],
                'guard_name' => 'web',
                'business_id' => $business->id,
                'is_custom' => true,
            ]);

            $role->syncPermissions($this->validPermissionsFor($business, $validated['permissions'] ?? []));

            return RequestResponse::created('Custom role created successfully.', $role->fresh('permissions'));
        });
    }

    public function edit(Request $request)
    {
        $role = $this->findEditableCustomRole($request);
        if ($role instanceof \App\Http\RequestResponse) {
            return $role;
        }

        return RequestResponse::ok('Role fetched.', [
            'id' => $role->id,
            'display_name' => $role->display_name,
            'permissions' => $role->permissions->pluck('name')->values(),
        ]);
    }

    public function update(Request $request)
    {
        $role = $this->findEditableCustomRole($request);
        if ($role instanceof \App\Http\RequestResponse) {
            return $role;
        }
        $business = Business::findBySlug(session('active_business_slug'));

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $exists = Role::where('business_id', $business->id)
            ->where('is_custom', true)
            ->where('display_name', $validated['display_name'])
            ->where('id', '!=', $role->id)
            ->exists();
        if ($exists) {
            return RequestResponse::badRequest('A custom role with that name already exists.');
        }

        return $this->handleTransaction(function () use ($validated, $business, $role) {
            $role->update(['display_name' => $validated['display_name']]);
            $role->syncPermissions($this->validPermissionsFor($business, $validated['permissions'] ?? []));

            return RequestResponse::ok('Custom role updated successfully.', $role->fresh('permissions'));
        });
    }

    public function destroy(Request $request)
    {
        $role = $this->findEditableCustomRole($request);
        if ($role instanceof \App\Http\RequestResponse) {
            return $role;
        }

        $role->delete();

        return RequestResponse::ok('Custom role deleted successfully.');
    }

    private function findEditableCustomRole(Request $request)
    {
        $businessSlug = session('active_business_slug');
        if (!$businessSlug) {
            return RequestResponse::badRequest('No active business selected.');
        }
        $business = Business::findBySlug($businessSlug);

        $validated = $request->validate(['role_id' => 'required|integer|exists:roles,id']);
        $role = Role::with('permissions')->find($validated['role_id']);

        if (!$role || !$role->is_custom || (int) $role->business_id !== (int) $business->id) {
            return RequestResponse::forbidden('Only a custom role you created can be edited or deleted.');
        }

        return $role;
    }

    private function validPermissionsFor(Business $business, array $permissionNames): array
    {
        $gateMap = \Database\Seeders\ModuleActionPermissionSeeder::MODULE_SUBSCRIPTION_GATE;
        $activeModules = collect(\Database\Seeders\ModuleActionPermissionSeeder::MODULES)
            ->filter(fn ($slug) => $business->hasModule($gateMap[$slug] ?? $slug))
            ->values();

        $allowedPermissions = $activeModules->flatMap(
            fn ($module) => collect(\Database\Seeders\ModuleActionPermissionSeeder::ACTIONS)
                ->map(fn ($action) => "module.{$module}.{$action}")
        )->values()->all();

        return collect($permissionNames)
            ->filter(fn ($name) => in_array($name, $allowedPermissions, true))
            ->values()
            ->all();
    }

    public function show($business, $role)
    {
        $roleName = urldecode($role);
        Log::info('Show role - Business slug from URL: ' . $business);
        Log::info('Show role - Role name from URL (decoded): ' . $roleName);

        $role = Role::with('permissions')
            ->where('name', $roleName)
            ->businessAssignable()
            ->firstOrFail();

        $businessSlug = session('active_business_slug') ?? $business;
        Log::info('Show role - Business Slug from session: ' . $businessSlug);
        $businessModel = $businessSlug ? Business::findBySlug($businessSlug) : null;

        if (!$businessModel) {
            Log::error('Show role - No active business selected.');
            return RequestResponse::badRequest('No active business selected.');
        }

        $users = User::whereHas('employee', function ($query) use ($businessModel) {
            $query->where('business_id', $businessModel->id);
        })->get();

        $roleUsers = User::whereHas('employee', function ($query) use ($businessModel) {
            $query->where('business_id', $businessModel->id);
        })->whereHas('roles', function ($query) use ($role) {
            $query->where('id', $role->id);
        })->with('employee.departments')->get();

        $departments = Department::where('business_id', $businessModel->id)
            ->orderBy('name', 'asc')
            ->get();

        return view('roles.show', compact('role', 'users', 'businessModel', 'businessSlug', 'roleUsers', 'departments'));
    }

    public function assign(Request $request)
    {
        $validatedData = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'user_id' => 'required|exists:users,id',
            'departments' => 'nullable|array',
            'departments.*' => 'nullable|exists:departments,id',
            'remove' => 'nullable|boolean',
        ]);

        $role = Role::find($validatedData['role_id']);
        if (!$role || in_array($role->name, Role::PLATFORM_ROLES, true) || in_array($role->name, ['applicant', 'business-admin'], true)) {
            return RequestResponse::forbidden('That role cannot be assigned from within a business.');
        }

        return $this->handleTransaction(function () use ($validatedData, $request, $role) {
            $user = User::findOrFail($validatedData['user_id']);

            $businessSlug = session('active_business_slug');
            $business = Business::findBySlug($businessSlug);
            if (!$business) {
                return RequestResponse::badRequest('No active business selected.');
            }

            $employee = Employee::where('user_id', $user->id)
                ->where('business_id', $business->id)
                ->first();

            if (!$employee) {
                return RequestResponse::badRequest('User is not an employee of this business.');
            }

            if ($request->input('remove', false)) {
                $user->removeRole($role);

                if ($role->name === 'chief-of-staff') {
                    $employee->departments()->detach();
                }
                return RequestResponse::ok('Role removed successfully.', ['role' => $role, 'user' => $user]);
            }

            if (!$user->hasRole($role->name)) {
                $user->assignRole($role);
            }

            if ($role->name === 'chief-of-staff') {
                $departments = $validatedData['departments'] ?? [];

                $departments = array_filter($departments);

                if (!empty($departments)) {
                    $employee->departments()->sync($departments);
                    Log::info('Departments assigned to chief-of-staff', [
                        'employee_id' => $employee->id,
                        'departments' => $departments
                    ]);
                }
            }

            return RequestResponse::ok('Role assigned successfully.', ['role' => $role, 'user' => $user]);
        });
    }

    public function updateDepartments(Request $request, $business)
{
    try {

        $businessModel = Business::findBySlug($business);

        if (!$businessModel) {
            return response()->json(['success' => false, 'message' => 'Business not found.'], 404);
        }

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'departments' => 'required|array',
            'departments.*' => 'exists:departments,id',
        ]);

        $user = User::findOrFail($validatedData['user_id']);
        $role = Role::findOrFail($validatedData['role_id']);

        if (!$user->hasRole($role)) {
            return response()->json(['success' => false, 'message' => 'User does not have this role.'], 403);
        }

        $employee = Employee::where('user_id', $user->id)
            ->where('business_id', $businessModel->id)
            ->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'User is not an employee of this business.'], 400);
        }

        $employee->departments()->sync($validatedData['departments']);

        Log::info('Departments updated for employee', [
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'departments' => $validatedData['departments']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Departments updated successfully.',
            'data' => ['employee_id' => $employee->id]
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error updating departments', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error updating departments: ' . $e->getMessage()
        ], 500);
    }
}
}

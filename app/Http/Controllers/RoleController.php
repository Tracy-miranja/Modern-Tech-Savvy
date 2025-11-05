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
        $query = Role::with('permissions')
            ->where('name', '!=', 'applicant')
            ->orderBy('created_at', 'desc');

        if ($request->has('filter')) {
            $filter = $request->input('filter');
            $query->where('name', 'like', "%$filter%");
        }

        $roles = $query->get();
        $businessSlug = session('active_business_slug');
        if (!$businessSlug) {
            return RequestResponse::badRequest('No active business selected.');
        }
        $rolesTable = view('roles._table', compact('roles', 'businessSlug'))->render();
        return RequestResponse::ok('Ok', $rolesTable);
    }

    public function show($business, $role)
    {
        $roleName = urldecode($role);
        Log::info('Show role - Business slug from URL: ' . $business);
        Log::info('Show role - Role name from URL (decoded): ' . $roleName);

        // Load the role with permissions
        $role = Role::with('permissions')
            ->where('name', $roleName)
            ->where('name', '!=', 'applicant')
            ->firstOrFail();

        $businessSlug = session('active_business_slug') ?? $business;
        Log::info('Show role - Business Slug from session: ' . $businessSlug);
        $businessModel = $businessSlug ? Business::findBySlug($businessSlug) : null;

        if (!$businessModel) {
            Log::error('Show role - No active business selected.');
            return RequestResponse::badRequest('No active business selected.');
        }

        // Load users who can be assigned the role (employees of the current business)
        $users = User::whereHas('employee', function ($query) use ($businessModel) {
            $query->where('business_id', $businessModel->id);
        })->get();

        // Load users who already have this role and are employees of the current business
        $roleUsers = User::whereHas('employee', function ($query) use ($businessModel) {
            $query->where('business_id', $businessModel->id);
        })->whereHas('roles', function ($query) use ($role) {
            $query->where('id', $role->id);
        })->with('employee.departments')->get();

        // Load departments for the current business
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

        return $this->handleTransaction(function () use ($validatedData, $request) {
            $role = Role::where('name', '!=', 'applicant')
                ->findOrFail($validatedData['role_id']);
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
                // Also remove departments if chief-of-staff
                if ($role->name === 'chief-of-staff') {
                    $employee->departments()->detach();
                }
                return RequestResponse::ok('Role removed successfully.', ['role' => $role, 'user' => $user]);
            }

            if (!$user->hasRole($role->name)) {
                $user->assignRole($role);
            }

            // Assign departments for chief-of-staff role
            if ($role->name === 'chief-of-staff') {
                $departments = $validatedData['departments'] ?? [];
                // Filter out null/empty values
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

    public function updateDepartments(Request $request, Business $business)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_id' => 'required|exists:roles,id',
                'departments' => 'required|array',
                'departments.*' => 'exists:departments,id',
            ]);

            $user = User::findOrFail($validatedData['user_id']);
            $role = Role::findOrFail($validatedData['role_id']);

            // Verify user has this role
            if (!$user->hasRole($role)) {
                return response()->json(['success' => false, 'message' => 'User does not have this role.'], 403);
            }

            // Get the employee for this business
            $employee = Employee::where('user_id', $user->id)
                ->where('business_id', $business->id)
                ->first();

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'User is not an employee of this business.'], 400);
            }

            // Update departments
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

        } catch (\Exception $e) {
            Log::error('Error updating departments', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating departments: ' . $e->getMessage()
            ], 500);
        }
    }
}

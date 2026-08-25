<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Relief;
use App\Models\EmployeeRelief;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;

class EmployeeReliefsController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = 'Employee Reliefs';
        $description = 'Assign reliefs to employees for payroll processing.';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $employees = Employee::with('user')
            ->where('business_id', $business->id)
            ->get();
        $reliefs = Relief::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();
        $employeeReliefs = EmployeeRelief::with(['employee.user', 'relief'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get();

        return view('employee-reliefs.index', compact('page', 'description', 'employees', 'reliefs', 'employeeReliefs'));
    }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }
            $employees = Employee::where('business_id', $business->id)->get();
            $employeeReliefs = EmployeeRelief::with(['employee.user', 'relief'])
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get();

            $employeeReliefsTable = view('employee-reliefs._table', compact('employeeReliefs'))->render();
            return RequestResponse::ok('Employee reliefs fetched successfully.', [
                'html' => $employeeReliefsTable,
                'count' => $employeeReliefs->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch employee reliefs:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to fetch employee reliefs.', [
                'errors' => [$e->getMessage()]
            ]);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'relief_id' => 'required|exists:reliefs,id',
            'amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $existing = EmployeeRelief::where('employee_id', $validatedData['employee_id'])
                ->where('relief_id', $validatedData['relief_id'])
                ->first();

            if ($existing) {
                return RequestResponse::badRequest('This relief is already assigned to the employee.');
            }

            $employeeRelief = EmployeeRelief::create([
                'employee_id' => $validatedData['employee_id'],
                'relief_id' => $validatedData['relief_id'],
                'amount' => $validatedData['amount'] ?? null,
                'is_active' => $validatedData['is_active'] ?? true,
                'start_date' => $validatedData['start_date'] ?? null,
                'end_date' => $validatedData['end_date'] ?? null,
            ]);

            return RequestResponse::created('Relief assigned to employee successfully.', $employeeRelief->id);
        });
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'employee_relief_id' => 'nullable|exists:employee_reliefs,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $employees = Employee::with('user')
            ->where('business_id', $business->id)
            ->get();
        $reliefs = Relief::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();
        $employeeRelief = null;

        if (!empty($validatedData['employee_relief_id'])) {
            $employeeRelief = EmployeeRelief::where('id', $validatedData['employee_relief_id'])
                ->whereIn('employee_id', $employees->pluck('id'))
                ->firstOrFail();
        }

        $form = view('employee-reliefs._form', compact('employeeRelief', 'employees', 'reliefs'))->render();
        return RequestResponse::ok('Employee relief form loaded successfully.', $form);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'employee_relief_id' => 'required|exists:employee_reliefs,id',
            'employee_id' => 'required|exists:employees,id',
            'relief_id' => 'required|exists:reliefs,id',
            'amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $employeeRelief = EmployeeRelief::where('id', $id)
                ->whereIn('employee_id', Employee::where('business_id', $business->id)->pluck('id'))
                ->firstOrFail();

            if ($employeeRelief->id != $validatedData['employee_relief_id']) {
                return RequestResponse::badRequest('Employee relief ID mismatch.');
            }

            $existing = EmployeeRelief::where('employee_id', $validatedData['employee_id'])
                ->where('relief_id', $validatedData['relief_id'])
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return RequestResponse::badRequest('This relief is already assigned to the employee.');
            }

            $employeeRelief->update([
                'employee_id' => $validatedData['employee_id'],
                'relief_id' => $validatedData['relief_id'],
                'amount' => $validatedData['amount'] ?? null,
                'is_active' => $validatedData['is_active'] ?? true,
                'start_date' => $validatedData['start_date'] ?? null,
                'end_date' => $validatedData['end_date'] ?? null,
            ]);

            return RequestResponse::ok('Employee relief updated successfully.');
        });
    }

    public function destroy(Request $request, $id)
    {
        $validatedData = $request->validate([
            'employee_relief_id' => 'required|exists:employee_reliefs,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $employeeRelief = EmployeeRelief::where('id', $id)
                ->whereIn('employee_id', Employee::where('business_id', $business->id)->pluck('id'))
                ->firstOrFail();

            if ($employeeRelief->id != $validatedData['employee_relief_id']) {
                return RequestResponse::badRequest('Employee relief ID mismatch.');
            }

            $employeeRelief->delete();

            return RequestResponse::ok('Employee relief deleted successfully.');
        });
    }
}
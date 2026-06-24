<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Warning;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployeeWarningIssued;
use App\Mail\EmployeeWarningResolved;

class WarningController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = 'Employee Warnings';
        $description = 'Manage employee warnings for disciplinary purposes. Issue, review, or resolve warnings as needed.';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $employees = $business->employees;
        $locations = $business->locations;
        $warnings = Warning::where('business_id', $business->id)
            ->with('employee.user', 'issuedBy')
            ->orderBy('issue_date', 'desc')
            ->get();

        return view('employees.warning.index', compact('page', 'description', 'employees', 'locations', 'warnings'));
    }

    // public function fetch(Request $request)
    // {
    //     try {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             return RequestResponse::badRequest('Business not found.');
    //         }
    //         $warnings = Warning::where('business_id', $business->id)
    //             ->with('employee.user', 'issuedBy')
    //             ->orderBy('issue_date', 'desc')
    //             ->get();

    //         $warningsTable = view('employees.warning._cards', compact('warnings'))->render();
    //         return RequestResponse::ok('Warnings fetched successfully.', [
    //             'html' => $warningsTable,
    //             'count' => $warnings->count()
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to fetch warnings:', ['error' => $e->getMessage()]);
    //         return RequestResponse::badRequest('Failed to fetch warnings.', [
    //             'errors' => [$e->getMessage()]
    //         ]);
    //     }
    // }
    public function fetch(Request $request)
{
    try {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $warnings = Warning::where('business_id', $business->id)
            ->with('employee.user', 'issuedBy')
            ->orderBy('issue_date', 'desc')
            ->get();

        $html = view('employees.warning._rows', compact('warnings'))->render();

        return RequestResponse::ok('Warnings fetched successfully.', [
            'html'  => $html,
            'count' => $warnings->count(),
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to fetch warnings:', ['error' => $e->getMessage()]);
        return RequestResponse::badRequest('Failed to fetch warnings.');
    }
}

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'issue_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $warningCount = Warning::where('employee_id', $validatedData['employee_id'])
                ->where('business_id', $business->id)
                ->count();

            if ($warningCount >= 2) {
                return RequestResponse::badRequest('Validation failed.', [
                    'errors' => ['employee_id' => 'This employee has already received the maximum of 2 warnings.']
                ]);
            }

            $warning = Warning::create([
                'employee_id' => $validatedData['employee_id'],
                'business_id' => $business->id,
                'issue_date' => $validatedData['issue_date'],
                'reason' => $validatedData['reason'],
                'description' => $validatedData['description'] ?? null,
                'status' => 'active',
                'issued_by' => auth()->user()->id,
            ]);

            // Send email notification to the employee
            $employee = $warning->employee;
            if ($employee && $employee->user && $employee->user->email) {
                Mail::to($employee->user->email)->send(new EmployeeWarningIssued($warning));
            }

            return RequestResponse::created('Warning issued successfully.', $warning->id);
        });
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'warning_id' => 'nullable|exists:warnings,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $employees = $business->employees;
        $warning = null;

        if (!empty($validatedData['warning_id'])) {
            $warning = Warning::where('business_id', $business->id)
                ->where('id', $validatedData['warning_id'])
                ->firstOrFail();
        }

        $form = view('employees.warning._form', compact('warning', 'employees'))->render();
        return RequestResponse::ok('Warning form loaded successfully.', $form);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'warning_id' => 'required|exists:warnings,id',
            'employee_id' => 'required|exists:employees,id',
            'issue_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,resolved',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $warning = Warning::where('business_id', $business->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($warning->id != $validatedData['warning_id']) {
                return RequestResponse::badRequest('Warning ID mismatch.');
            }

            if ($warning->employee_id != $validatedData['employee_id']) {
                $warningCount = Warning::where('employee_id', $validatedData['employee_id'])
                    ->where('business_id', $business->id)
                    ->count();

                if ($warningCount >= 2) {
                    return RequestResponse::badRequest('Validation failed.', [
                        'errors' => ['employee_id' => 'This employee has already received the maximum of 2 warnings.']
                    ]);
                }
            }

            $previousStatus = $warning->status; // Store previous status
            $warning->update([
                'employee_id' => $validatedData['employee_id'],
                'issue_date' => $validatedData['issue_date'],
                'reason' => $validatedData['reason'],
                'description' => $validatedData['description'] ?? null,
                'status' => $validatedData['status'],
                'issued_by' => auth()->user()->id,
            ]);

            // Check if status changed to 'resolved' and send email
            if ($validatedData['status'] === 'resolved' && $previousStatus !== 'resolved') {
                $employee = $warning->employee;
                if ($employee && $employee->user && $employee->user->email) {
                    Mail::to($employee->user->email)->send(new EmployeeWarningResolved($warning));
                }
            }

            return RequestResponse::ok('Warning updated successfully.');
        });
    }

    public function destroy(Request $request, $id)
    {
        $validatedData = $request->validate([
            'warning_id' => 'required|exists:warnings,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $warning = Warning::where('business_id', $business->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($warning->id != $validatedData['warning_id']) {
                return RequestResponse::badRequest('Warning ID mismatch.');
            }

            $warning->delete();

            return RequestResponse::ok('Warning deleted successfully.');
        });
    }
}

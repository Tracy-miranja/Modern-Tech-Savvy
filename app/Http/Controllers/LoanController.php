<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Models\Loan;
use App\Models\Business;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;

class LoanController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $loans = Loan::with('employee')
            ->join('employees', 'loans.employee_id', '=', 'employees.id')
            ->where('employees.business_id', $business->id)
            ->select('loans.*')
            ->get();
        $loan_table = view('loans._table', compact('loans'))->render();
        return RequestResponse::ok('Ok', $loan_table);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:1',
            'interest_rate' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $request) {
            $business = Business::findBySlug(session('active_business_slug'));

            $employee = Employee::where('id', $validatedData['employee_id'])
                ->where('business_id', $business->id)
                ->firstOrFail();

            $loan = Loan::create([
                'employee_id' => $validatedData['employee_id'],
                'amount' => $validatedData['amount'],
                'interest_rate' => $validatedData['interest_rate'] ?? null,
                'term_months' => $validatedData['term_months'] ?? null,
                'start_date' => $validatedData['start_date'],
                'notes' => $validatedData['notes'] ?? null,
            ]);

            $loan->setStatus(Status::ACTIVE);
            return RequestResponse::created('Loan recorded successfully.');
        });
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'loan' => 'required|exists:loans,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        $loan = Loan::with('employee')
            ->join('employees', 'loans.employee_id', '=', 'employees.id')
            ->where('employees.business_id', $business->id)
            ->where('loans.id', $validatedData['loan'])
            ->select('loans.*')
            ->firstOrFail();
        $employees = $business->employees;
        $loan_form = view('loans._form', compact('loan', 'employees'))->render();
        return RequestResponse::ok('Ok', $loan_form);
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:1',
            'interest_rate' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));

            $loan = Loan::join('employees', 'loans.employee_id', '=', 'employees.id')
                ->where('employees.business_id', $business->id)
                ->where('loans.id', $validatedData['loan_id'])
                ->select('loans.*')
                ->firstOrFail();
            $employee = Employee::where('id', $validatedData['employee_id'])
                ->where('business_id', $business->id)
                ->firstOrFail();

            $loan->update([
                'employee_id' => $validatedData['employee_id'],
                'amount' => $validatedData['amount'],
                'interest_rate' => $validatedData['interest_rate'] ?? null,
                'term_months' => $validatedData['term_months'] ?? null,
                'start_date' => $validatedData['start_date'],
                'notes' => $validatedData['notes'] ?? null,
            ]);

            return RequestResponse::ok('Loan updated successfully.');
        });
    }

    public function destroy(Request $request)
    {
        $validatedData = $request->validate([
            'loan_id' => 'required|exists:loans,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));

            $loan = Loan::join('employees', 'loans.employee_id', '=', 'employees.id')
                ->where('employees.business_id', $business->id)
                ->where('loans.id', $validatedData['loan_id'])
                ->select('loans.*')
                ->firstOrFail();

            $loan->delete();
            return RequestResponse::ok('Loan deleted successfully.');
        });
    }

    public function bulkDelete(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:loans,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));

            $deleted = Loan::join('employees', 'loans.employee_id', '=', 'employees.id')
                ->where('employees.business_id', $business->id)
                ->whereIn('loans.id', $validatedData['ids'])
                ->delete();

            if ($deleted) {
                return RequestResponse::ok('Selected loans deleted successfully.');
            }

            return RequestResponse::badRequest('Failed to delete loans.', 500);
        });
    }
}

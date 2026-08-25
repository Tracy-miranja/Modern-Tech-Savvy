<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Models\Business;
use App\Models\Deduction;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Models\EmployeeDeduction;
use App\Traits\HandleTransactions;

class EmployeeDeductionController extends Controller
{
    use HandleTransactions;
    public function create(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $deductions = Deduction::all();
        $employees = $business->employees;
        $employee_deductions = $business->employeeDeductions;

        $deductionsCreate = view('payroll._create_employee_deductions', compact('deductions', 'employees', 'employee_deductions'))->render();

        return RequestResponse::ok('Ok', $deductionsCreate);
    }

    public function store(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'deduction_id' => 'required|exists:deductions,id',
                'amount' => 'required|numeric|min:0',
            ]);

            $deduction = EmployeeDeduction::create($request->all());

            $deduction->setStatus((Status::ACTIVE));

            return RequestResponse::ok('Deduction added successfully');
        });
    }

    public function update(Request $request, $id)
    {
        return $this->handleTransaction(function () use ($request, $id) {
            $request->validate([
                'amount' => 'required|numeric|min:0',
                'date' => 'required|date',
            ]);

            $deduction = EmployeeDeduction::findOrFail($id);
            $deduction->update([
                'amount' => $request->amount,
                'date' => $request->date,
            ]);

            return RequestResponse::ok('Deduction updated successfully');
        });
    }

    public function destroy(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $deduction = EmployeeDeduction::findOrFail($request->deduction);
            $deduction->delete();

            return RequestResponse::ok('Deduction removed successfully');
        });
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Location;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    private function employees($request) {
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employeesOnly;
        if ($request->has('location')) {
            $location = Location::findBySlug($request->location);
            $employees = $location->employees;
        }

        return $employees;
    }
    public function iTaxEmployeeDetails(Request $request)
    {
        $employees = $this->employees($request);

        $itaxDetails = [];
        foreach ($employees as $key => $employee) {
            $payslip = $employee->payrolls()->latest();
            $itaxDetails['name'] = $employee->user->name;
            $itaxDetails['salutation'] = $employee->salutations()->first();
            $itaxDetails['employment_type'] = $employee->employmentDetails->employment_term;
            $itaxDetails['basic_salary'] = $employee->paymentDetails->basic_salary;
            $itaxDetails['paye'] = $payslip->paye;
            $itaxDetails['nssf'] = $payslip->nssf;
            $itaxDetails['nhif'] = $payslip->nhif;
            $itaxDetails['housing-levy'] = $payslip->housing_levy;
        }

        $csv = "";

    }

    public function kraItaxReturnSchedule(Request $request) {
        $employees = $this->employees($request);

    }

}

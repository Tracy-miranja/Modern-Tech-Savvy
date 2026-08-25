<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Business;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Services\TrendService;
use App\Models\EmployeePayroll;
use App\Services\PayrollTrendService;

class TrendController extends Controller
{
    protected $trendService;
    protected $payrollTrendService;

    public function __construct(PayrollTrendService $payrollTrendService, TrendService $trendService)
    {
        $this->payrollTrendService = $payrollTrendService;
        $this->trendService = $trendService;
    }

    public function payroll(Request $request) {
        return $this->getTrends(EmployeePayroll::class, $request, 'created_at', ['net_pay', 'gross_pay']);
    }

    public function attendance(Request $request)
    {
        return $this->getTrends(Attendance::class, $request, 'date');
    }

    public function leave(Request $request)
    {
        return $this->getTrends(LeaveRequest::class, $request, 'created_at');
    }

    public function loans(Request $request)
    {
        return $this->getTrends(Loan::class, $request, 'start_date', 'amount');
    }

    protected function getTrends($modelClass, Request $request, $dateColumn, $sumColumn = null)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $businessId = $business->id;
        $year = $request->input('year', now()->year);

        $trends = $this->trendService->getTrends(new $modelClass(), $businessId, $year, $dateColumn, $sumColumn);

        return RequestResponse::ok('Trends retrieved successfully.', $trends);
    }

}

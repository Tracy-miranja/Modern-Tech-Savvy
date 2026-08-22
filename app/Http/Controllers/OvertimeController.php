<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Business;
use App\Models\Overtime;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;

class OvertimeController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Active business not found.');
        }

        $dateInput    = $request->input('date');
        $status       = $request->input('status'); // pending, approved, rejected
        $overtimeType = $request->input('overtime_type'); // regular, holiday, manual

        $query = Overtime::where('business_id', $business->id)
            ->with(['employee.user', 'approvedBy']);

        if ($dateInput) {
            try {
                $date = Carbon::parse($dateInput, 'Africa/Nairobi')->format('Y-m-d');
                $query->whereDate('date', $date);
            } catch (\Exception $e) {
                return RequestResponse::badRequest('Invalid date format provided.');
            }
        }

        // Use Spatie status filter (since you use HasStatuses)
        if ($status) {
            $query->where('status', $status);
        }

        if ($overtimeType) {
            $query->where('overtime_type', $overtimeType);
        }

        $overtimes = $query->orderBy('date', 'desc')->get();

        // pass $business so the partial can embed business slug
        $overtimeTable = view('attendances._overtime_table', [
            'overtimes' => $overtimes,
            'business'  => $business,
        ])->render();

        return RequestResponse::ok('Overtime records fetched successfully.', $overtimeTable);
    }

    private function resolveOvertimeRate(Employee $employee, Business $business, string $type): float
    {
        $type = strtolower(trim($type));

        if ($type === 'holiday') {
            return (float) ($employee->overtime_rate_holiday ?? $business->overtime_rate_holiday ?? 2.0);
        }

        // regular OR manual → default to regular config
        return (float) ($employee->overtime_rate_regular ?? $business->overtime_rate ?? 1.5);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'date'           => 'required|date',
            'overtime_hours' => 'required|numeric|min:0.01',
            'overtime_type'  => 'required|in:regular,holiday,manual',
            'description'    => 'required|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Active business not found in session.');
            }

            $employee = Employee::findOrFail($validated['employee_id']);

            if (!$employee->is_overtime_eligible) {
                return RequestResponse::badRequest('This employee is not eligible for overtime.');
            }

            $hours = round((float) $validated['overtime_hours'], 2);

            // Rate multiplier only (NO salary/hourly-rate)
            $rate = $this->resolveOvertimeRate($employee, $business, $validated['overtime_type']);
            if ($rate <= 0) {
                return RequestResponse::badRequest('Overtime rate is not configured correctly.');
            }

            // Business rule: total_pay = hours * rate
            $totalPay = round($hours * $rate, 2);

            $overtime = Overtime::create([
                'employee_id'    => $employee->id,
                'business_id'    => $business->id,
                'location_id'    => $employee->location_id,
                'date'           => $validated['date'],
                'overtime_hours' => $hours,
                'overtime_type'  => $validated['overtime_type'],
                'rate'           => $rate,
                'total_pay'      => $totalPay,
                'description'    => $validated['description'],
                'status'         => 'pending',
                'approved_by'    => null,
                'approved_at'    => null,
                'rejection_reason' => null,
            ]);

            
            if (method_exists($overtime, 'setStatus')) {
                $overtime->setStatus('pending');
            }

            return RequestResponse::created('Overtime record created successfully. Pending approval.');
        });
    }

    public function edit(Request $request)
    {
        $validated = $request->validate([
            'overtime' => 'required|exists:overtimes,id',
        ]);

        $overtime = Overtime::with(['employee.user'])->findOrFail($validated['overtime']);

        $employees = Employee::where('business_id', $overtime->business_id)
            ->where('is_overtime_eligible', true)
            ->with('user')
            ->get();

        $overtimeForm = view('attendances._overtime_form', compact('overtime', 'employees'))->render();
        return RequestResponse::ok('Overtime found', $overtimeForm);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'overtime_id'    => 'required|exists:overtimes,id',
            'employee_id'    => 'required|exists:employees,id',
            'date'           => 'required|date',
            'overtime_hours' => 'required|numeric|min:0.01',
            'overtime_type'  => 'required|in:regular,holiday,manual',
            'description'    => 'required|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $overtime = Overtime::findOrFail($validated['overtime_id']);

            if ($overtime->status === 'approved') {
                return RequestResponse::badRequest('Cannot edit approved overtime. Please reject it first.');
            }

            $business = $overtime->business;
            if (!$business) {
                return RequestResponse::badRequest('Business not found for this overtime record.');
            }

            $employee = Employee::findOrFail($validated['employee_id']);

            if (!$employee->is_overtime_eligible) {
                return RequestResponse::badRequest('This employee is not eligible for overtime.');
            }

            $hours = round((float) $validated['overtime_hours'], 2);

            // Rate multiplier only
            $rate = $this->resolveOvertimeRate($employee, $business, $validated['overtime_type']);
            if ($rate <= 0) {
                return RequestResponse::badRequest('Overtime rate is not configured correctly.');
            }

            // Business rule
            $totalPay = round($hours * $rate, 2);

            $overtime->update([
                'employee_id'    => $employee->id,
                'date'           => $validated['date'],
                'overtime_hours' => $hours,
                'overtime_type'  => $validated['overtime_type'],
                'rate'           => $rate,
                'total_pay'      => $totalPay,
                'description'    => $validated['description'],
                'status'         => 'pending',
                'approved_by'    => null,
                'approved_at'    => null,
                'rejection_reason' => null,
            ]);

            if (method_exists($overtime, 'setStatus')) {
                $overtime->setStatus('pending');
            }

            return RequestResponse::ok('Overtime record updated successfully.');
        });
    }


    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'overtime' => 'required|exists:overtimes,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $overtime = Overtime::findOrFail($validated['overtime']);

            if ($overtime->status === 'approved') {
                return RequestResponse::badRequest('Cannot delete approved overtime. Please reject first.');
            }

            $overtime->delete();
            return RequestResponse::ok('Overtime record deleted successfully.');
        });
    }

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'overtime_id' => 'required|exists:overtimes,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $overtime = Overtime::findOrFail($validated['overtime_id']);

            if ($overtime->status === 'approved') {
                return RequestResponse::badRequest('This overtime is already approved.');
            }

            $overtime->update([
                'status'           => 'approved',
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
                'rejection_reason' => null,
            ]);

            $overtime->setStatus('approved');

            return RequestResponse::ok('Overtime approved successfully.');
        });
    }

    public function reject(Request $request)
    {
        $validated = $request->validate([
            'overtime_id'       => 'required|exists:overtimes,id',
            'rejection_reason'  => 'required|string|max:500',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $overtime = Overtime::findOrFail($validated['overtime_id']);

            $overtime->update([
                'status'           => 'rejected',
                'approved_by'      => null,
                'approved_at'      => null,
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            $overtime->setStatus('rejected');

            return RequestResponse::ok('Overtime rejected.');
        });
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'overtime_ids'   => 'required|array|min:1',
            'overtime_ids.*' => 'exists:overtimes,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $userId = auth()->id();

            $overtimes = Overtime::whereIn('id', $validated['overtime_ids'])->get();

            $count = 0;
            foreach ($overtimes as $ot) {
                if ($ot->status === 'pending') {
                    $ot->update([
                        'status'           => 'approved',
                        'approved_by'      => $userId,
                        'approved_at'      => now(),
                        'rejection_reason' => null,
                    ]);
                    $ot->setStatus('approved');
                    $count++;
                }
            }

            return RequestResponse::ok("{$count} overtime records approved successfully.");
        });
    }
}

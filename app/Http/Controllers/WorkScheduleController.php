<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Business;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;

class WorkScheduleController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $employeeId = $request->input('employee_id');
        $shiftId    = $request->input('shift_id'); // <-- NEW

        $query = WorkSchedule::where('business_id', $business->id)
            ->with(['employee.user', 'shift']);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        //  handle shift filter (normal shift id OR "no_shift")
        if ($shiftId) {
            if ($shiftId === 'no_shift') {
                $query->whereNull('shift_id');
            } else {
                $query->where('shift_id', $shiftId);
            }
        }

        $schedules = $query->orderBy('effective_from', 'desc')->get();

        $scheduleTable = view('attendances.work_schedules_table', compact('schedules'))->render();
        return RequestResponse::ok('Work schedules fetched successfully.', $scheduleTable);
    }

    public function createForm(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $employees = Employee::where('business_id', $business->id)
            ->with('user')
            ->get();

        $shifts = \App\Models\Shift::where('business_id', $business->id)->get();

        $form = view('attendances.work_schedules_form', [
            'employees' => $employees,
            'shifts' => $shifts,
            'schedule' => null,
        ])->render();

        return RequestResponse::ok('Form loaded', $form);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'working_days' => 'required|array|min:1',
            'working_days.*' => 'integer|between:0,6',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($request, $validated) {
            $business = Business::findBySlug(session('active_business_slug'));

            // Deactivate overlapping schedules
            WorkSchedule::where('employee_id', $validated['employee_id'])
                ->where('business_id', $business->id)
                ->where(function ($query) use ($validated) {
                    $query->where('effective_from', '<=', $validated['effective_to'] ?? '9999-12-31')
                        ->where(function ($q) use ($validated) {
                            $q->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $validated['effective_from']);
                        });
                })
                ->update(['is_active' => false]);

            WorkSchedule::create([
                'employee_id' => $validated['employee_id'],
                'shift_id' => $validated['shift_id'],
                'business_id' => $business->id,
                'working_days' => $validated['working_days'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'is_active' => true,
                'notes' => $validated['notes'],
            ]);

            return RequestResponse::created('Work schedule created successfully.');
        });
    }

    public function edit(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|exists:work_schedules,id',
        ]);

        $schedule = WorkSchedule::with(['employee.user', 'shift'])->findOrFail($validated['schedule']);
        $employees = Employee::where('business_id', $schedule->business_id)->with('user')->get();
        $shifts = \App\Models\Shift::where('business_id', $schedule->business_id)->get();

        $scheduleForm = view('attendances.work_schedules_form', compact('schedule', 'employees', 'shifts'))->render();
        return RequestResponse::ok('Schedule found', $scheduleForm);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:work_schedules,id',
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'working_days' => 'required|array|min:1',
            'working_days.*' => 'integer|between:0,6',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $schedule = WorkSchedule::findOrFail($validated['schedule_id']);

            $schedule->update([
                'employee_id' => $validated['employee_id'],
                'shift_id' => $validated['shift_id'],
                'working_days' => $validated['working_days'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'notes' => $validated['notes'],
            ]);

            return RequestResponse::ok('Work schedule updated successfully.');
        });
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|exists:work_schedules,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $schedule = WorkSchedule::findOrFail($validated['schedule']);
            $schedule->delete();

            return RequestResponse::ok('Work schedule deleted successfully.');
        });
    }

    /**
     * Get schedule info for a specific employee and date
     */
    public function getScheduleInfo(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'nullable|date',
        ]);

        $date = $validated['date'] ? Carbon::parse($validated['date']) : now();
        $schedule = WorkSchedule::getActiveSchedule($validated['employee_id'], $date);

        if (!$schedule) {
            return RequestResponse::badRequest('No active work schedule found for this employee.');
        }

        return RequestResponse::ok('Schedule found', [
            'schedule' => $schedule,
            'shift' => $schedule->shift,
            'is_working_day' => $schedule->isWorkingDay($date),
            'working_days' => $schedule->working_days_names,
        ]);        
    }

    public function activate(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|exists:work_schedules,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));
            $schedule = WorkSchedule::where('business_id', $business->id)
                ->findOrFail($validated['schedule']);

            // deactivate all schedules for this employee in this business
            WorkSchedule::where('business_id', $business->id)
                ->where('employee_id', $schedule->employee_id)
                ->update(['is_active' => false]);

            // activate selected
            $schedule->is_active = true;
            $schedule->save();

            return RequestResponse::ok('Work schedule activated successfully.');
        });
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',

            'shift_ids' => 'required|array|min:1',
            'shift_ids.*' => 'exists:shifts,id',

            'working_days' => 'required|array|min:1',
            'working_days.*' => 'integer|between:0,6',

            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));

            foreach ($validated['employee_ids'] as $employeeId) {
                foreach ($validated['shift_ids'] as $shiftId) {

                    // Deactivate overlapping schedules for that employee (same business)
                    WorkSchedule::where('employee_id', $employeeId)
                        ->where('business_id', $business->id)
                        ->where(function ($query) use ($validated) {
                            $query->where('effective_from', '<=', $validated['effective_to'] ?? '9999-12-31')
                                ->where(function ($q) use ($validated) {
                                    $q->whereNull('effective_to')
                                        ->orWhere('effective_to', '>=', $validated['effective_from']);
                                });
                        })
                        ->update(['is_active' => false]);

                    WorkSchedule::create([
                        'employee_id' => $employeeId,
                        'shift_id' => $shiftId,
                        'business_id' => $business->id,
                        'working_days' => $validated['working_days'],
                        'effective_from' => $validated['effective_from'],
                        'effective_to' => $validated['effective_to'] ?? null,
                        'is_active' => true,
                        'notes' => $validated['notes'] ?? null,
                    ]);
                }
            }

            return RequestResponse::created('Bulk work schedules assigned successfully.');
        });
    }


}
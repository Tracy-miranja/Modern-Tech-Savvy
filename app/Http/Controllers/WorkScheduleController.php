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
        $shiftId    = $request->input('shift_id'); // 

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

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));

            // shift may be null (No Shift schedule)
            $newShift = null;
            $newStart = null;
            $newEnd   = null;

            if (!empty($validated['shift_id'])) {
                $newShift = \App\Models\Shift::findOrFail($validated['shift_id']);

                $newStart = Carbon::parse($newShift->start_time);
                $newEnd   = Carbon::parse($newShift->end_time);

                // overnight
                if ($newEnd->lessThanOrEqualTo($newStart)) {
                    $newEnd->addDay();
                }
            }

            $newFrom = Carbon::parse($validated['effective_from'])->startOfDay();
            $newTo   = !empty($validated['effective_to'])
                ? Carbon::parse($validated['effective_to'])->endOfDay()
                : Carbon::parse('9999-12-31')->endOfDay();

            $newDays = collect($validated['working_days'])->map(fn($d) => (int)$d)->unique();

            // Get existing active schedules for employee
            $existingSchedules = WorkSchedule::where('employee_id', $validated['employee_id'])
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->with('shift')
                ->get();

            foreach ($existingSchedules as $s) {

                // 1) Check effective range overlap
                $oldFrom = Carbon::parse($s->effective_from)->startOfDay();
                $oldTo   = $s->effective_to
                    ? Carbon::parse($s->effective_to)->endOfDay()
                    : Carbon::parse('9999-12-31')->endOfDay();

                $dateRangesOverlap = $newFrom->lte($oldTo) && $newTo->gte($oldFrom);
                if (!$dateRangesOverlap) continue;

                // 2) Check working days overlap
                $oldDays = collect($s->working_days ?? [])->map(fn($d) => (int)$d)->unique();
                $daysOverlap = $newDays->intersect($oldDays)->isNotEmpty();
                if (!$daysOverlap) continue;

                // 3) If either schedule has no shift, we don't time-overlap-check
                // (you may choose to block "no shift" + shift on same days, but I’m leaving it permissive)
                if (!$newShift || !$s->shift) continue;

                $existingStart = Carbon::parse($s->shift->start_time);
                $existingEnd   = Carbon::parse($s->shift->end_time);
                if ($existingEnd->lessThanOrEqualTo($existingStart)) {
                    $existingEnd->addDay();
                }

                // 4) Time overlap check (allow touching edges)
                // overlap if: newStart < existingEnd AND newEnd > existingStart
                $overlap = $newStart->lt($existingEnd) && $newEnd->gt($existingStart);

                if ($overlap) {
                    return RequestResponse::badRequest(
                        'Shift time overlaps with an existing active schedule (same effective period + working days).'
                    );
                }
            }

            WorkSchedule::create([
                'employee_id'    => $validated['employee_id'],
                'shift_id'       => $validated['shift_id'] ?? null,
                'business_id'    => $business->id,
                'working_days'   => $validated['working_days'],
                'effective_from' => $validated['effective_from'],
                'effective_to'   => $validated['effective_to'] ?? null,
                'is_active'      => true,
                'notes'          => $validated['notes'] ?? null,
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
                ->with(['shift'])
                ->findOrFail($validated['schedule']);

            // If it has no shift, we allow activation (optional)
            $newShift = $schedule->shift;
            $newStart = null; $newEnd = null;

            if ($newShift) {
                $newStart = Carbon::parse($newShift->start_time);
                $newEnd   = Carbon::parse($newShift->end_time);
                if ($newEnd->lessThanOrEqualTo($newStart)) $newEnd->addDay();
            }

            $newFrom = Carbon::parse($schedule->effective_from)->startOfDay();
            $newTo   = $schedule->effective_to
                ? Carbon::parse($schedule->effective_to)->endOfDay()
                : Carbon::parse('9999-12-31')->endOfDay();

            $newDays = collect($schedule->working_days ?? [])->map(fn($d) => (int)$d)->unique();

            // check other active schedules for overlap
            $others = WorkSchedule::where('business_id', $business->id)
                ->where('employee_id', $schedule->employee_id)
                ->where('is_active', true)
                ->where('id', '!=', $schedule->id)
                ->with('shift')
                ->get();

            foreach ($others as $s) {
                $oldFrom = Carbon::parse($s->effective_from)->startOfDay();
                $oldTo   = $s->effective_to
                    ? Carbon::parse($s->effective_to)->endOfDay()
                    : Carbon::parse('9999-12-31')->endOfDay();

                $dateRangesOverlap = $newFrom->lte($oldTo) && $newTo->gte($oldFrom);
                if (!$dateRangesOverlap) continue;

                $oldDays = collect($s->working_days ?? [])->map(fn($d) => (int)$d)->unique();
                if ($newDays->intersect($oldDays)->isEmpty()) continue;

                if (!$newShift || !$s->shift) continue;

                $existingStart = Carbon::parse($s->shift->start_time);
                $existingEnd   = Carbon::parse($s->shift->end_time);
                if ($existingEnd->lessThanOrEqualTo($existingStart)) $existingEnd->addDay();

                $overlap = $newStart->lt($existingEnd) && $newEnd->gt($existingStart);
                if ($overlap) {
                    return RequestResponse::badRequest('Cannot activate: schedule overlaps an existing active schedule.');
                }
            }

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

            $newFrom = Carbon::parse($validated['effective_from'])->startOfDay();
            $newTo   = !empty($validated['effective_to'])
                ? Carbon::parse($validated['effective_to'])->endOfDay()
                : Carbon::parse('9999-12-31')->endOfDay();

            $newDays = collect($validated['working_days'])->map(fn($d) => (int)$d)->unique();

            // preload shifts
            $shifts = \App\Models\Shift::whereIn('id', $validated['shift_ids'])->get()->keyBy('id');

            foreach ($validated['employee_ids'] as $employeeId) {

                $existingSchedules = WorkSchedule::where('employee_id', $employeeId)
                    ->where('business_id', $business->id)
                    ->where('is_active', true)
                    ->with('shift')
                    ->get();

                foreach ($validated['shift_ids'] as $shiftId) {

                    $shift = $shifts->get($shiftId);
                    if (!$shift) {
                        return RequestResponse::badRequest("Shift not found: {$shiftId}");
                    }

                    $newStart = Carbon::parse($shift->start_time);
                    $newEnd   = Carbon::parse($shift->end_time);
                    if ($newEnd->lessThanOrEqualTo($newStart)) $newEnd->addDay();

                    // validate against existing
                    foreach ($existingSchedules as $s) {
                        $oldFrom = Carbon::parse($s->effective_from)->startOfDay();
                        $oldTo   = $s->effective_to
                            ? Carbon::parse($s->effective_to)->endOfDay()
                            : Carbon::parse('9999-12-31')->endOfDay();

                        $dateRangesOverlap = $newFrom->lte($oldTo) && $newTo->gte($oldFrom);
                        if (!$dateRangesOverlap) continue;

                        $oldDays = collect($s->working_days ?? [])->map(fn($d) => (int)$d)->unique();
                        $daysOverlap = $newDays->intersect($oldDays)->isNotEmpty();
                        if (!$daysOverlap) continue;

                        if (!$s->shift) continue;

                        $existingStart = Carbon::parse($s->shift->start_time);
                        $existingEnd   = Carbon::parse($s->shift->end_time);
                        if ($existingEnd->lessThanOrEqualTo($existingStart)) $existingEnd->addDay();

                        $overlap = $newStart->lt($existingEnd) && $newEnd->gt($existingStart);

                        if ($overlap) {
                            return RequestResponse::badRequest(
                                "Overlap detected for employee {$employeeId}. Shift {$shift->name} conflicts with an existing active schedule."
                            );
                        }
                    }

                    WorkSchedule::create([
                        'employee_id'    => $employeeId,
                        'shift_id'       => $shiftId,
                        'business_id'    => $business->id,
                        'working_days'   => $validated['working_days'],
                        'effective_from' => $validated['effective_from'],
                        'effective_to'   => $validated['effective_to'] ?? null,
                        'is_active'      => true,
                        'notes'          => $validated['notes'] ?? null,
                    ]);

                    // also add the newly created schedule into $existingSchedules so subsequent shifts validate against it
                    $existingSchedules->push(WorkSchedule::latest('id')->with('shift')->first());
                }
            }

            return RequestResponse::created('Bulk work schedules assigned successfully.');
        });
    }

    public function timeline(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::with('user')->findOrFail($validated['employee_id']);

        $schedules = WorkSchedule::where('business_id', $business->id)
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->with('shift')
            ->orderBy('effective_from', 'desc')
            ->get();

        $html = view('attendances._work_schedule_timeline', compact('employee','schedules'))->render();
        return RequestResponse::ok('Ok.', $html);
    }
}
<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveAttendanceSyncService
{
    public function handleLeaveSaved(LeaveRequest $leave): void
    {
        $wasApproved = !is_null($leave->getOriginal('approved_by'))
            && is_null($leave->getOriginal('rejection_reason'))
            && is_null($leave->getOriginal('cancelled_at'));

        $isApproved = $leave->status === 'approved';

        if (!$wasApproved && $isApproved) {
            $this->syncRange($leave, Carbon::parse($leave->start_date), Carbon::parse($leave->end_date));
            return;
        }

        if ($wasApproved && !$isApproved) {
            $this->releaseRange(
                $leave,
                Carbon::parse($leave->getOriginal('start_date')),
                Carbon::parse($leave->getOriginal('end_date'))
            );
            return;
        }

        if ($wasApproved && $isApproved && $leave->wasChanged('end_date')) {
            $oldEnd = Carbon::parse($leave->getOriginal('end_date'))->startOfDay();
            $newEnd = Carbon::parse($leave->end_date)->startOfDay();

            if ($newEnd->lt($oldEnd)) {
                $this->releaseRange($leave, $newEnd->copy()->addDay(), $oldEnd);
            } elseif ($newEnd->gt($oldEnd)) {
                $this->syncRange($leave, $oldEnd->copy()->addDay(), $newEnd);
            }
        }
    }

    protected function syncRange(LeaveRequest $leave, Carbon $start, Carbon $end): void
    {
        if ($start->gt($end)) {
            return;
        }

        $employee = $leave->employee ?: Employee::find($leave->employee_id);
        if (!$employee) {
            return;
        }

        foreach (CarbonPeriod::create($start->toDateString(), $end->toDateString()) as $date) {
            $existing = Attendance::where('employee_id', $leave->employee_id)
                ->where('business_id', $leave->business_id)
                ->whereDate('date', $date->toDateString())
                ->first();

            if ($existing) {

                $existing->is_on_leave = true;
                $existing->leave_request_id = $leave->id;
                if (is_null($existing->clock_in)) {
                    $existing->is_absent = false;
                }
                $existing->save();
                continue;
            }

            $schedule = WorkSchedule::getActiveSchedule($leave->employee_id, $date, $leave->business_id);
            $isWorkingDay = $schedule ? $schedule->isWorkingDay($date) : true;
            $holiday = Holiday::isHoliday($leave->business_id, $date);

            Attendance::create([
                'employee_id' => $leave->employee_id,
                'business_id' => $leave->business_id,
                'date' => $date->toDateString(),
                'is_absent' => false,
                'is_working_day' => $isWorkingDay,
                'is_holiday' => $holiday !== null,
                'is_on_leave' => true,
                'leave_request_id' => $leave->id,
            ]);
        }
    }

    protected function releaseRange(LeaveRequest $leave, Carbon $start, Carbon $end): void
    {
        if ($start->gt($end)) {
            return;
        }

        Attendance::where('leave_request_id', $leave->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->each(function (Attendance $row) {
                if (is_null($row->clock_in)) {

                    $row->delete();
                } else {

                    $row->is_on_leave = false;
                    $row->leave_request_id = null;
                    $row->save();
                }
            });
    }
}

<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricDeviceEnrollment;
use App\Models\DevicePunchLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\DB;

class DeviceAttendanceService
{
    public function processPunch(BiometricDevice $device, ?string $devicePin, ?Carbon $punchedAt, array $rawPayload = []): DevicePunchLog
    {
        $punchedAt = ($punchedAt ?: now('Africa/Nairobi'))->copy()->timezone('Africa/Nairobi');
        $devicePin = trim((string) $devicePin);

        if ($devicePin === '') {
            return $this->log($device, null, null, null, $punchedAt, 'error', 'Punch had no PIN.', $rawPayload);
        }

        $employee = $this->resolveEmployee($device, $devicePin);
        if (!$employee) {
            $device->update(['last_seen_at' => now()]);
            return $this->log($device, $devicePin, null, null, $punchedAt, 'unmatched_employee', "No employee matches device PIN {$devicePin}.", $rawPayload);
        }

        return DB::transaction(function () use ($device, $devicePin, $employee, $punchedAt, $rawPayload) {
            $date = $punchedAt->toDateString();

            $open = Attendance::where('employee_id', $employee->id)
                ->where('business_id', $device->business_id)
                ->where('date', $date)
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->latest('id')
                ->first();

            if ($open) {
                $open->clock_out = $punchedAt->format('H:i:s');
                $open->save();
                $open->calculateTimeMetrics();
                $open->save();
                $open->load(['employee', 'business']);
                $open->createOvertimeRecords();

                $device->update(['last_seen_at' => now()]);
                return $this->log($device, $devicePin, $employee->id, $open->id, $punchedAt, 'processed', 'Clock-out recorded via device.', $rawPayload);
            }

            $schedule = WorkSchedule::getActiveSchedule($employee->id, $punchedAt, $device->business_id);
            $isWorkingDay = $schedule ? $schedule->isWorkingDay($punchedAt) : true;
            $holiday = Holiday::isHoliday($device->business_id, $punchedAt);
            $isHoliday = $holiday !== null;
            if ($isHoliday && $holiday->is_working_day) {
                $isWorkingDay = true;
            }

            $attendance = Attendance::create([
                'employee_id'      => $employee->id,
                'business_id'      => $device->business_id,
                'work_schedule_id' => $schedule?->id,
                'shift_id'         => $schedule?->shift_id,
                'date'             => $date,
                'clock_in'         => $punchedAt->format('H:i:s'),
                'is_working_day'   => $isWorkingDay,
                'is_holiday'       => $isHoliday,
                'logged_by'        => null,
            ]);

            if ($schedule && $schedule->shift) {
                $expectedIn  = $punchedAt->copy()->setTimeFromTimeString($schedule->shift->start_time);
                $expectedOut = $punchedAt->copy()->setTimeFromTimeString($schedule->shift->end_time);
                if ($expectedOut->lte($expectedIn)) {
                    $expectedOut->addDay();
                }
                $attendance->expected_clock_in = $expectedIn;
                $attendance->expected_clock_out = $expectedOut;
                $attendance->save();
            }

            $device->update(['last_seen_at' => now()]);
            return $this->log($device, $devicePin, $employee->id, $attendance->id, $punchedAt, 'processed', 'Clock-in recorded via device.', $rawPayload);
        });
    }

    protected function resolveEmployee(BiometricDevice $device, string $devicePin): ?Employee
    {
        $enrollment = BiometricDeviceEnrollment::where('biometric_device_id', $device->id)
            ->where('device_pin', $devicePin)
            ->first();
        if ($enrollment) {
            return $enrollment->employee;
        }

        return Employee::where('business_id', $device->business_id)
            ->where('employee_code', $devicePin)
            ->first();
    }

    protected function log(BiometricDevice $device, ?string $pin, ?int $employeeId, ?int $attendanceId, Carbon $punchedAt, string $status, string $message, array $rawPayload): DevicePunchLog
    {
        return DevicePunchLog::create([
            'biometric_device_id' => $device->id,
            'device_pin'          => $pin,
            'employee_id'         => $employeeId,
            'attendance_id'       => $attendanceId,
            'punched_at'          => $punchedAt,
            'status'              => $status,
            'message'             => $message,
            'raw_payload'         => $rawPayload ? json_encode($rawPayload) : null,
        ]);
    }
}

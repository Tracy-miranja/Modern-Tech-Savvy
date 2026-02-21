<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\LogsActivity;
use App\Models\WorkSchedule;
use App\Models\Holiday;
use App\Models\Overtime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'business_id',
        'date',
        'clock_in',
        'expected_clock_in',
        'clock_out',
        'expected_clock_out',
        'overtime_hours',
        'overtime_regular',
        'overtime_holiday',
        'regular_hours',
        'late_minutes',
        'early_departure_minutes',
        'is_absent',
        'is_working_day',
        'is_holiday',
        'remarks',
        'logged_by',
        'device_mac',
        'punch_latitude',
        'punch_longitude',
        'work_schedule_id',
        'shift_id',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'expected_clock_in' => 'datetime',
        'expected_clock_out' => 'datetime',
        'is_absent' => 'boolean',
        'is_working_day' => 'boolean',
        'is_holiday' => 'boolean',
        'overtime_hours' => 'float',
        'overtime_regular' => 'float',
        'overtime_holiday' => 'float',
        'regular_hours' => 'float',
        'late_minutes' => 'float',
        'early_departure_minutes' => 'float',
        'punch_latitude' => 'float',
        'punch_longitude' => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }
    /**
     * Calculate all time metrics for this attendance record
     */
    public function calculateTimeMetrics(): void
    {
        if (!$this->date || !$this->clock_in) {
            return;
        }

        $day = Carbon::parse($this->date)->startOfDay();

        // Build full datetimes from stored time strings (you store H:i:s)
        $clockIn = $this->clock_in instanceof Carbon
            ? $this->clock_in
            : $day->copy()->setTimeFromTimeString((string)$this->clock_in);

        $clockOut = null;
        if ($this->clock_out) {
            $clockOut = $this->clock_out instanceof Carbon
                ? $this->clock_out
                : $day->copy()->setTimeFromTimeString((string)$this->clock_out);

            // If clock out is "earlier" than clock in, assume it crossed midnight
            if ($clockOut->lt($clockIn)) {
                $clockOut->addDay();
            }
        }

        // Resolve schedule (for expected times + working day)
        $schedule = WorkSchedule::getActiveSchedule($this->employee_id, $day, $this->business_id);

        $expectedIn = null;
        $expectedOut = null;

        if ($schedule && $schedule->shift) {
            $expectedIn = $day->copy()->setTimeFromTimeString($schedule->shift->start_time);
            $expectedOut = $day->copy()->setTimeFromTimeString($schedule->shift->end_time);

            // Shift crossing midnight (e.g. 22:00 -> 06:00)
            if ($expectedOut->lte($expectedIn)) {
                $expectedOut->addDay();
            }

            $this->expected_clock_in = $expectedIn;
            $this->expected_clock_out = $expectedOut;
        }

        // Determine working day from schedule
        $isWorkingDay = $schedule ? $schedule->isWorkingDay($day) : true;

        // Determine holiday
        $holiday = Holiday::isHoliday($this->business_id, $day);
        $isHoliday = $holiday !== null;
        $holidayIsWorkingDay = $isHoliday ? (bool)$holiday->is_working_day : false;

        // If holiday is marked as "working day", treat it as working day
        if ($isHoliday && $holidayIsWorkingDay) {
            $isWorkingDay = true;
        }

        $this->is_working_day = $isWorkingDay;
        $this->is_holiday = $isHoliday;

        // Lateness (minutes)
        $this->late_minutes = 0;
        if ($expectedIn && $clockIn->gt($expectedIn)) {
            $this->late_minutes = $clockIn->diffInMinutes($expectedIn);
        }

        // Without clock out we can't calculate the rest
        if (!$clockOut) {
            return;
        }

        // Early departure (minutes)
        $this->early_departure_minutes = 0;
        if ($expectedOut && $clockOut->lt($expectedOut)) {
            $this->early_departure_minutes = $expectedOut->diffInMinutes($clockOut);
        }

        // Total worked hours
        $totalMinutes = $clockIn->diffInMinutes($clockOut);
        $totalHours = round($totalMinutes / 60, 2);

        // Expected shift hours
        $expectedShiftHours = 8.0; // default fallback
        if ($expectedIn && $expectedOut) {
            $expectedShiftHours = round($expectedIn->diffInMinutes($expectedOut) / 60, 2);
        }

        // Reset
        $this->regular_hours = 0;
        $this->overtime_regular = 0;
        $this->overtime_holiday = 0;

        /**
         * RULES
         * 1) Non-working day OR holiday (non-working): all hours = holiday OT
         * 2) Normal working day (not holiday): OT beyond shift = regular OT
         * 3) Holiday but marked as working day: OT beyond shift = holiday OT, regular hours capped
         */
        if (!$isWorkingDay || ($isHoliday && !$holidayIsWorkingDay)) {
            // Non-working day OR holiday non-working
            $this->regular_hours = 0;
            $this->overtime_holiday = $totalHours;
        } else {
            // Working day (either normal day OR holiday working day)
            $this->regular_hours = min($totalHours, $expectedShiftHours);
            $extra = max(0, round($totalHours - $expectedShiftHours, 2));

            if ($isHoliday && $holidayIsWorkingDay) {
                // Working holiday: overtime is holiday OT
                $this->overtime_holiday = $extra;
            } else {
                // Normal working day: overtime is regular OT
                $this->overtime_regular = $extra;
            }
        }

        $this->overtime_hours = round($this->overtime_regular + $this->overtime_holiday, 2);
    }

    /**
     * Create overtime records from attendance
     */
    public function createOvertimeRecords(): void
    {
        $employee = $this->employee;
        $business = $this->business;

        if (!$employee || !$business) return;

        if (!$employee->is_overtime_eligible) {
            return;
        }

        // Delete only auto-generated pending overtime for this attendance
        Overtime::where('attendance_id', $this->id)
            ->where('status', 'pending')
            ->where('description', 'like', 'Auto:%')
            ->delete();

        // REGULAR OT
        if ($this->overtime_regular > 0) {
            $rate = (float) ($employee->overtime_rate_regular ?? $business->overtime_rate ?? 1.5);
            $hours = round((float) $this->overtime_regular, 2);

            Overtime::create([
                'attendance_id'   => $this->id,
                'employee_id'     => $this->employee_id,
                'business_id'     => $this->business_id,
                'location_id'     => $employee->location_id,
                'date'            => $this->date,
                'overtime_hours'  => $hours,
                'overtime_type'   => 'regular',
                'rate'            => $rate,
                'total_pay'       => round($hours * $rate, 2), // ✅ BUSINESS RULE
                'description'     => 'Auto: Regular overtime (from attendance)',
                'status'          => 'pending',
                'approved_by'     => null,
                'approved_at'     => null,
                'rejection_reason'=> null,
            ]);
        }

        // HOLIDAY OT
        if ($this->overtime_holiday > 0) {
            $rate = (float) ($employee->overtime_rate_holiday ?? $business->overtime_rate_holiday ?? 2.0);
            $hours = round((float) $this->overtime_holiday, 2);

            Overtime::create([
                'attendance_id'   => $this->id,
                'employee_id'     => $this->employee_id,
                'business_id'     => $this->business_id,
                'location_id'     => $employee->location_id,
                'date'            => $this->date,
                'overtime_hours'  => $hours,
                'overtime_type'   => 'holiday',
                'rate'            => $rate,
                'total_pay'       => round($hours * $rate, 2), // ✅ BUSINESS RULE
                'description'     => 'Auto: Holiday/Non-working overtime (from attendance)',
                'status'          => 'pending',
                'approved_by'     => null,
                'approved_at'     => null,
                'rejection_reason'=> null,
            ]);
        }
    }

    /**
     * Calculate hourly rate for employee
     */
    private function calculateHourlyRate(Employee $employee): float
    {
        // Assuming monthly salary / (working days per month * hours per day)
        // Adjust based on your payroll structure
        $monthlySalary = $employee->salary ?? 0;
        $workingDaysPerMonth = 22; // average
        $hoursPerDay = 8;
        
        return $monthlySalary > 0 
            ? round($monthlySalary / ($workingDaysPerMonth * $hoursPerDay), 2)
            : 0;
    }

    /**
     * Get summary statistics for an employee in a date range
     */
    public static function getEmployeeSummary(int $employeeId, Carbon $start, Carbon $end): array
    {
        $attendances = self::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->get();

        return [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('is_absent', false)->count(),
            'absent_days' => $attendances->where('is_absent', true)->count(),
            'total_regular_hours' => round($attendances->sum('regular_hours'), 2),
            'total_overtime_regular' => round($attendances->sum('overtime_regular'), 2),
            'total_overtime_holiday' => round($attendances->sum('overtime_holiday'), 2),
            'total_overtime_hours' => round($attendances->sum('overtime_hours'), 2),
            'total_late_minutes' => round($attendances->sum('late_minutes'), 2),
            'total_early_departures' => round($attendances->sum('early_departure_minutes'), 2),
            'working_days_attended' => $attendances->where('is_working_day', true)->where('is_absent', false)->count(),
            'non_working_days_attended' => $attendances->where('is_working_day', false)->where('is_absent', false)->count(),
        ];
    }
}
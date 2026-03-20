<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HourlyPayCalculator
{
    /**
     * Calculate gross pay for hourly employee
     *
     * @param Employee $employee
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function calculateHourlyGrossPay(Employee $employee, string $startDate, string $endDate): array
    {
        $paymentDetail = $employee->paymentDetails;

        Log::info('Starting hourly pay calculation', [
            'employee_id' => $employee->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'has_payment_details' => !is_null($paymentDetail),
            'payment_type' => $paymentDetail->payment_type ?? 'null',
        ]);

        if (!$paymentDetail || $paymentDetail->payment_type !== 'hourly') {
            Log::warning('Employee is not hourly or has no payment details', [
                'employee_id' => $employee->id,
                'has_payment_details' => !is_null($paymentDetail),
                'payment_type' => $paymentDetail->payment_type ?? 'null',
            ]);

            return [
                'hours_worked' => 0,
                'overtime_hours' => 0,
                'hourly_rate' => 0,
                'overtime_rate' => 0,
                'regular_pay' => 0,
                'overtime_pay' => 0,
                'gross_pay' => 0,
            ];
        }

        $hourlyRate = floatval($paymentDetail->hourly_rate);

        Log::info('Payment details loaded', [
            'employee_id' => $employee->id,
            'hourly_rate' => $hourlyRate,
        ]);

        // Get all attendance records
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_absent', false)
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->get();

        Log::info('Attendance records fetched', [
            'employee_id' => $employee->id,
            'count' => $attendances->count(),
            'attendance_ids' => $attendances->pluck('id')->toArray(),
        ]);

        $regularHours = 0;
        $overtimeHours = 0;

        foreach ($attendances as $attendance) {
            try {
                // FIX: Parse date and time separately, then combine
                // Convert date to Carbon if it's a string, or use as-is if already Carbon
                $dateObj = $attendance->date instanceof Carbon
                    ? $attendance->date
                    : Carbon::parse($attendance->date);

                // Extract just the date portion (Y-m-d)
                $dateString = $dateObj->format('Y-m-d');

                // Parse clock_in and clock_out times
                $clockInTime = is_string($attendance->clock_in)
                    ? $attendance->clock_in
                    : Carbon::parse($attendance->clock_in)->format('H:i:s');

                $clockOutTime = is_string($attendance->clock_out)
                    ? $attendance->clock_out
                    : Carbon::parse($attendance->clock_out)->format('H:i:s');

                // Now combine date with time strings
                $clockIn = Carbon::parse($dateString . ' ' . $clockInTime);
                $clockOut = Carbon::parse($dateString . ' ' . $clockOutTime);

                // Calculate hours worked
                $hoursWorked = abs($clockOut->diffInMinutes($clockIn)) / 60;
                $regularHours += $hoursWorked;

                // Add overtime
                $overtimeHours += floatval($attendance->overtime_hours ?? 0);

                Log::debug('Attendance record processed', [
                    'attendance_id' => $attendance->id,
                    'date' => $dateString,
                    'clock_in' => $clockInTime,
                    'clock_out' => $clockOutTime,
                    'hours_worked' => $hoursWorked,
                    'overtime_hours' => $attendance->overtime_hours,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to process attendance record', [
                    'attendance_id' => $attendance->id,
                    'date_raw' => $attendance->date,
                    'clock_in_raw' => $attendance->clock_in,
                    'clock_out_raw' => $attendance->clock_out,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
        }

        // Calculate pay (overtime typically paid at 1.5x rate)
        $overtimeRate = $hourlyRate * 1.5;
        $regularPay = $regularHours * $hourlyRate;
        $overtimePay = $overtimeHours * $overtimeRate;
        $grossPay = $regularPay + $overtimePay;

        Log::info('Hourly pay calculation complete', [
            'employee_id' => $employee->id,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'regular_pay' => $regularPay,
            'overtime_pay' => $overtimePay,
            'gross_pay' => $grossPay,
        ]);

        return [
            'hours_worked' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'regular_pay' => round($regularPay, 2),
            'overtime_pay' => round($overtimePay, 2),
            'gross_pay' => round($grossPay, 2),
        ];
    }

    /**
     * Get attendance summary for an employee
     *
     * @param Employee $employee
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getAttendanceSummary(Employee $employee, string $startDate, string $endDate): array
    {
        $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        $presentDays = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_absent', false)
            ->whereNotNull('clock_in')
            ->count();

        $absentDays = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_absent', true)
            ->count();

        Log::info('Attendance summary calculated', [
            'employee_id' => $employee->id,
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
        ]);

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
        ];
    }
}

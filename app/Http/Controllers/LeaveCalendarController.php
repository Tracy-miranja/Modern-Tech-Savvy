<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Models\MandatoryLeavePeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveCalendarController extends Controller
{

    public function employeeCalendar(Business $business)
    {
        return view('leave.calendar', [
            'business' => $business,
            'eventsUrl' => route('myaccount.leave.calendar.events', ['business' => $business->slug]),
            'title' => 'My Leave Calendar',
            'nonWorkingDays' => $business->non_working_days ?? [],
        ]);
    }

    public function businessCalendar(Business $business)
    {
        $locations = Location::where('business_id', $business->id)->orderBy('name')->get(['id', 'name', 'country']);

        return view('leave.calendar', [
            'business' => $business,
            'eventsUrl' => route('business.leave.calendar.events', ['business' => $business->slug]),
            'teamHeadcountUrl' => route('business.leave.calendar.team-headcount', ['business' => $business->slug]),
            'capacityWarningPercent' => $business->leave_planner_capacity_warning_percent ?? 30,
            'title' => 'Business Leave Calendar',
            'showDepartmentFilter' => true,
            'showPlanner' => true,
            'locations' => $locations,
            'nonWorkingDays' => $business->non_working_days ?? [],
        ]);
    }

    public function employeeEvents(Request $request, Business $business)
    {
        $employee = auth()->user()->activeEmployee();

        if (!$employee || (int) $employee->business_id !== (int) $business->id) {
            return response()->json([]);
        }

        $employeeIds = $employee->directReports()->pluck('id')->push($employee->id);

        return response()->json($this->buildEvents(
            $request,
            $business,
            $employeeIds,
            $employee->location_id,
            $employee->department_id
        ));
    }

    public function businessEvents(Request $request, Business $business)
    {
        $employeeIds = null;

        if ($request->filled('department_id') || $request->filled('location_id')) {
            $employeeQuery = \App\Models\Employee::where('business_id', $business->id);

            if ($request->filled('department_id')) {
                $employeeQuery->where('department_id', $request->integer('department_id'));
            }
            if ($request->filled('location_id')) {
                $employeeQuery->where('location_id', $request->integer('location_id'));
            }

            $employeeIds = $employeeQuery->pluck('id');
        }

        $locationId = $request->filled('location_id') ? $request->integer('location_id') : null;
        $departmentId = $request->filled('department_id') ? $request->integer('department_id') : null;

        return response()->json($this->buildEvents($request, $business, $employeeIds, $locationId, $departmentId));
    }

    public function teamHeadcount(Request $request, Business $business)
    {
        $query = \App\Models\Employee::where('business_id', $business->id);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->integer('location_id'));
        }

        return response()->json(['count' => $query->count()]);
    }

    private function buildEvents(Request $request, Business $business, $employeeIds = null, ?int $locationId = null, ?int $departmentId = null): array
    {
        $start = $request->filled('start') ? Carbon::parse($request->input('start')) : now()->startOfMonth();
        $end = $request->filled('end') ? Carbon::parse($request->input('end')) : now()->endOfMonth();

        $query = LeaveRequest::with(['employee.user', 'leaveType'])
            ->where('business_id', $business->id)
            ->whereNotNull('approved_by')
            ->whereNull('rejection_reason')
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        $leaveEvents = $query->get()->map(function (LeaveRequest $leave) {
            return [
                'id' => 'leave-' . $leave->id,
                'title' => (optional($leave->employee->user)->name ?? 'N/A') . ' - ' . (optional($leave->leaveType)->name ?? 'Leave'),
                'start' => $leave->start_date->toDateString(),
                'end' => $leave->end_date->copy()->addDay()->toDateString(),
                'color' => '#0d6efd',
                'extendedProps' => [
                    'type' => 'leave',
                    'reference_number' => $leave->reference_number,

                    'employee_id' => $leave->employee_id,
                    'employee_name' => optional($leave->employee->user)->name ?? 'N/A',
                    'department_id' => $leave->employee->department_id ?? null,
                    'leave_type' => optional($leave->leaveType)->name ?? 'Leave',
                ],
            ];
        });

        $holidayEvents = Holiday::getHolidaysInRange($business->id, $start, $end, $locationId)->map(function (Holiday $holiday) {
            return [
                'id' => 'holiday-' . $holiday->id . '-' . Carbon::parse($holiday->date)->toDateString(),
                'title' => 'Holiday: ' . $holiday->name,
                'start' => Carbon::parse($holiday->date)->toDateString(),
                'allDay' => true,
                'color' => $holiday->is_working_day ? '#fd7e14' : '#dc3545',
                'extendedProps' => ['type' => 'holiday'],
            ];
        });

        $mandatoryLeaveEvents = MandatoryLeavePeriod::where('business_id', $business->id)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get()
            ->filter(function (MandatoryLeavePeriod $period) use ($departmentId, $locationId) {
                if ($period->scope_type === MandatoryLeavePeriod::SCOPE_DEPARTMENT) {
                    return $departmentId === null || in_array($departmentId, (array) ($period->scope_ids ?? []), true);
                }
                if ($period->scope_type === MandatoryLeavePeriod::SCOPE_LOCATION) {
                    return $locationId === null || in_array($locationId, (array) ($period->scope_ids ?? []), true);
                }
                return true;
            })
            ->map(function (MandatoryLeavePeriod $period) {
                return [
                    'id' => 'mandatory-leave-' . $period->id,
                    'title' => 'Company Leave: ' . $period->name,
                    'start' => $period->start_date->toDateString(),
                    'end' => $period->end_date->copy()->addDay()->toDateString(),
                    'allDay' => true,
                    'color' => '#6f42c1',
                    'extendedProps' => ['type' => 'mandatory_leave'],
                ];
            });

        return $leaveEvents->concat($holidayEvents)->concat($mandatoryLeaveEvents)->values()->all();
    }
}

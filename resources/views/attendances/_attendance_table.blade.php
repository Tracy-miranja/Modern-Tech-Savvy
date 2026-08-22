<table class="table table-striped table-hover" id="attendancesTable">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Date</th>
            <th>Day Type</th>
            <th>Shift</th>
            <th>Clock In</th>
            <th>Clock Out</th>
            <th>Regular Hrs</th>
            <th>OT (Regular)</th>
            <th>OT (Holiday)</th>
            <th>Late/Early</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $attendance)
            <tr @if($attendance->is_absent) class="table-warning" @endif>
                <td>{{ $attendance->employee->user->name ?? 'N/A' }}</td>
                <td>{{ $attendance->date->format("jS M Y") }}</td>
                <td>
                    @if($attendance->is_holiday)
                        <span class="badge bg-danger">Holiday</span>
                    @elseif(!$attendance->is_working_day)
                        <span class="badge bg-info">Non-Working</span>
                    @else
                        <span class="badge bg-success">Working Day</span>
                    @endif
                </td>
                <td>
                    @if($attendance->shift)
                        <strong>{{ $attendance->shift->name }}</strong><br>
                        <small class="text-muted">
                        {{ \Carbon\Carbon::parse($attendance->shift->start_time)->format('H:i') }}
                        -
                        {{ \Carbon\Carbon::parse($attendance->shift->end_time)->format('H:i') }}
                        </small>
                    @else
                        <span class="text-muted">No Shift</span>
                    @endif
                </td>
                <td>
                    @if($attendance->is_absent)
                        <span class="text-danger">Absent</span>
                    @else
                        {{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-' }}
                        @if($attendance->expected_clock_in && $attendance->clock_in)
                            <br><small class="text-muted">Expected: {{ $attendance->expected_clock_in->format('H:i') }}</small>
                        @endif
                    @endif
                </td>
                <td>
                    {{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-' }}
                    @if($attendance->expected_clock_out && $attendance->clock_out)
                        <br><small class="text-muted">Expected: {{ $attendance->expected_clock_out->format('H:i') }}</small>
                    @endif
                </td>
                <td>
                    <strong>{{ \App\Support\TimeFmt::hoursToHm($attendance->regular_hours) }}</strong>
                </td>
                <td>
                    @if($attendance->overtime_regular > 0)
                    <span class="text-primary fw-bold">{{ \App\Support\TimeFmt::hoursToHm($attendance->overtime_regular) }}</span>
                    @else
                    -
                    @endif

                </td>
                <td>
                    @if($attendance->overtime_holiday > 0)
                        <span class="text-warning fw-bold">{{ \App\Support\TimeFmt::hoursToHm($attendance->overtime_holiday) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($attendance->late_minutes > 0)
                        <span class="badge bg-warning">Late: {{ round($attendance->late_minutes) }}m</span>
                    @endif
                    @if($attendance->early_departure_minutes > 0)
                        <span class="badge bg-info">Early: {{ round($attendance->early_departure_minutes) }}m</span>
                    @endif
                    @if($attendance->late_minutes == 0 && $attendance->early_departure_minutes == 0 && !$attendance->is_absent)
                        <span class="text-success">On Time</span>
                    @endif
                </td>
                <td>{{ Str::limit($attendance->remarks, 30) }}</td>
                <td>
                    <button onclick="viewAttendanceDetails(this)" data-attendance="{{ $attendance->id }}" 
                        class="btn btn-sm btn-info" title="View Details">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button onclick="editAttendance(this)" data-attendance="{{ $attendance->id }}"
                    class="btn btn-sm btn-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                    </button>

                    <button onclick="deleteAttendance(this)" data-attendance="{{ $attendance->id }}"
                    class="btn btn-sm btn-danger" title="Delete">
                    <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        @php
            $totalRegular = $attendances->sum('regular_hours');
            $totalOTRegular = $attendances->sum('overtime_regular');
            $totalOTHoliday = $attendances->sum('overtime_holiday');
            $presentDays = $attendances->where('is_absent', false)->count();
            $absentDays = $attendances->where('is_absent', true)->count();
        @endphp
        <tr class="table-secondary fw-bold">
            <td colspan="2">SUMMARY</td>
            <td>
                Present: {{ $presentDays }}<br>
                Absent: {{ $absentDays }}
            </td>
            <td colspan="2"></td>
            <td>{{ \App\Support\TimeFmt::hoursToHm($totalRegular) }}</td>
            <td>{{ \App\Support\TimeFmt::hoursToHm($totalOTRegular) }}</td>
            <td>{{ \App\Support\TimeFmt::hoursToHm($totalOTHoliday) }}</td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>

@if($attendances->isEmpty())
    <div class="alert alert-info">
        No attendance records found for this date.
    </div>
@endif
<table class="table table-striped table-hover" id="workSchedulesTable">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Shift</th>
            <th>Working Days</th>
            <th>Effective From</th>
            <th>Effective To</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($schedules as $schedule)
            <tr>
                <td>{{ $schedule->employee->user->name ?? 'N/A' }}</td>
                <td>
                    @if($schedule->shift)
                        {{ $schedule->shift->name }}<br>
                        <small class="text-muted">{{ $schedule->shift->start_time->format('H:i') }} - {{ $schedule->shift->end_time->format('H:i') }}</small>
                    @else
                        <span class="text-muted">No shift assigned</span>
                    @endif
                </td>
                <td>
                    @foreach($schedule->working_days_names as $day)
                        <span class="badge bg-primary">{{ $day }}</span>
                    @endforeach
                </td>
                <td>{{ $schedule->effective_from->format('jS M Y') }}</td>
                <td>{{ $schedule->effective_to ? $schedule->effective_to->format('jS M Y') : 'Ongoing' }}</td>
                <td>
                    @if($schedule->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </td>
                <td>
                    <button onclick="editWorkSchedule(this)" data-schedule="{{ $schedule->id }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button onclick="deleteWorkSchedule(this)" data-schedule="{{ $schedule->id }}" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    @if(!$schedule->is_active)
                        <button onclick="activateWorkSchedule(this)" data-schedule="{{ $schedule->id }}" class="btn btn-sm btn-success">
                            <i class="bi bi-check-circle"></i> Activate
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($schedules->isEmpty())
    <div class="alert alert-info">
        No work schedules found. Create one to define working days for employees.
    </div>
@endif
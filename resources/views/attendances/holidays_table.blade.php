<div class="mb-3">
    <button class="btn btn-primary" onclick="addHoliday()">
        <i class="bi bi-plus-circle"></i> Add Holiday
    </button>
    <button class="btn btn-secondary" onclick="changeYear(-1)">
        <i class="bi bi-chevron-left"></i> {{ $year - 1 }}
    </button>
    <span class="mx-2 fw-bold">{{ $year }}</span>
    <button class="btn btn-secondary" onclick="changeYear(1)">
        {{ $year + 1 }} <i class="bi bi-chevron-right"></i>
    </button>
</div>

<table class="table table-striped table-hover" id="holidaysTable">
    <thead>
        <tr>
            <th>Name</th>
            <th>Date</th>
            <th>Type</th>
            <th>Recurring</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($holidays->sortBy('date') as $holiday)
            <tr>
                <td>{{ $holiday->name }}</td>
                <td>{{ $holiday->date->format('jS M Y') }}</td>
                <td>
                    @if($holiday->is_working_day)
                        <span class="badge bg-warning">Working Day</span>
                    @else
                        <span class="badge bg-info">Non-Working Day</span>
                    @endif
                </td>
                <td>
                    @if($holiday->is_recurring)
                        <span class="badge bg-success">
                            <i class="bi bi-arrow-repeat"></i> Annual
                        </span>
                    @else
                        <span class="badge bg-secondary">One-time</span>
                    @endif
                </td>
                <td>{{ Str::limit($holiday->description, 50) }}</td>
                <td>
                    <button onclick="editHoliday(this)" data-holiday="{{ $holiday->slug }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button onclick="deleteHoliday(this)" data-holiday="{{ $holiday->slug }}" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($holidays->isEmpty())
    <div class="alert alert-info">
        No holidays found for {{ $year }}. Add holidays to track non-working days and special overtime rates.
    </div>
@endif


<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-labelledby="addHolidayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addHolidayModalLabel">Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" id="holidayFormContainer">
                
            </div>
        </div>
    </div>
</div>

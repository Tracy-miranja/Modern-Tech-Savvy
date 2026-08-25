@php
    $assignments = $roster?->assignments ?? [null];
@endphp

@foreach ($assignments as $index => $assignment)
    <div class="row g-3 mb-3 assignment-row">
        <div class="col-md-4">
            <label class="form-label">Employee</label>
            <input type="text" name="assignments[{{ $index }}][employee]" class="form-control"
                   value="{{ $assignment->employee ?? '' }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Role</label>
            <input type="text" name="assignments[{{ $index }}][role]" class="form-control"
                   value="{{ $assignment->role ?? '' }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="assignments[{{ $index }}][date]" class="form-control date-picker"
                   value="{{ $assignment?->date ? \Carbon\Carbon::parse($assignment->date)->format('Y-m-d') : '' }}"
                   required>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-danger btn-sm remove-assignment">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
@endforeach

<form id="warningForm">
    @csrf
    @if(isset($warning))
    <input type="hidden" name="warning_id" value="{{ $warning->id }}">
    @endif
    <div class="row g-3">
        <div class="col-6">
            <label class="form-label fw-medium">Employee *</label>
            <select name="employee_id" class="form-select" required>
                <option value="" disabled {{ !isset($warning) ? 'selected' : '' }}>Select employee</option>
                @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" {{ isset($warning) && $warning->employee_id == $employee->id ? 'selected' : '' }}>
                    {{ $employee->full_name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <label class="form-label fw-medium">Category</label>
            <select name="category" class="form-select">
                @foreach (\App\Models\Warning::CATEGORIES as $cat)
                <option value="{{ $cat }}" {{ (isset($warning) ? $warning->category : 'misconduct') === $cat ? 'selected' : '' }}>
                    {{ \App\Models\Warning::label($cat) }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label fw-medium">Offence / Incident *</label>
            <textarea name="offence" class="form-control" rows="3" required>{{ $warning->offence ?? '' }}</textarea>
        </div>

        <div class="col-6">
            <label class="form-label fw-medium">Reported By</label>
            <input type="text" name="reported_by_name" class="form-control" value="{{ $warning->reported_by_name ?? '' }}">
        </div>
        <div class="col-6">
            <label class="form-label fw-medium">Reported On</label>
            <input type="date" name="issue_date" class="form-control"
                value="{{ isset($warning) ? $warning->issue_date->toDateString() : now()->toDateString() }}" required>
        </div>

        <div class="col-6">
            <label class="form-label fw-medium">Stage</label>
            <select name="stage" class="form-select">
                @foreach (\App\Models\Warning::STAGES as $stage)
                <option value="{{ $stage }}" {{ (isset($warning) ? $warning->stage : 'informal_action') === $stage ? 'selected' : '' }}>
                    {{ \App\Models\Warning::label($stage) }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <label class="form-label fw-medium">Hearing Date</label>
            <input type="date" name="hearing_date" class="form-control"
                value="{{ isset($warning) && $warning->hearing_date ? $warning->hearing_date->toDateString() : '' }}">
        </div>

        <div class="col-6">
            <label class="form-label fw-medium">Decision Outcome</label>
            <select name="decision_outcome" class="form-select">
                @foreach (\App\Models\Warning::DECISION_OUTCOMES as $outcome)
                <option value="{{ $outcome }}" {{ (isset($warning) ? $warning->decision_outcome : 'pending') === $outcome ? 'selected' : '' }}>
                    {{ $outcome === 'pending' ? '— Pending —' : \App\Models\Warning::label($outcome) }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <label class="form-label fw-medium">Appeal Status</label>
            <select name="appeal_status" class="form-select">
                <option value="">—</option>
                @foreach (\App\Models\Warning::APPEAL_STATUSES as $status)
                <option value="{{ $status }}" {{ isset($warning) && $warning->appeal_status === $status ? 'selected' : '' }}>
                    {{ \App\Models\Warning::label($status) }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label fw-medium">Notes</label>
            <textarea name="description" class="form-control" rows="3">{{ $warning->description ?? '' }}</textarea>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" onclick="closeWarningModal()">Cancel</button>
        <button type="button" class="btn btn-success" onclick="saveWarning(this)">
            {{ isset($warning) ? 'Update Case' : 'Open Case' }}
        </button>
    </div>
</form>

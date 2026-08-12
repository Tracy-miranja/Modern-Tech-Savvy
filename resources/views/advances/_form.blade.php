<form id="advanceForm" method="post">
    @csrf
    @if (isset($advance))
    <input type="hidden" name="advance_id" value="{{ $advance->id }}">
    @endif

    <div class="form-group mb-3">
        <label for="employee_id">Employee</label>
        <select class="form-control" id="employee_id" name="employee_id" required>
            <option value="">Select an Employee</option>
            @foreach ($employees as $employee)
            <option value="{{ $employee->id }}"
                {{ isset($advance) && $advance->employee_id == $employee->id ? 'selected' : '' }}>
                {{ $employee->user->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="form-group mb-3">
        <label for="amount">Advance Amount</label>
        <input type="number" class="form-control" id="amount" name="amount" required min="1"
            value="{{ isset($advance) ? $advance->amount : old('amount') }}">
    </div>

    <div class="form-group mb-3">
        <label for="date">Advance Date</label>
        <input type="datetime-local" placeholder="Advance Date" class="form-control" id="date" name="date" required
            value="{{ isset($advance) ? \Carbon\Carbon::parse($advance->date)->format('Y-m-d\TH:i') : old('date') }}">

    </div>

    <div class="form-group mb-3">
        <label for="note">Note</label>
        <textarea name="note" id="note" class="form-control"
            rows="4">{{ isset($advance) ? $advance->note : old('note') }}</textarea>
    </div>

    <div>
        <button onclick="saveAdvance(this)" type="button" class="btn btn-primary w-100" id="submitButton">
            <i class="bi bi-check-circle me-2"></i> {{ isset($advance) ? 'Update Advance' : 'Save Advance' }}
        </button>
    </div>
</form>

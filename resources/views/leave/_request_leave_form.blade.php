<form id="leaveForm" method="post" enctype="multipart/form-data">
    @csrf

    @if ((auth()->user()->hasRole('admin') || auth()->user()->hasRole('business-admin')) && (session('active_role') == 'admin' || session('active_role') == 'business-admin'))
        <div class="mb-3">
            <label for="business_location" class="form-label">Select Business or Location</label>
            <select class="form-select" id="business_location" name="business_location" required>
                <option value="" disabled selected>Select Location</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->slug }}">{{ $location->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="employee" class="form-label">Select an Employee</label>
            <select class="form-select" id="employee" name="employee_id" required>
                <option value="" disabled selected>Select Employee</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->user->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="mb-2">
        <label for="leave_type" class="form-label">Leave Type</label>
        <select class="form-select" id="leave_type" name="leave_type_id" required>
            <option value="" disabled selected>Select Leave Type</option>
            @foreach ($leaveTypes as $leaveType)
                <option
                    value="{{ $leaveType->id }}"
                    data-requires-attachment="{{ $leaveType->requires_attachment ? '1' : '0' }}"
                    data-allows-backdating="{{ $leaveType->allows_backdating ? '1' : '0' }}"
                    data-allows-half-day="{{ $leaveType->allows_half_day ? '1' : '0' }}"
                >
                    {{ $leaveType->name }}
                </option>
            @endforeach
        </select>
        <small id="remainingDays" class="text-muted d-block mt-1"></small>
    </div>

    {{-- Attachment field (hidden by default) --}}
    <div class="mb-2 d-none" id="attachmentField">
        <label for="attachment" class="form-label">Attachment (Required for this leave type)</label>
        <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.jpg,.png,.doc,.docx">
        <div class="form-check mt-2">
            <input type="checkbox" class="form-check-input" id="attach_later" name="attach_later" value="1">
            <label class="form-check-label" for="attach_later">I'll upload the document later</label>
        </div>
    </div>

    {{-- Half-day controls (toggle/hidden by default) --}}
    <div class="row d-none" id="halfDayRow">
        <div class="col-md-6 mb-2">
            <div class="form-check mt-2">
                <input type="checkbox" class="form-check-input" id="half_day" name="half_day" value="1">
                <label class="form-check-label" for="half_day">Request Half Day</label>
            </div>
        </div>
        <div class="col-md-6 mb-2 d-none" id="halfDayTypeCol">
            <label for="half_day_type" class="form-label">Half Day Type</label>
            <select class="form-select" id="half_day_type" name="half_day_type">
                <option value="" selected disabled>Select</option>
                <option value="morning">Morning</option>
                <option value="afternoon">Afternoon</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-2">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" class="form-control datepicker" id="start_date" name="start_date" required>
        </div>
        <div class="col-md-6 mb-2">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" class="form-control datepicker" id="end_date" name="end_date" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="reason" class="form-label">Reason for Leave</label>
        <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Provide a brief reason..."></textarea>
    </div>

    <div class="text-center">
        <button type="button" onclick="saveLeave(this)" class="btn btn-primary w-100">
            <i class="bi bi-check-circle"></i> Submit Leave Request
        </button>
    </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const leaveTypeSelect = document.getElementById('leave_type');
    const employeeSelect  = document.getElementById('employee');
    const attachmentField = document.getElementById('attachmentField');

    const halfDayRow      = document.getElementById('halfDayRow');
    const halfDayCheckbox = document.getElementById('half_day');
    const halfDayTypeCol  = document.getElementById('halfDayTypeCol');

    const startDateInput  = document.getElementById('start_date');
    const endDateInput    = document.getElementById('end_date');

    function todayStr() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }

    function applyBackdatingRule(allowsBackdating) {
        if (allowsBackdating) {
            startDateInput.removeAttribute('min');
            endDateInput.removeAttribute('min');
        } else {
            const t = todayStr();
            startDateInput.setAttribute('min', t);
            endDateInput.setAttribute('min', t);
        }
    }

    function toggleHalfDayUI(allowsHalfDay) {
        if (allowsHalfDay) {
            halfDayRow.classList.remove('d-none');
        } else {
            halfDayRow.classList.add('d-none');
            halfDayCheckbox.checked = false;
            halfDayTypeCol.classList.add('d-none');
        }
    }

    halfDayCheckbox?.addEventListener('change', function() {
        if (this.checked) {
            halfDayTypeCol.classList.remove('d-none');
        } else {
            halfDayTypeCol.classList.add('d-none');
        }
    });

    leaveTypeSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const requiresAttachment = opt.getAttribute('data-requires-attachment') === '1';
        const allowsBackdating   = opt.getAttribute('data-allows-backdating') === '1';
        const allowsHalfDay      = opt.getAttribute('data-allows-half-day') === '1';

        // Show/hide attachment field
        if (requiresAttachment) {
            attachmentField.classList.remove('d-none');
        } else {
            attachmentField.classList.add('d-none');
            document.getElementById('attach_later').checked = false;
            document.getElementById('attachment').value = '';
        }

        // Backdating rule affects min attribute
        applyBackdatingRule(allowsBackdating);

        // Half-day UI
        toggleHalfDayUI(allowsHalfDay);

        // Fetch remaining days
        const leaveTypeId = this.value;
        const employeeId  = employeeSelect ? employeeSelect.value : null;

        if (leaveTypeId) {
            const payload = { leave_type_id: leaveTypeId };
            // Only include employee_id if an admin selected someone
            if (employeeId) {
                payload.employee_id = employeeId;
            }

fetch("{{ route('ajax.leave.remaining-days') }}", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": "{{ csrf_token() }}"
    },
    body: JSON.stringify(payload)
})
.then(async (response) => {
    const text = await response.text();
    const ct = response.headers.get('content-type') || '';

    if (!response.ok || !ct.includes('application/json')) {
        console.error('Remaining days raw response:', response.status, response.statusText, text);
        throw new Error('Failed to fetch remaining days');
    }

    return JSON.parse(text);
})
.then(data => {
    console.log('Remaining days response:', data);
    document.getElementById('remainingDays').innerText =
        "Remaining Days: " + (data.remaining_days ?? 0);
})
.catch(err => {
    console.error('Remaining days error:', err);
    document.getElementById('remainingDays').innerText =
        "Remaining Days: N/A";
});


        }
    });

    // Initialize min dates to today by default (will toggle when a leave type is chosen)
    applyBackdatingRule(false);
});
</script>

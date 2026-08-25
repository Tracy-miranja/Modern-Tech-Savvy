<form action="" id="clockOutForm" method="POST">
    @csrf

    <div class="form-group">
        <label for="employee_id">Employee</label>
        <select name="employee_id" id="employee_id" class="form-control" required>
            <option value="">-- Select Employee --</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->user->name }}</option>
            @endforeach
        </select>
    </div>

    <input type="hidden" name="latitude" id="clockout_latitude">
    <input type="hidden" name="longitude" id="clockout_longitude">
    <input type="hidden" name="device_mac" id="clockout_device_mac">

    <div id="clockout_mac_wrapper" class="mb-3 d-none">
        <label for="clockout_device_mac_input" class="form-label fw-semibold text-dark">
            Device MAC (e.g. 10:9A:DD:01:23:45)
        </label>
        <input type="text" id="clockout_device_mac_input" class="form-control" placeholder="AA:BB:CC:DD:EE:FF">
        <div class="form-text">Saved on this browser for next time.</div>
    </div>

    <div class="form-group">
        <label for="remarks">Remarks</label>
        <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
    </div>

    <button type="button" onclick="clockOut(this)" class="btn btn-primary w-100">
        <i class="bi bi-check-circle me-2"></i> Clock Out
    </button>
</form>

<meta name="enforce_geofence" content="{{ (int)($currentBusiness->enforce_geofence ?? 0) }}">
<meta name="enforce_mac" content="{{ (int)($currentBusiness->enforce_mac ?? 0) }}">

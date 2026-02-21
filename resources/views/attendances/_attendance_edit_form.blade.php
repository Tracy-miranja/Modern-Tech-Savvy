<form id="attendanceEditForm">
  @csrf

  <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Employee</label>
      <input type="text" class="form-control" value="{{ $attendance->employee->user->name ?? 'N/A' }}" disabled>
    </div>

    <div class="col-md-6 mb-3">
      <label class="form-label">Date</label>
      <input type="text" class="form-control" value="{{ $attendance->date?->format('jS M Y') }}" disabled>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Clock In (HH:MM)</label>
      <input type="time" class="form-control" name="clock_in"
             value="{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '' }}">
      <div class="form-text">Leave blank to keep current.</div>
    </div>

    <div class="col-md-6 mb-3">
      <label class="form-label">Clock Out (HH:MM)</label>
      <input type="time" class="form-control" name="clock_out"
             value="{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}">
      <div class="form-text">Leave blank to keep current.</div>
    </div>
  </div>

  <div class="mb-3">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="is_absent" id="is_absent"
             value="1" {{ $attendance->is_absent ? 'checked' : '' }}>
      <label class="form-check-label" for="is_absent">Mark as Absent</label>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Remarks</label>
    <textarea class="form-control" name="remarks" rows="3">{{ $attendance->remarks }}</textarea>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-secondary"
        data-bs-dismiss="modal"
        onclick="hideModal('editAttendanceModal')">
  Cancel
</button>
    <button type="button" class="btn btn-primary" onclick="submitAttendanceUpdate(this)">
      <i class="bi bi-check-circle"></i> Save Changes
    </button>
  </div>
</form>

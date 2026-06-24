<form id="warningForm">
  @csrf
  {{-- <input type="hidden" name="warning_id" value="{{ $warning->id ?? '' }}"> --}}
  @if(isset($warning) && $warning->id)
  <input type="hidden" name="warning_id" value="{{ $warning->id }}">
@endif

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
    <div>
      <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Employee *</label>
      <select name="employee_id"
        style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;">
        <option value="">Select employee</option>
        @foreach($employees as $emp)
          <option value="{{ $emp->id }}"
            {{ isset($warning) && $warning->employee_id == $emp->id ? 'selected' : '' }}>
            {{ $emp->user->name }}
          </option>
        @endforeach
      </select>
    </div>
    <div>
      <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Issue Date *</label>
      <input type="date" name="issue_date"
        value="{{ isset($warning) ? \Carbon\Carbon::parse($warning->issue_date)->format('Y-m-d') : '' }}"
        style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;">
    </div>
  </div>

  <div style="margin-bottom:16px;">
    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Reason *</label>
    <input type="text" name="reason"
      value="{{ $warning->reason ?? '' }}"
      placeholder="Brief reason for warning"
      style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;">
  </div>

  <div style="margin-bottom:16px;">
    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Description</label>
    <textarea name="description" rows="3"
      placeholder="Additional details..."
      style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;resize:vertical;">{{ $warning->description ?? '' }}</textarea>
  </div>

  @if(isset($warning) && $warning->id)
  <div style="margin-bottom:16px;">
    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Status</label>
    <select name="status"
      style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;">
      <option value="active"   {{ ($warning->status ?? '') === 'active'   ? 'selected' : '' }}>Active</option>
      <option value="resolved" {{ ($warning->status ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
    </select>
  </div>
  @endif

  <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid #f3f4f6;margin-top:4px;">
    <button type="button" onclick="closeWarningModal()"
      style="background:#f3f4f6;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;">
      Cancel
    </button>
    <button type="button" onclick="saveWarning(this)"
      style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;">
      {{ isset($warning) && $warning->id ? 'Update Warning' : 'Issue Warning' }}
    </button>
  </div>
</form>

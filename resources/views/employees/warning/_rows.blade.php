@forelse($warnings as $warning)
<tr style="border-bottom:1px solid #f9fafb;"
    data-status="{{ $warning->status }}"
    data-date="{{ \Carbon\Carbon::parse($warning->issue_date)->format('Y-m-d') }}">
  <td style="padding:14px 20px;">
    <div style="font-weight:800;color:#111;">{{ $warning->employee->user->name ?? 'N/A' }}</div>
    <div style="font-size:11px;color:#9ca3af;">{{ $warning->employee->user->email ?? '' }}</div>
  </td>
  <td style="padding:14px 20px;color:#374151;max-width:200px;">{{ $warning->reason }}</td>
  <td style="padding:14px 20px;color:#374151;">{{ \Carbon\Carbon::parse($warning->issue_date)->format('M d, Y') }}</td>
  <td style="padding:14px 20px;color:#374151;">{{ $warning->issuedBy->name ?? 'N/A' }}</td>
  <td style="padding:14px 20px;">
    @if($warning->status === 'active')
      <span style="background:#fef2f2;color:#ef4444;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">Active</span>
    @else
      <span style="background:#f0fdf4;color:#10b981;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">Resolved</span>
    @endif
  </td>
  <td style="padding:14px 20px;">
    <div style="display:flex;gap:6px;">
      <button onclick="editWarning({{ $warning->id }})"
  style="background:#f3f4f6;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;">
  <i class="bi bi-pencil" style="color:#6366f1;"></i>
</button>
<button onclick="deleteWarning({{ $warning->id }}, this)"
  style="background:#fef2f2;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;">
  <i class="bi bi-trash" style="color:#ef4444;"></i>
</button>
    </div>
  </td>
</tr>
@empty
<tr>
  <td colspan="6" style="padding:40px;text-align:center;color:#9ca3af;">No warnings found.</td>
</tr>
@endforelse

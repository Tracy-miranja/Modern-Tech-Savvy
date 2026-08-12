@forelse ($warnings as $warning)
<tr data-stage="{{ $warning->stage }}" data-category="{{ $warning->category }}">
  <td style="padding:12px 20px;font-family:monospace;color:#6b7280;">{{ $warning->case_id }}</td>
  <td style="padding:12px 20px;">{{ $warning->employee->full_name ?? '—' }}</td>
  <td style="padding:12px 20px;">
    <span style="background:#f3f4f6;padding:3px 10px;border-radius:20px;font-size:12px;">
      {{ \App\Models\Warning::label($warning->category) }}
    </span>
  </td>
  <td style="padding:12px 20px;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
    {{ $warning->offence }}
  </td>
  <td style="padding:12px 20px;">{{ $warning->issue_date?->format('Y-m-d') }}</td>
  <td style="padding:12px 20px;">
    <span style="background:#eef2ff;color:#4338ca;padding:3px 10px;border-radius:20px;font-size:12px;">
      {{ \App\Models\Warning::label($warning->stage) }}
    </span>
  </td>
  <td style="padding:12px 20px;">
    {{ $warning->decision_outcome && $warning->decision_outcome !== 'pending' ? \App\Models\Warning::label($warning->decision_outcome) : '—' }}
  </td>
  <td style="padding:12px 20px;white-space:nowrap;">
    <button onclick="viewWarning({{ $warning->id }})" title="View" style="background:none;border:none;cursor:pointer;color:#6b7280;"><i class="bi bi-eye"></i></button>
    <button onclick="editWarning({{ $warning->id }})" title="Edit" style="background:none;border:none;cursor:pointer;color:#6b7280;"><i class="bi bi-pencil"></i></button>
    <button onclick="deleteWarning({{ $warning->id }})" title="Delete" style="background:none;border:none;cursor:pointer;color:#ef4444;"><i class="bi bi-trash"></i></button>
  </td>
</tr>
@empty
<tr><td colspan="8" style="padding:24px;text-align:center;color:#9ca3af;">No disciplinary cases found.</td></tr>
@endforelse

<!-- _leave_entitlements_table.blade.php -->
@php
    // Build a composite slug that can find the record without exposing ID
    // slug = base64("business:employee:leaveType:leavePeriod") URL-safe
    function ent_slug($e) {
        $raw = implode(':', [
            (int)$e->business_id,
            (int)$e->employee_id,
            (int)$e->leave_type_id,
            (int)$e->leave_period_id
        ]);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
@endphp

<table class="table table-hover table-bordered table-striped" id="leaveEntitlementsTable">
    <thead>
        <tr>
            <th>Employee No</th>
            <th>Employee</th>
            <th>Leave Type</th>
            <th>Entitled Days</th>
            <th>Total Days</th>
            <th>Days Taken</th>
            <th>Days <br> Remaining</th>
            <th style="width: 220px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($leaveEntitlements as $entitlement)
            @php $slug = ent_slug($entitlement); @endphp
            <tr>
                <td>
                    <a href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-hash"></i>
                        {{ $entitlement->employee->employee_code ?? '—' }}
                    </a>
                </td>
                <td>{{ $entitlement->employee->user->name ?? 'N/A' }}</td>
                <td>{{ $entitlement->leaveType->name ?? 'N/A' }}</td>
                <td>{{ number_format((float) $entitlement->entitled_days, 2) }}</td>
                <td>{{ number_format((float) $entitlement->total_days, 2) }}</td>
                <td>{{ number_format((float) $entitlement->days_taken, 2) }}</td>
                <td>{{ number_format((float) $entitlement->days_remaining, 2) }}</td>
                <td class="d-flex gap-1 flex-wrap">
{{--                     <button type="button"
                            class="btn btn-secondary btn-sm"
                            title="Details"
                            data-id="{{ $entitlement->id }}"
                            data-slug="{{ $slug }}"
                            onclick="viewLeaveEntitlements(this)">
                        <i class="bi bi-view-list me-1"></i> Details
                    </button> --}}

                    <button type="button"
                            class="btn btn-warning btn-sm"
                            title="Edit"
                            data-id="{{ $entitlement->id }}"
                            data-slug="{{ $slug }}"
                            onclick="editLeaveEntitlements(this)">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>

{{--                     <button type="button"
                            class="btn btn-danger btn-sm"
                            title="Delete"
                            data-id="{{ $entitlement->id }}"
                            data-slug="{{ $slug }}"
                            onclick="deleteLeaveEntitlements(this)">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button> --}}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted">No entitlements found for the selected period.</td>
            </tr>
        @endforelse
    </tbody>
</table>

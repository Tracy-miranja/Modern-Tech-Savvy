<div id="employeeReliefsTable" class="table-responsive">
    <table class="table table-hover table-bordered align-middle">
        <thead class="bg-light">
            <tr>
                <th scope="col" class="text-dark fw-semibold">Employee</th>
                <th scope="col" class="text-dark fw-semibold">Relief</th>
                <th scope="col" class="text-dark fw-semibold">Custom Amount</th>
                <th scope="col" class="text-dark fw-semibold">Active</th>
                <th scope="col" class="text-dark fw-semibold">Start Date</th>
                <th scope="col" class="text-dark fw-semibold">End Date</th>
                <th scope="col" class="text-dark fw-semibold text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employeeReliefs as $employeeRelief)
            <tr>
                <td>{{ $employeeRelief->employee->user->name }}</td>
                <td>{{ $employeeRelief->relief?->name ?? 'Relief Deleted' }}</td>
                <td>{{ $employeeRelief->amount ? number_format($employeeRelief->amount, 2) : 'Default' }}</td>
                <td>{{ $employeeRelief->is_active ? 'Yes' : 'No' }}</td>
                <td>{{ $employeeRelief->start_date ?? 'N/A' }}</td>
                <td>{{ $employeeRelief->end_date ?? 'N/A' }}</td>
                <td class="text-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-warning me-2"
                            data-employee-relief="{{ $employeeRelief->id }}" onclick="editEmployeeRelief(this)">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-employee-relief="{{ $employeeRelief->id }}"
                            onclick="deleteEmployeeRelief(this)">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-2"></i> No reliefs assigned to employees yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@push('styles')
<style>
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table th,
.table td {
    vertical-align: middle;
}

.btn-group .btn {
    padding: 6px 12px;
}

@media (max-width: 576px) {
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .btn-group .btn {
        width: 100%;
        text-align: center;
    }
}
</style>
@endpush

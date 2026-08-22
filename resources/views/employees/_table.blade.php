<div class="table-responsive">
    <table id="employeesTable" class="table table-hover table-bordered w-100">
        <thead class="bg-light">
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Department</th>
                <th>Job Category</th>
                <th>Location</th>
                 <th>Monthly Salary</th>
                <th>Hourly Rate</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr id="loadingRow" style="display: none;">
                <td colspan="8" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
            @forelse ($employees as $employee)
            <tr data-employee-id="{{ $employee->id }}">
                <td>{{ $employee->user->name }}</td>
                <td>{{ $employee->employee_code }}</td>
                <td>{{ $employee->department ? $employee->department->name : 'N/A' }}</td>
                <td>{{ optional($employee->employmentDetails)->jobCategory ? $employee->employmentDetails->jobCategory->name : 'N/A' }}
                </td>
                <td>{{ $employee->location ? $employee->location->name : $employee->business->company_name }}</td>
                <td>{{ number_format((float) (optional($employee->paymentDetails)->basic_salary ?? 0), 2) . ' ' . (optional($employee->paymentDetails)->currency ?? '') }}
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewEmployee({{ $employee->id }})">
                            <i class="fa fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="editEmployee({{ $employee->id }})">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <a href="{{ route('business.performance.employee', ['business' => $employee->business->slug, 'employee' => $employee->id]) }}" class="btn btn-sm btn-outline-info">
                            <i class="fa fa-chart-line"></i> Performance
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee({{ $employee->id }})">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-2"></i> No employees found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

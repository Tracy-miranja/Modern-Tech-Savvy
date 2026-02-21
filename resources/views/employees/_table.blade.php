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
        <td>{{ $employee->user->name ?? 'N/A' }}</td>
        <td>{{ $employee->employee_code ?? 'N/A' }}</td>
        <td>{{ $employee->department?->name ?? 'N/A' }}</td>
        <td>{{ $employee->employmentDetails?->jobCategory?->name ?? 'N/A' }}</td>
        <td>
            {{ $employee->location?->name ?? ($employee->business?->company_name ?? 'Main Office') }}
        </td>

        <!-- Define $payment ONCE at the beginning of the row -->
        @php
            $payment = $employee->paymentDetails;
            $currency = $payment?->currency ?? 'KES';
        @endphp

        <!-- Monthly Salary Column -->
        <td class="text-nowrap">
            @if($payment && $payment->payment_type !== 'hourly')
                {{ number_format((float) ($payment->basic_salary ?? 0), 2) }} {{ $currency }}
            @else
                <span class="text-muted">—</span>
            @endif
        </td>

        <!-- Hourly Rate Column -->
        <td class="text-nowrap">
            @if($payment && $payment->payment_type === 'hourly')
                {{ number_format((float) ($payment->hourly_rate ?? 0), 2) }} {{ $currency }}/hr
            @else
                <span class="text-muted">—</span>
            @endif
        </td>

        <td>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" onclick="viewEmployee({{ $employee->id }})">
                    <i class="fa fa-eye"></i> View
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="editEmployee({{ $employee->id }})">
                    <i class="fa fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee({{ $employee->id }})">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted py-4">
            <i class="fa fa-info-circle me-2"></i> No employees found.
        </td>
    </tr>
@endforelse
        </tbody>
    </table>
</div>

@php
    $totalPayrollCount = $payrolls->count();
    $totalNetPay = $payrolls->sum(function($payroll) {
        return $payroll->employeePayrolls->sum('net_pay');
    });
@endphp

{{-- <div class="mb-3">
    <h5 class="text-muted">
        <span class="text-danger">{{ $totalPayrollCount ?? $payrolls->count() }} payroll(s) found</span> |
        Total Payroll: {{ number_format($totalPayrollAmount ?? 0, 2) }} |
        Total Net Pay: {{ number_format($totalNetPay ?? 0, 2) }}
    </h5>
</div> --}}


<div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead class="border-bottom">
            <tr>
                <th><input type="checkbox" id="selectAllPayrolls" onclick="toggleSelectAll()"></th>
                <th>Month</th>
                <th>No. of Payslips</th>
                <th>Status</th>
                <th>Emailed</th>
                <th>1/3 Rule</th>
                <th>Location</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payrolls as $payroll)
    @php
        // Employee payrolls after filtering
        $filteredEmployeePayrolls = $payroll->employeePayrolls;

        $payslipCount = $filteredEmployeePayrolls->count();

        $locations = $filteredEmployeePayrolls
            ->pluck('employee.location.name')
            ->unique()
            ->filter();
    @endphp
    <tr>
        <td><input type="checkbox" class="payrollCheckbox" value="{{ $payroll->id }}" onclick="updateSelectedPayrolls()"></td>
        <td>{{ now()->month($payroll->payrun_month)->monthName }} ({{ str_pad($payroll->payrun_month, 2, '0', STR_PAD_LEFT) }}), {{ $payroll->payrun_year }}</td>
        <td>{{ $payslipCount }} payslips</td>
        <td>{{ $payroll->status === 'closed' ? 'closed' : 'open' }}</td>
        <td>{{ $payroll->emailed ? '✔' : '✘' }}</td>
        <td>{{ $payroll->third_rule ? '✔' : '✘' }}</td>
        <td>
            @if($locations->count() === 1)
                {{ $locations->first() }}
            @elseif($locations->count() > 1)
                Multiple Locations
            @else
                {{ $business->company_name }}
            @endif
        </td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-dark" onclick="viewPayroll({{ $payroll->id }}, '{{ $payslipCount }}')"><i class="fa fa-eye"></i></button>
            <button class="btn btn-sm btn-outline-primary" onclick="emailPayslips({{ $payroll->id }})"><i class="fa fa-envelope"></i></button>
            <button class="btn btn-sm btn-outline-danger" onclick="deletePayroll({{ $payroll->id }})"><i class="fa fa-trash"></i></button>
            <button class="btn btn-sm btn-outline-dark" onclick="closeMonth({{ $payroll->id }}, {{ $payroll->payrun_month }}, {{ $payroll->payrun_year }})"><i class="fa fa-lock"></i></button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted py-4">
            <i class="fa fa-info-circle me-2"></i> No payrolls found.
        </td>
    </tr>
@endforelse

        </tbody>
    </table>
</div>

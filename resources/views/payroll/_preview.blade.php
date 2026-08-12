<div class="table-responsive">
    <table class="table table-hover table-bordered" id="previewTable">
        <thead class="bg-light">
            <tr>
                <th>Name</th>
                <th>Basic Salary</th>
                <th>Allowances</th>

                <th>Overtime</th>
                <th>Gross Pay</th>
                <th>SHIF</th>
                <th>NSSF</th>
                <th>Housing Levy</th>
                <th>HELB</th>
                <th>Taxable Income</th>
                <th>PAYE (Before Reliefs)</th>
                <th>Reliefs</th>
                <th>PAYE</th>
                <th>Deductions</th>
                <th>Advances</th>
                <th>Loans</th>
                <th>Reimbursement</th>
                <th>Net Pay</th>
                <th>Bank Details</th>
                <th>Present Days</th>
                <th>Absent Days</th>
                <th>Days in Month</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payrollData as $data)
            <?php
                $allowances = $data['allowances'] ?? [];
                $taxableAllowancesDisplay = array_filter($allowances, fn($a) => is_array($a) && ($a['is_taxable'] ?? false) && !($a['is_employer_contribution'] ?? false));
                $benefitsDisplay = array_filter($allowances, fn($a) => is_array($a) && !($a['is_taxable'] ?? false) && !($a['is_employer_contribution'] ?? false));
            ?>
            <tr>
                <td>{{ $data['employee']->user?->name ?? 'N/A' }}</td>
                {{-- {{ $data['currency'] ?? 'KES' }} --}}
                <td>{{ number_format($data['basic_salary'] ?? 0, 2) }} </td>
                <td>{{ collect($taxableAllowancesDisplay)->map(fn($a) => "{$a['name']} (" . number_format($a['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}
                </td>

                <td>{{ number_format($data['overtime'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['gross_pay'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['shif'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['nssf'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['housing_levy'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['helb'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['taxable_income'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['paye_before_reliefs'] ?? 0, 2) }}</td>
                <td>{{ collect($data['reliefs'])->map(fn($r) => "{$r['name']} (" . number_format($r['display_amount'] ?? $r['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}
                </td>
                <td>{{ number_format($data['paye'] ?? 0, 2) }}</td>
                <td>{{ collect($data['deductions'])->map(fn($d) => "{$d['name']} (" . number_format($d['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}
                </td>
                <td>{{ number_format($data['advance_recovery'] ?? 0, 2) }}</td>
                <td>{{ number_format($data['loan_repayment'] ?? 0, 2) }}</td>
                <td>{{ collect($benefitsDisplay)->map(fn($a) => "{$a['name']} (" . number_format($a['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}
                </td>
                <td>{{ number_format($data['net_pay'] ?? 0, 2) }}</td>
                <td>{{ $data['bank_name'] ?? 'N/A' }} ({{ $data['account_number'] ?? 'N/A' }})</td>
                <td>{{ $data['attendance_present'] ?? 0 }}</td>
                <td>{{ $data['attendance_absent'] ?? 0 }}</td>
                <td>{{ $data['days_in_month'] ?? 0 }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="22" class="text-center">No payroll data available</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

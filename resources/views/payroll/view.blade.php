<x-app-layout title="{{ $page }}">
    <div class="container py-5" id="payroll-document">
        <div class="invoice-header mb-4 p-4 bg-white shadow-sm rounded">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex align-items-center">
                    @if($entityType === 'business')
                    <img src="{{ $entity->getImageUrl() }}" alt="{{ $entity->company_name }} Logo" class="me-3"
                        style="max-height: 60px; max-width: 150px; object-fit: contain;">
                    @elseif($entityType === 'location')
                    <img src="{{ $business->getImageUrl() }}" alt="{{ $business->company_name }} Logo" class="me-3"
                        style="max-height: 60px; max-width: 150px; object-fit: contain;">
                    @else
                    <div class="me-3 bg-light rounded d-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px;">
                        <span
                            class="text-muted fw-bold">{{ strtoupper(substr($entity->company_name ?? $entity->name, 0, 1)) }}</span>
                    </div>
                    @endif
                    <div>
                        <h1 class="h4 fw-bold mb-1 text-dark">
                            {{ $entity->company_name ?? $entity->name ?? 'Default Company Name' }}
                        </h1>
                        <p class="text-muted small mb-1">{{ $entity->physical_address ?? 'Default Address' }}</p>
                        <p class="text-muted small mb-1">Phone:
                            {{ ($entityType === 'business' ? $entity->phone : $business->phone) ?? '+123-456-7890' }}
                        </p>
                        <p class="text-muted small mb-0">Email:
                            {{ ($entityType === 'business' && $entity->user ? $entity->user->email : $business->user->email) ?? 'companyemail@company.com' }}
                        </p>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <h2 class="h5 fw-bold text-dark">Payroll Statement</h2>
                    <p class="text-muted small mb-1">Payroll Period:
                        {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}
                    </p>
                    <p class="text-muted small mb-1">Payroll ID: {{ $payroll->id }}</p>
                    <p class="text-muted small mb-0">Date: {{ now()->format('F d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Payroll Details Section -->
        <div class="invoice-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h5 fw-bold mb-0 text-dark">Payroll Details</h4>
                <div class="d-flex gap-2">
                     {{-- ── Variance & AI Analysis ─────────────────────────────────── --}}
            <a href="{{ route('business.payroll.variance', ['business' => $business->slug]) }}"
               class="btn btn-outline-secondary modern-btn flex-shrink-0">
                <i class="bi bi-graph-up-arrow me-1"></i> Variance & AI Analysis
            </a>
            {{-- ── END Variance ────────────────────────────────────────────── --}}
                    {{-- ── NSSF Download Dropdown ─────────────────────────────────────────── --}}
            <div class="dropdown d-inline-flex">
               <button class="btn btn-outline-secondary modern-btn dropdown-toggle flex-shrink-0"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false">

                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> NSSF
                </button>

                <ul class="dropdown-menu shadow-sm" style="min-width: 280px;">

                    {{-- ── New NSSF (Return/Remittance) format ──────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            New NSSF (Return/Remittance) Format
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'new_remittance', 'payroll_id' => $payroll->id, 'format' => 'xlsx']) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'new_remittance', 'payroll_id' => $payroll->id, 'format' => 'csv']) }}">
                            <i class="bi bi-file-earmark-text text-secondary"></i> Export as CSV
                        </a>
                    </li>

                    <li><hr class="dropdown-divider my-1"></li>

                    {{-- ── Up to June 2018 ───────────────────────────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            Up to June 2018
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'pre_2018', 'payroll_id' => $payroll->id, 'format' => 'xlsx']) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'pre_2018', 'payroll_id' => $payroll->id, 'format' => 'csv']) }}">
                            <i class="bi bi-file-earmark-text text-secondary"></i> Export as CSV
                        </a>
                    </li>

                    <li><hr class="dropdown-divider my-1"></li>

                    {{-- ── Old NSSF Format ───────────────────────────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            Old NSSF Format
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'old_format', 'payroll_id' => $payroll->id, 'format' => 'xlsx']) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'old_format', 'payroll_id' => $payroll->id, 'format' => 'csv']) }}">
                            <i class="bi bi-file-earmark-text text-secondary"></i> Export as CSV
                        </a>
                    </li>

                    <li><hr class="dropdown-divider my-1"></li>

                    {{-- ── Schedule PDF ──────────────────────────────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            Schedule
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'schedule', 'payroll_id' => $payroll->id, 'format' => 'pdf']) }}">
                            <i class="bi bi-file-earmark-pdf text-danger"></i> Schedule (PDF)
                        </a>
                    </li>

                    <li><hr class="dropdown-divider my-1"></li>

                    {{-- ── Month by Month Report ─────────────────────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            Month by Month Report
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.monthly_summary', ['business' => $business->slug, 'year' => $payroll->payrun_year, 'format' => 'xlsx']) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.monthly_summary', ['business' => $business->slug, 'year' => $payroll->payrun_year, 'format' => 'pdf']) }}">
                            <i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF
                        </a>
                    </li>

                    <li><hr class="dropdown-divider my-1"></li>

                    {{-- ── Grouped by Department ─────────────────────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            Grouped by Department
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'xlsx', 'group_by' => 'department']) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'pdf', 'group_by' => 'department']) }}">
                            <i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF
                        </a>
                    </li>

                    <li><hr class="dropdown-divider my-1"></li>

                    {{-- ── Grouped by Location ───────────────────────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            Grouped by Location
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'xlsx', 'group_by' => 'location']) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'pdf', 'group_by' => 'location']) }}">
                            <i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF
                        </a>
                    </li>

                    <li><hr class="dropdown-divider my-1"></li>

                    {{-- ── Grouped by Job Category ───────────────────────────────────── --}}
                    <li>
                        <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                            Grouped by Job Category
                        </h6>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'xlsx', 'group_by' => 'job_category']) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'pdf', 'group_by' => 'job_category']) }}">
                            <i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF
                        </a>
                    </li>

                </ul>
            </div>
            {{-- ── END NSSF Dropdown ──────────────────────────────────────────────── --}}
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                            id="exportReportsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Export Reports
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportReportsDropdown">
                            @foreach([
                            'basic_salary' => 'Basic Salary', 'gross_pay' => 'Gross Pay', 'net_pay' => 'Net Pay',
                            'overtime' => 'Overtime', 'shif' => 'SHIF', 'nssf' => 'NSSF', 'paye' => 'PAYE'
                            ] as $columnKey => $columnName)
                            <li><a class="dropdown-item download-column" href="#" data-column="{{ $columnKey }}"
                                    data-format="pdf">{{ $columnName }} (PDF)</a></li>
                            <li><a class="dropdown-item download-column" href="#" data-column="{{ $columnKey }}"
                                    data-format="csv">{{ $columnName }} (CSV)</a></li>
                            <li><a class="dropdown-item download-column" href="#" data-column="{{ $columnKey }}"
                                    data-format="xlsx">{{ $columnName }} (XLSX)</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <button class="btn btn-outline-info modern-btn" data-bs-toggle="modal"
                        data-bs-target="#analyticsModal">
                        <i class="bi bi-bar-chart-line me-1"></i> Payroll Analytics
                    </button>
                </div>
            </div>
            <div class="table-responsive shadow-sm rounded bg-white">
                <table class="table modern-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Basic Salary ({{ $payroll->currency ?? 'KES' }})</th>
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
                            <th>Net Pay</th>
                            <th>Bank Name</th>
                            <th>Account Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payroll->employeePayrolls as $index => $ep)
                        <?php
                        $deductions = json_decode($ep->deductions, true) ?? [];
                        $overtime = json_decode($ep->overtime, true) ?? ['amount' => 0];
                        $allowances = json_decode($ep->allowances, true) ?? [];
                        $reliefs = json_decode($ep->reliefs, true) ?? []; // Read from employee_payrolls.reliefs

                        $customDeductions = array_filter($deductions, fn($d) => !in_array($d['name'] ?? '', ['SHIF', 'NSSF', 'PAYE', 'Housing Levy', 'HELB', 'Loan Repayment', 'Advance Recovery', 'Absenteeism Charge']));
                        $totalCustomDeductions = array_sum(array_map(fn($d) => $d['amount'] ?? 0, $customDeductions));

                        // Prepare reliefs display for any type of relief
                        $reliefsDisplay = [];
                        foreach ($reliefs as $reliefKey => $reliefData) {
                            if (is_array($reliefData) && isset($reliefData['amount'])) {
                                $reliefName = ucwords(str_replace('-', ' ', $reliefKey));
                                $reliefsDisplay[] = "{$reliefName} (" . number_format($reliefData['amount'], 2) . ")";
                            }
                        }
                        $reliefsText = !empty($reliefsDisplay) ? implode(', ', $reliefsDisplay) : 'None';
                        ?>
                        <tr data-employee-payroll-id="{{ $ep->id }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $ep->employee->user->name ?? 'N/A' }}</td>
                            <td>{{ number_format($ep->basic_salary ?? 0, 2) }}</td>
                            <td>{{ collect($allowances)->map(fn($a) => "{$a['name']} (" . number_format($a['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}
                            </td>
                            <td>{{ number_format($overtime['amount'] ?? 0, 2) }}</td>
                            <td>{{ number_format($ep->gross_pay ?? 0, 2) }}</td>
                            <td>{{ number_format($ep->shif ?? ($deductions['shif'] ?? 0), 2) }}</td>
                            <td>{{ number_format($ep->nssf ?? ($deductions['nssf'] ?? 0), 2) }}</td>
                            <td>{{ number_format($ep->housing_levy ?? ($deductions['housing_levy'] ?? 0), 2) }}</td>
                            <td>{{ number_format($ep->helb ?? ($deductions['helb'] ?? 0), 2) }}</td>
                            <td>{{ number_format($ep->taxable_income ?? 0, 2) }}</td>
                            <td>{{ number_format($ep->paye_before_reliefs ?? 0, 2) }}</td>
                            <td>{{ $reliefsText }}</td>
                            <td>{{ number_format($ep->paye ?? 0, 2) }}</td>
                            <td>{{ number_format($totalCustomDeductions, 2) }}</td>
                            <td>{{ number_format($ep->advance_recovery ?? ($deductions['advance_recovery'] ?? 0), 2) }}
                            </td>
                            <td>{{ number_format($ep->loan_repayment ?? ($deductions['loan_repayment'] ?? 0), 2) }}</td>
                            <td>{{ number_format($ep->net_pay ?? 0, 2) }}</td>
                            <td>{{ $ep->bank_name ?? 'N/A' }}</td>
                            <td>{{ $ep->account_number ?? 'N/A' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-dark view-payslip"
                                    data-employee-id="{{ $ep->employee_id }}" data-payroll-id="{{ $payroll->id }}"
                                    data-employee-payroll-id="{{ $ep->id }}" title="View Payslip" data-bs-toggle="modal"
                                    data-bs-target="#payslipModal">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary email-payslip me-1"
                                    data-employee-payroll-id="{{ $ep->id }}" title="Email Payslip">
                                    <i class="fa fa-envelope"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success download-single-p9 me-1"
                                    data-employee-id="{{ $ep->employee_id }}" data-year="{{ $payroll->payrun_year }}"
                                    title="Download P9">
                                    <i class="fa fa-file-download"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end fw-bold">Totals:</td>
                            <td class="fw-bold">{{ number_format($totals['totalBasicSalary'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalAllowances'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalOvertime'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalGrossPay'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalShif'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalNssf'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalHousingLevy'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalHelb'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalTaxableIncome'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalPayeBeforeReliefs'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalReliefs'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalPaye'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalCustomDeductions'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalAdvances'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalLoans'], 2) }}</td>
                            <td class="fw-bold">{{ number_format($totals['totalNetPay'], 2) }}</td>
                            <td></td> <!-- Placeholder for Bank Name -->
                            <td></td> <!-- Placeholder for Account Number -->
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Footer and Action Buttons -->
        <div class="invoice-footer mt-5 p-4 bg-white shadow-sm rounded">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted small mb-5"><strong>Authorized By:</strong> ___________________________</p>
                    <p class="text-muted small mb-0"><strong>Date:</strong> ___________________________</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted small mb-1">Generated on: {{ now()->format('F d, Y H:i:s') }}</p>
                    <p class="text-muted small mb-0">For official use only.</p>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex flex-wrap align-items-center gap-2">
            <!-- Send Payslips Button -->
            <button class="btn btn-primary modern-btn flex-shrink-0" onclick="sendPayslips({{ $payroll->id }})">
                <i class="bi bi-envelope me-1"></i> Send Payslips
            </button>

            <!-- Download PDF Button -->
            <a href="{{ route('business.payroll.reports', ['business' => $business->slug, $entityType => $entity->slug, 'id' => $payroll->id, 'format' => 'pdf']) }}"
                class="btn btn-outline-secondary modern-btn flex-shrink-0">
                <i class="bi bi-file-earmark-pdf me-1"></i> Company Payslip PDF
            </a>

            <!-- Download CSV Button -->
            <a href="{{ route('business.payroll.reports', ['business' => $business->slug, $entityType => $entity->slug, 'id' => $payroll->id, 'format' => 'csv']) }}"
                class="btn btn-outline-secondary modern-btn flex-shrink-0">
                <i class="bi bi-file-earmark-text me-1"></i> Company Payslip CSV
            </a>

            <!-- Download XLSX Button -->
            <a href="{{ route('business.payroll.reports', ['business' => $business->slug, $entityType => $entity->slug, 'id' => $payroll->id, 'format' => 'xlsx']) }}"
                class="btn btn-outline-secondary modern-btn flex-shrink-0">
                <i class="bi bi-file-earmark-excel me-1"></i> Company Payslip XLSX
            </a>


             <!-- Download Table Footer as PDF Button -->
            <button class="btn btn-outline-secondary modern-btn flex-shrink-0" id="downloadTableFooterPdf">
                <i class="bi bi-file-earmark-pdf me-1"></i> Download Totals PDF
            </button>

            <!-- Download Bank Advice Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                    id="exportBankAdviceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Bank Advice
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportBankAdviceDropdown">
                    <li><a class="dropdown-item download-bank-advice" href="#" data-format="xlsx">Export as XLSX</a>
                    </li>
                    <li><a class="dropdown-item download-bank-advice" href="#" data-format="csv">Export as CSV</a></li>
                    <li><a class="dropdown-item download-bank-advice" href="#" data-format="pdf">Export as PDF</a></li>
                </ul>
            </div>



            {{-- ── Master Roll Download Dropdown ─────────────────────────────────── --}}
<div class="dropdown d-inline-flex">
 <button class="btn btn-outline-secondary modern-btn dropdown-toggle flex-shrink-0"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-auto-close="false"
        aria-expanded="false">

        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Master Roll
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-0" style="min-width: 260px; ">

        {{-- ── DETAILED ─────────────────────────────────────────────── --}}
        <li>
            <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                Detailed (All Columns)
            </h6>
        </li>

        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'xlsx',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Detailed in Excel
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'pdf',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Detailed in PDF
            </a>
        </li>

        <li><hr class="dropdown-divider my-1"></li>

        {{-- ── SUMMARY ──────────────────────────────────────────────── --}}
        <li>
            <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                Summary (Key Columns)
            </h6>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'xlsx',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Summary in Excel
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'pdf',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Summary in PDF
            </a>
        </li>

        <li><hr class="dropdown-divider my-1"></li>

        {{-- ── GROUP DETAILED by Location ──────────────────────────── --}}
        <li>
            <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                Group Detailed — by Location
            </h6>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'xlsx',
                    'groupBy'  => 'location',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Group detailed excel by location
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'xlsx',
                    'groupBy'  => 'department',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Group detailed excel by department
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'xlsx',
                    'groupBy'  => 'job_category',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Group detailed excel by job category
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'pdf',
                    'groupBy'  => 'location',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Group detailed PDF by location
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'pdf',
                    'groupBy'  => 'department',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Group detailed PDF by department
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'detailed',
                    'format'   => 'pdf',
                    'groupBy'  => 'job_category',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Group detailed PDF by job category
            </a>
        </li>

        <li><hr class="dropdown-divider my-1"></li>

        {{-- ── GROUP SUMMARY ────────────────────────────────────────── --}}
        <li>
            <h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">
                Group Summary
            </h6>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'xlsx',
                    'groupBy'  => 'location',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Group summary excel by location
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'xlsx',
                    'groupBy'  => 'department',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Group summary excel by department
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'xlsx',
                    'groupBy'  => 'job_category',
                ])
            }}">
                <i class="bi bi-file-earmark-excel text-success"></i> Group summary excel by job category
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'pdf',
                    'groupBy'  => 'location',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Group summary PDF by location
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'pdf',
                    'groupBy'  => 'department',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Group summary PDF by department
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="{{
                route('business.payroll.master-roll', [
                    'business' => $business->slug,
                    'id'       => $payroll->id,
                    'type'     => 'summary',
                    'format'   => 'pdf',
                    'groupBy'  => 'job_category',
                ])
            }}">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Group summary PDF by job category
            </a>
        </li>

    </ul>
</div>

            <!-- Download P9 Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="exportP9Dropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    Export P9
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportP9Dropdown">
                    <li><a class="dropdown-item download-p9" href="#" data-format="xlsx">Export as XLSX</a></li>
                    <li><a class="dropdown-item download-p9" href="#" data-format="csv">Export as CSV</a></li>
                    {{-- <li><a class="dropdown-item download-p9" href="#" data-format="pdf">Export as PDF</a></li> --}}
                </ul>
            </div>
        </div>

        <!-- Payroll Analytics Modal -->
        <div class="modal fade" id="analyticsModal" tabindex="-1" aria-labelledby="analyticsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="analyticsModalLabel">Payroll Analytics</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Pie Chart -->
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold text-center">Payroll Distribution</h6>
                                        <canvas id="payrollPieChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            <!-- Bar Chart -->
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold text-center">Payroll Totals</h6>
                                        <canvas id="payrollBarChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modern-btn"
                            data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payslip Modal -->
        <div class="modal fade" id="payslipModal" tabindex="-1" aria-labelledby="payslipModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="max-width: 550px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="payslipModalBody">
                        <p>Loading payslip...</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modern-btn"
                            data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
    .email-payslip,
    .view-payslip {
        padding: 4px 8px;
        font-size: 0.85rem;
    }

    .email-payslip i,
    .view-payslip i {
        font-size: 1rem;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .modern-table th,
    .modern-table td {
        white-space: nowrap;
    }

    .card {
        border: none;
        border-radius: 10px;
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-title {
        margin-bottom: 1rem;
        color: #333;
    }
    </style>
    @endpush

    @push('scripts')
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- jsPDF CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
     <!-- jsPDF autoTable Plugin CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <script>
    const payrollTotals = @json($totals);
    const payrollCurrency = '{{ $payroll->currency }}';

    function sendPayslips(payrollId) {
        const businessSlug = '{{ $business->slug }}';
        fetch(`/business/${businessSlug}/payroll/send-payslips`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    payroll_id: payrollId
                })
            })
            .then(response => response.ok ? response.json() : response.text().then(text => {
                throw new Error(text);
            }))
            .then(data => Swal.fire('Success!', data.message || 'Payslips queued for sending.', 'success'))
            .catch(error => Swal.fire('Error!', error.message || 'Failed to send payslips.', 'error'));
    }

    document.querySelectorAll('.email-payslip').forEach(button => {
        button.addEventListener('click', function() {
            const employeePayrollId = this.getAttribute('data-employee-payroll-id');
            const businessSlug = '{{ $business->slug }}';
            fetch(`/business/${businessSlug}/payroll/send-payslips`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        employee_payroll_id: employeePayrollId
                    })
                })
                .then(response => response.ok ? response.json() : response.text().then(text => {
                    throw new Error(text);
                }))
                .then(data => Swal.fire('Success!', data.message || 'Payslip queued for sending.',
                    'success'))
                .catch(error => Swal.fire('Error!', error.message || 'Failed to send payslip.',
                    'error'));
        });
    });

    document.querySelectorAll('.view-payslip').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.getAttribute('data-employee-id');
            const payrollId = this.getAttribute('data-payroll-id');
            const modalBody = document.getElementById('payslipModalBody');
            const businessSlug = '{{ $business->slug }}';
            modalBody.innerHTML = '<p>Loading payslip...</p>';
            fetch(`/business/${businessSlug}/payroll/payslip/${employeeId}?payroll_id=${payrollId}`)
                .then(response => response.ok ? response.text() : Promise.reject(
                    'Failed to load payslip'))
                .then(html => modalBody.innerHTML = html)
                .catch(error => modalBody.innerHTML =
                    '<p>Error loading payslip. Please try again.</p>');
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".download-column").forEach(item => {
            item.addEventListener("click", async function(e) {
                e.preventDefault();
                const column = this.getAttribute("data-column");
                const format = this.getAttribute("data-format");
                const businessSlug = '{{ $business->slug }}';
                const payrollId = '{{ $payroll->id }}';
                try {
                    const url =
                        `{{ route('business.payroll.download_column', ['business' => $business->slug, 'id' => $payroll->id, 'column' => ':column', 'format' => ':format']) }}`
                        .replace(':column', column)
                        .replace(':format', format);
                    window.location.href = url;
                } catch (error) {
                    toastr.error('Failed to initiate download. Please try again.');
                }
            });
        });

        // New Download P9 Handler
        document.querySelectorAll(".download-p9").forEach(item => {
            item.addEventListener("click", function(e) {
                e.preventDefault();
                const format = this.getAttribute("data-format");
                const businessSlug = '{{ $business->slug }}';
                const payrollId = '{{ $payroll->id }}';
                const url =
                    `{{ route('business.payroll.download_p9', ['business' => $business->slug, 'year' => $payroll->payrun_year, 'format' => ':format']) }}`
                    .replace(':format', format);
                window.location.href = url;
            });
        });

        const items = document.querySelectorAll('.download-bank-advice');
        if (items.length === 0) {
            console.error('No elements found with class "download-bank-advice"');
            return;
        }

        items.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                try {
                    const format = this.getAttribute('data-format');
                    const businessSlug = '{{ $business->slug ?? '
                    ' }}';
                    const month = '{{ $payroll->payrun_month ?? '
                    ' }}';
                    const year = '{{ $payroll->payrun_year ?? '
                    ' }}';

                    if (!businessSlug || !month || !year || !format) {
                        console.error('Missing required variables:', {
                            businessSlug,
                            month,
                            year,
                            format
                        });
                        return;
                    }

                    const url =
                        `{{ route('business.payroll.download_bank_advice', ['business' => ':business', 'year' => ':year', 'month' => ':month', 'format' => ':format']) }}`
                        .replace(':business', encodeURIComponent(businessSlug))
                        .replace(':year', encodeURIComponent(year))
                        .replace(':month', encodeURIComponent(month))
                        .replace(':format', encodeURIComponent(format));

                    console.log('Generated URL:', url);
                    window.location.href = url;
                } catch (error) {
                    console.error('Error generating bank advice URL:', error);
                }
            });
        });

        document.querySelectorAll('.download-single-p9').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const employeeId = this.getAttribute('data-employee-id');
                const year = this.getAttribute('data-year');
                const format = 'pdf';
                const businessSlug = '{{ $business->slug }}';
                const url =
                    `/business/${businessSlug}/payroll/p9/${employeeId}/${year}/${format}`;
                window.location.href = url;
            });
        });

         // Download Table Footer as PDF
        document.getElementById('downloadTableFooterPdf').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
             const doc = new jsPDF({ orientation: 'landscape' });

            doc.setFontSize(16);
            doc.text('Payroll Totals', 14, 20);
            doc.setFontSize(12);
            doc.text(`Company: {{ $entity->company_name ?? $entity->name ?? 'Default Company Name' }}`, 14, 30);
            doc.text(`Payroll Period: {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}`, 14, 40);
            doc.text(`Date: {{ now()->format('F d, Y') }}`, 14, 50);

            // table headers and data
            const headers = [
                'Basic Salary', 'Allowances', 'Overtime', 'Gross Pay', 'SHIF', 'NSSF', 'Housing Levy',
                'HELB', 'Taxable Income', 'PAYE (Before Reliefs)', 'Reliefs', 'PAYE', 'Deductions',
                'Advances', 'Loans', 'Net Pay'
            ];
            const data = [
                [
                    payrollTotals.totalBasicSalary.toFixed(2),
                    payrollTotals.totalAllowances.toFixed(2),
                    payrollTotals.totalOvertime.toFixed(2),
                    payrollTotals.totalGrossPay.toFixed(2),
                    payrollTotals.totalShif.toFixed(2),
                    payrollTotals.totalNssf.toFixed(2),
                    payrollTotals.totalHousingLevy.toFixed(2),
                    payrollTotals.totalHelb.toFixed(2),
                    payrollTotals.totalTaxableIncome.toFixed(2),
                    payrollTotals.totalPayeBeforeReliefs.toFixed(2),
                    payrollTotals.totalReliefs.toFixed(2),
                    payrollTotals.totalPaye.toFixed(2),
                    payrollTotals.totalCustomDeductions.toFixed(2),
                    payrollTotals.totalAdvances.toFixed(2),
                    payrollTotals.totalLoans.toFixed(2),
                    payrollTotals.totalNetPay.toFixed(2)
                ]
            ];

            // table to PDF
            doc.autoTable({
                head: [headers],
                body: data,
                startY: 60,
                styles: { fontSize: 10, cellPadding: 2 },
                headStyles: { fillColor: [100, 100, 100], textColor: [255, 255, 255] },
                columnStyles: {
                        0: { cellWidth: 16 },
                        1: { cellWidth: 16 },
                        2: { cellWidth: 16 },
                        3: { cellWidth: 16 },
                        4: { cellWidth: 16 },
                        5: { cellWidth: 16 },
                        6: { cellWidth: 16 },
                        7: { cellWidth: 16 },
                        8: { cellWidth: 16 },
                        9: { cellWidth: 16 },
                        10:{ cellWidth: 16 },
                        11:{ cellWidth: 16 },
                        12:{ cellWidth: 16 },
                        13:{ cellWidth: 16 },
                        14:{ cellWidth: 16 },
                        15:{ cellWidth: 16 }
                    },
                    theme: 'grid'
            });


            doc.save(`Payroll_Totals_${payrollTotals.payrollId || 'ID'}.pdf`);
        });

        // Chart.js Initialization
        const pieCtx = document.getElementById('payrollPieChart').getContext('2d');
        const barCtx = document.getElementById('payrollBarChart').getContext('2d');

        // Pie Chart - Payroll Distribution
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Basic Salary', 'Overtime', 'Statutory Deductions', 'Net Pay'],
                datasets: [{
                    data: [
                        payrollTotals.totalBasicSalary,
                        payrollTotals.totalOvertime,
                        payrollTotals.totalStatutoryDeductions,
                        payrollTotals.totalNetPay
                    ],
                    backgroundColor: [
                        '#4CAF50', // Green
                        '#FF9800', // Orange
                        '#F44336', // Red
                        '#2196F3' // Blue
                    ],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                            },
                            color: '#333'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += `${context.parsed.toFixed(2)} ${payrollCurrency}`;
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Bar Chart - Payroll Totals
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Gross Pay', 'Statutory Deductions', 'Net Pay'],
                datasets: [{
                    label: `Payroll Totals (${payrollCurrency})`,
                    data: [
                        payrollTotals.totalGrossPay,
                        payrollTotals.totalStatutoryDeductions,
                        payrollTotals.totalNetPay
                    ],
                    backgroundColor: [
                        '#4CAF50', // Green
                        '#F44336', // Red
                        '#2196F3' // Blue
                    ],
                    borderColor: [
                        '#388E3C',
                        '#D32F2F',
                        '#1976D2'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return `${value.toFixed(0)} ${payrollCurrency}`;
                            },
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 14
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += `${context.parsed.y.toFixed(2)} ${payrollCurrency}`;
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
    @endpush
</x-app-layout>

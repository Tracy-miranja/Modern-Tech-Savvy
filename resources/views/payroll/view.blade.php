<x-app-layout title="{{ $page }}">

    {{-- ════════════════════════════════════════════════════════════════
         PAYROLL VIEW — sticky first 2 cols + sticky Actions col
         + column-visibility toggle + scroll arrows
         ════════════════════════════════════════════════════════════════ --}}

    <div class="container py-5" id="payroll-document">

        <!-- ── Invoice Header ──────────────────────────────── -->
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
                        <span class="text-muted fw-bold">{{ strtoupper(substr($entity->company_name ?? $entity->name, 0, 1)) }}</span>
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
                    <p class="text-muted small mb-1">Payroll Period: {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}</p>
                    <p class="text-muted small mb-1">Payroll ID: {{ $payroll->id }}</p>
                    <p class="text-muted small mb-0">Date: {{ now()->format('F d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- ── Payroll Details Section ──────────────────────────────────── -->
        <div class="invoice-body">

            <!-- toolbar -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 class="h5 fw-bold mb-0 text-dark">Payroll Details</h4>
                <div class="d-flex gap-2 flex-wrap">

                    <!-- Variance & AI Analysis -->
                    <a href="{{ route('business.payroll.variance', ['business' => $business->slug]) }}"
                       class="btn btn-outline-secondary modern-btn flex-shrink-0">
                        <i class="bi bi-graph-up-arrow me-1"></i> Variance & AI Analysis
                    </a>

                    <!-- NSSF dropdown -->
                    <div class="dropdown d-inline-flex">
                        <button class="btn btn-outline-secondary modern-btn dropdown-toggle flex-shrink-0"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> NSSF
                        </button>
                        <ul class="dropdown-menu shadow-sm" style="min-width: 280px;">
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">New NSSF (Return/Remittance) Format</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'new_remittance', 'payroll_id' => $payroll->id, 'format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'new_remittance', 'payroll_id' => $payroll->id, 'format' => 'csv']) }}"><i class="bi bi-file-earmark-text text-secondary"></i> Export as CSV</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Up to June 2018</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'pre_2018', 'payroll_id' => $payroll->id, 'format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'pre_2018', 'payroll_id' => $payroll->id, 'format' => 'csv']) }}"><i class="bi bi-file-earmark-text text-secondary"></i> Export as CSV</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Old NSSF Format</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'old_format', 'payroll_id' => $payroll->id, 'format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'old_format', 'payroll_id' => $payroll->id, 'format' => 'csv']) }}"><i class="bi bi-file-earmark-text text-secondary"></i> Export as CSV</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Schedule</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'schedule', 'payroll_id' => $payroll->id, 'format' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Schedule (PDF)</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Month by Month Report</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('business.payroll.nssf.monthly_summary', ['business' => $business->slug, 'year' => $payroll->payrun_year, 'format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('business.payroll.nssf.monthly_summary', ['business' => $business->slug, 'year' => $payroll->payrun_year, 'format' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Grouped by Department</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'xlsx', 'group_by' => 'department']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'pdf', 'group_by' => 'department']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Grouped by Location</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'xlsx', 'group_by' => 'location']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'pdf', 'group_by' => 'location']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Grouped by Job Category</h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'xlsx', 'group_by' => 'job_category']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Export as XLSX</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 nssf-dl" href="#" data-base="{{ route('business.payroll.nssf.download', ['business' => $business->slug, 'format_type' => 'grouped', 'payroll_id' => $payroll->id, 'format' => 'pdf', 'group_by' => 'job_category']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Export as PDF</a></li>
                        </ul>
                    </div>

                    <!-- Export Reports -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                            id="exportReportsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Export Reports
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportReportsDropdown">
                            @foreach(['basic_salary' => 'Basic Salary','gross_pay' => 'Gross Pay','net_pay' => 'Net Pay','overtime' => 'Overtime','shif' => 'SHIF','nssf' => 'NSSF','paye' => 'PAYE'] as $columnKey => $columnName)
                            <li><a class="dropdown-item download-column" href="#" data-column="{{ $columnKey }}" data-format="pdf">{{ $columnName }} (PDF)</a></li>
                            <li><a class="dropdown-item download-column" href="#" data-column="{{ $columnKey }}" data-format="csv">{{ $columnName }} (CSV)</a></li>
                            <li><a class="dropdown-item download-column" href="#" data-column="{{ $columnKey }}" data-format="xlsx">{{ $columnName }} (XLSX)</a></li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Column Visibility Toggle -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                            id="colVisDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="bi bi-layout-three-columns me-1"></i> Columns
                        </button>
                        <ul class="dropdown-menu px-2 py-2" style="min-width:200px;" aria-labelledby="colVisDropdown" id="colVisMenu">
                            <!-- populated by JS -->
                        </ul>
                    </div>

                    <!-- Payroll Analytics -->
                    <button class="btn btn-outline-info modern-btn" data-bs-toggle="modal" data-bs-target="#analyticsModal">
                        <i class="bi bi-bar-chart-line me-1"></i> Payroll Analytics
                    </button>
                </div>
            </div>

            <!-- scroll hint banner -->
            <div id="scrollHint" class="d-flex align-items-center gap-2 text-muted small mb-2 px-1" style="opacity:1;transition:opacity .4s;">
                <i class="bi bi-arrow-left-right"></i>
                <span>Scroll the table left / right — <strong>#</strong> and <strong>Employee</strong> columns and <strong>Actions</strong> stay pinned.</span>
                <button type="button" class="btn-close btn-close-sm ms-auto" style="font-size:.65rem;" onclick="document.getElementById('scrollHint').style.opacity=0;"></button>
            </div>

            <div class="payroll-scroll" id="payrollTableScroll">
                    <table class="table modern-table table-hover mb-0" id="payrollTable">
                        <thead>
                            <tr>
                                <th class="col-pin-1">#</th>
                                <th class="col-pin-2">Employee</th>
                                <th data-col="basic_salary">Basic Salary ({{ $payroll->currency ?? 'KES' }})</th>
                                <th data-col="allowances">Allowances</th>
                                <th data-col="overtime">Overtime</th>
                                <th data-col="gross_pay">Gross Pay</th>
                                <th data-col="shif">SHIF</th>
                                <th data-col="nssf">NSSF</th>
                                <th data-col="housing_levy">Housing Levy</th>
                                <th data-col="helb">HELB</th>
                                <th data-col="taxable_income">Taxable Income</th>
                                <th data-col="paye_before">PAYE (Before Reliefs)</th>
                                <th data-col="reliefs">Reliefs</th>
                                <th data-col="paye">PAYE</th>
                                <th data-col="deductions">Deductions</th>
                                <th data-col="advances">Advances</th>
                                <th data-col="loans">Loans</th>
                                <th data-col="benefits">Reimbursement</th>
                                <th data-col="net_pay">Net Pay</th>
                                <th data-col="bank_name">Bank Name</th>
                                <th data-col="account_number">Account Number</th>
                                <th class="col-pin-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payroll->employeePayrolls as $index => $ep)
                            <?php
                            $deductions = json_decode($ep->deductions, true) ?? [];
                            $overtime   = json_decode($ep->overtime,   true) ?? ['amount' => 0];
                            $allowances = json_decode($ep->allowances, true) ?? [];
                            $taxableAllowancesDisplay = array_filter($allowances, fn($a) => is_array($a) && ($a['is_taxable'] ?? false) && !($a['is_employer_contribution'] ?? false));
$benefitsDisplay = array_filter($allowances, fn($a) => is_array($a) && !($a['is_taxable'] ?? false) && !($a['is_employer_contribution'] ?? false));
                            $reliefs    = json_decode($ep->reliefs,    true) ?? [];

                            $customDeductions = array_filter($deductions, fn($d) => !in_array($d['name'] ?? '', ['SHIF', 'NSSF', 'PAYE', 'Housing Levy', 'HELB', 'Loan Repayment', 'Advance Recovery', 'Absenteeism Charge']));
                            $totalCustomDeductions = array_sum(array_map(fn($d) => $d['amount'] ?? 0, $customDeductions));

                            $reliefsDisplay = [];
                            foreach ($reliefs as $reliefKey => $reliefData) {
                                if (is_array($reliefData) && isset($reliefData['amount'])) {
                                    $reliefName = ucwords(str_replace('-', ' ', $reliefKey));
                                    $displayAmt = $reliefData['display_amount'] ?? $reliefData['amount'];
                                    $reliefsDisplay[] = "{$reliefName} (" . number_format($displayAmt, 2) . ")";
                                }
                            }
                            $reliefsText = !empty($reliefsDisplay) ? implode(', ', $reliefsDisplay) : 'None';
                            ?>
                            <tr data-employee-payroll-id="{{ $ep->id }}">
                                <td class="col-pin-1">{{ $index + 1 }}</td>
                                <td class="col-pin-2">{{ $ep->employee->user->name ?? 'N/A' }}</td>
                                <td data-col="basic_salary">{{ number_format($ep->basic_salary ?? 0, 2) }}</td>
                                <td data-col="allowances">{{ collect($taxableAllowancesDisplay)->map(fn($a) => "{$a['name']} (" . number_format($a['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}</td>

                                {{-- <td data-col="allowances">{{ collect($allowances)->map(fn($a) => "{$a['name']} (" . number_format($a['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}</td> --}}
                                <td data-col="overtime">{{ number_format($overtime['amount'] ?? 0, 2) }}</td>
                                <td data-col="gross_pay">{{ number_format($ep->gross_pay ?? 0, 2) }}</td>
                                <td data-col="shif">{{ number_format($ep->shif ?? ($deductions['shif'] ?? 0), 2) }}</td>
                                <td data-col="nssf">{{ number_format($ep->nssf ?? ($deductions['nssf'] ?? 0), 2) }}</td>
                                <td data-col="housing_levy">{{ number_format($ep->housing_levy ?? ($deductions['housing_levy'] ?? 0), 2) }}</td>
                                <td data-col="helb">{{ number_format($ep->helb ?? ($deductions['helb'] ?? 0), 2) }}</td>
                                <td data-col="taxable_income">{{ number_format($ep->taxable_income ?? 0, 2) }}</td>
                                <td data-col="paye_before">{{ number_format($ep->paye_before_reliefs ?? 0, 2) }}</td>
                                <td data-col="reliefs">{{ $reliefsText }}</td>
                                <td data-col="paye">{{ number_format($ep->paye ?? 0, 2) }}</td>
                                <td data-col="deductions">{{ number_format($totalCustomDeductions, 2) }}</td>
                                <td data-col="advances">{{ number_format($ep->advance_recovery ?? ($deductions['advance_recovery'] ?? 0), 2) }}</td>
                                <td data-col="loans">{{ number_format($ep->loan_repayment ?? ($deductions['loan_repayment'] ?? 0), 2) }}</td>
                                <td data-col="benefits">{{ collect($benefitsDisplay)->map(fn($a) => "{$a['name']} (" . number_format($a['amount'] ?? 0, 2) . ")")->implode(', ') ?: 'None' }}</td>
                                <td data-col="net_pay">{{ number_format($ep->net_pay ?? 0, 2) }}</td>
                                <td data-col="bank_name">{{ $ep->bank_name ?? 'N/A' }}</td>
                                <td data-col="account_number">{{ $ep->account_number ?? 'N/A' }}</td>
                                <td class="col-pin-right">
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-dark view-payslip"
                                            data-employee-id="{{ $ep->employee_id }}"
                                            data-payroll-id="{{ $payroll->id }}"
                                            data-employee-payroll-id="{{ $ep->id }}"
                                            title="View Payslip"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payslipModal">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary email-payslip"
                                            data-employee-payroll-id="{{ $ep->id }}"
                                            title="Email Payslip">
                                            <i class="fa fa-envelope"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success download-single-p9"
                                            data-employee-id="{{ $ep->employee_id }}"
                                            data-year="{{ $payroll->payrun_year }}"
                                            title="Download P9">
                                            <i class="fa fa-file-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td class="col-pin-1 fw-bold"></td>
                                <td class="col-pin-2 fw-bold text-end">Totals:</td>
                                <td data-col="basic_salary">{{ number_format($totals['totalBasicSalary'], 2) }}</td>
                               <td data-col="allowances">{{ number_format($totals['totalAllowances'], 2) }}</td>

                                <td data-col="overtime">{{ number_format($totals['totalOvertime'], 2) }}</td>
                                <td data-col="gross_pay">{{ number_format($totals['totalGrossPay'], 2) }}</td>
                                <td data-col="shif">{{ number_format($totals['totalShif'], 2) }}</td>
                                <td data-col="nssf">{{ number_format($totals['totalNssf'], 2) }}</td>
                                <td data-col="housing_levy">{{ number_format($totals['totalHousingLevy'], 2) }}</td>
                                <td data-col="helb">{{ number_format($totals['totalHelb'], 2) }}</td>
                                <td data-col="taxable_income">{{ number_format($totals['totalTaxableIncome'], 2) }}</td>
                                <td data-col="paye_before">{{ number_format($totals['totalPayeBeforeReliefs'], 2) }}</td>
                                <td data-col="reliefs">{{ number_format($totals['totalReliefs'], 2) }}</td>
                                <td data-col="paye">{{ number_format($totals['totalPaye'], 2) }}</td>
                                <td data-col="deductions">{{ number_format($totals['totalCustomDeductions'], 2) }}</td>
                                <td data-col="advances">{{ number_format($totals['totalAdvances'], 2) }}</td>
                                <td data-col="loans">{{ number_format($totals['totalLoans'], 2) }}</td>
                                <td data-col="benefits">{{ number_format($totals['totalBenefits'], 2) }}</td>
                                <td data-col="net_pay">{{ number_format($totals['totalNetPay'], 2) }}</td>
                                <td data-col="bank_name"></td>
                                <td data-col="account_number"></td>
                                <td class="col-pin-right"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div><!-- /payroll-scroll -->

            <!-- Scroll arrows -->
            <div class="d-flex justify-content-end gap-2 mt-2" id="scrollArrows">
                <button class="btn btn-outline-secondary btn-sm px-2" id="scrollLeft" title="Scroll left">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm px-2" id="scrollRight" title="Scroll right">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div><!-- /invoice-body -->

        <!-- ── Footer & Action Buttons ──────────────────────────────── -->
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
            <button class="btn btn-primary modern-btn flex-shrink-0" onclick="sendPayslips({{ $payroll->id }})">
                <i class="bi bi-envelope me-1"></i> Send Payslips
            </button>
            <a href="#" class="btn btn-outline-secondary modern-btn flex-shrink-0 payroll-dl"
                data-base="{{ route('business.payroll.reports', ['business' => $business->slug, $entityType => $entity->slug, 'id' => $payroll->id, 'format' => 'pdf']) }}">
                <i class="bi bi-file-earmark-pdf me-1"></i> Company Payslip PDF
            </a>
            <a href="#" class="btn btn-outline-secondary modern-btn flex-shrink-0 payroll-dl"
                data-base="{{ route('business.payroll.reports', ['business' => $business->slug, $entityType => $entity->slug, 'id' => $payroll->id, 'format' => 'csv']) }}">
                <i class="bi bi-file-earmark-text me-1"></i> Company Payslip CSV
            </a>
            <a href="#" class="btn btn-outline-secondary modern-btn flex-shrink-0 payroll-dl"
                data-base="{{ route('business.payroll.reports', ['business' => $business->slug, $entityType => $entity->slug, 'id' => $payroll->id, 'format' => 'xlsx']) }}">
                <i class="bi bi-file-earmark-excel me-1"></i> Company Payslip XLSX
            </a>
            <button class="btn btn-outline-secondary modern-btn flex-shrink-0" id="downloadTableFooterPdf">
                <i class="bi bi-file-earmark-pdf me-1"></i> Download Totals PDF
            </button>

            <!-- Bank Advice -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                    id="exportBankAdviceDropdown" data-bs-toggle="dropdown" aria-expanded="false">Bank Advice</button>
                <ul class="dropdown-menu" aria-labelledby="exportBankAdviceDropdown">
                    <li><a class="dropdown-item download-bank-advice" href="#" data-format="xlsx">Export as XLSX</a></li>
                    <li><a class="dropdown-item download-bank-advice" href="#" data-format="csv">Export as CSV</a></li>
                    <li><a class="dropdown-item download-bank-advice" href="#" data-format="pdf">Export as PDF</a></li>
                </ul>
            </div>

            <!-- Master Roll -->
            <div class="dropdown d-inline-flex">
                <button class="btn btn-outline-secondary modern-btn dropdown-toggle flex-shrink-0"
                    type="button" data-bs-toggle="dropdown" data-bs-auto-close="false" aria-expanded="false">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Master Roll
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-0" style="min-width: 260px;">
                    <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Detailed (All Columns)</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Detailed in Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Detailed in PDF</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Summary (Key Columns)</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Summary in Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'pdf']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Summary in PDF</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Group Detailed — by Location</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'xlsx', 'groupBy' => 'location']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Group detailed excel by location</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'xlsx', 'groupBy' => 'department']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Group detailed excel by department</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'xlsx', 'groupBy' => 'job_category']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Group detailed excel by job category</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'pdf', 'groupBy' => 'location']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Group detailed PDF by location</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'pdf', 'groupBy' => 'department']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Group detailed PDF by department</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'detailed', 'format' => 'pdf', 'groupBy' => 'job_category']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Group detailed PDF by job category</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size:.7rem; color:#1a1a2e;">Group Summary</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'xlsx', 'groupBy' => 'location']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Group summary excel by location</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'xlsx', 'groupBy' => 'department']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Group summary excel by department</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'xlsx', 'groupBy' => 'job_category']) }}"><i class="bi bi-file-earmark-excel text-success"></i> Group summary excel by job category</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'pdf', 'groupBy' => 'location']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Group summary PDF by location</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'pdf', 'groupBy' => 'department']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Group summary PDF by department</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 master-roll-dl" href="#" data-base="{{ route('business.payroll.master-roll', ['business' => $business->slug, 'id' => $payroll->id, 'type' => 'summary', 'format' => 'pdf', 'groupBy' => 'job_category']) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> Group summary PDF by job category</a></li>
                </ul>
            </div>

            <!-- Export P9 -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="exportP9Dropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">Export P9</button>
                <ul class="dropdown-menu" aria-labelledby="exportP9Dropdown">
                    <li><a class="dropdown-item download-p9" href="#" data-format="xlsx">Export as XLSX</a></li>
                    <li><a class="dropdown-item download-p9" href="#" data-format="csv">Export as CSV</a></li>
                </ul>
            </div>
        </div>

        <!-- ── Analytics Modal ──────────────────────────────────────────── -->
        <div class="modal fade" id="analyticsModal" tabindex="-1" aria-labelledby="analyticsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="analyticsModalLabel">Payroll Analytics</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold text-center">Payroll Distribution</h6>
                                        <canvas id="payrollPieChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
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
                        <button type="button" class="btn btn-secondary modern-btn" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Payslip Modal ────────────────────────────────────────────── -->
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
                        <button type="button" class="btn btn-secondary modern-btn" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /container -->

    @push('styles')
    <style>
    /* ── Scroll wrapper ── */
    .payroll-scroll {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
        background: #fff;
    }
    .payroll-scroll > table {
        margin-bottom: 0;
        /* border-separate required for sticky + visible borders */
        border-collapse: separate;
        border-spacing: 0;
        min-width: max-content;
    }
    .payroll-scroll th,
    .payroll-scroll td {
        white-space: nowrap;
        font-size: .85rem;
        padding: .5rem .75rem;
        vertical-align: middle;
        border-bottom: 1px solid #dee2e6;
    }
    .payroll-scroll thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }
    .payroll-scroll tfoot td {
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
        font-weight: 700;
    }

    /* ── Sticky left: # column ── */
    .col-pin-1 {
        position: sticky;
        left: 0;
        z-index: 2;
        background: #fff;
        min-width: 42px;
    }
    /* ── Sticky left: Employee column ── */
    .col-pin-2 {
        position: sticky;
        left: 42px;
        z-index: 2;
        background: #fff;
        min-width: 140px;
        /* shadow to show separation from scrolling columns */
        box-shadow: 3px 0 6px -3px rgba(0,0,0,.18);
    }
    /* ── Sticky right: Actions column ── */
    .col-pin-right {
        position: sticky;
        right: 0;
        z-index: 2;
        background: #fff;
        min-width: 110px;
        box-shadow: -3px 0 6px -3px rgba(0,0,0,.18);
    }
    /* Header/footer rows need higher z-index so they sit above body cells */
    thead .col-pin-1,
    thead .col-pin-2,
    thead .col-pin-right { background: #f8f9fa; z-index: 3; }
    tfoot .col-pin-1,
    tfoot .col-pin-2,
    tfoot .col-pin-right { background: #f8f9fa; z-index: 3; }

    /* Hover — must also re-state background on sticky cells */
    .payroll-scroll tbody tr:hover td { background: #f0f4ff; }
    .payroll-scroll tbody tr:hover .col-pin-1,
    .payroll-scroll tbody tr:hover .col-pin-2,
    .payroll-scroll tbody tr:hover .col-pin-right { background: #f0f4ff; }

    /* ── Column-visibility hidden utility ── */
    .col-hidden { display: none !important; }

    /* ── Misc ── */
    #scrollArrows .btn { line-height: 1.2; }
    #scrollHint { transition: opacity .4s ease; }
    #colVisMenu .form-check { padding-left: 1.7rem; margin-bottom: .15rem; }
    #colVisMenu .form-check-label { font-size: .83rem; cursor: pointer; user-select: none; }
    .email-payslip, .view-payslip { padding: 4px 8px; font-size: 0.85rem; }
    .card { border: none; border-radius: 10px; }
    .card-body { padding: 1.5rem; }
    .card-title { margin-bottom: 1rem; color: #333; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <script>
    const payrollTotals   = @json($totals);
    const payrollCurrency = '{{ $payroll->currency }}';

    const appendViewFilters = function (baseUrl) {
        const src = new URLSearchParams(window.location.search);
        const out = new URLSearchParams();
        ['location', 'department', 'job_category'].forEach(function (key) {
            const v = src.get(key);
            if (v && v.trim() !== '') out.set(key, v);
        });
        const qs = out.toString();
        return qs ? baseUrl + (baseUrl.includes('?') ? '&' : '?') + qs : baseUrl;
    };

    function sendPayslips(payrollId) {
        fetch(`/business/{{ $business->slug }}/payroll/send-payslips`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ payroll_id: payrollId })
        })
        .then(r => r.ok ? r.json() : r.text().then(t => { throw new Error(t); }))
        .then(d => Swal.fire('Success!', d.message || 'Payslips queued for sending.', 'success'))
        .catch(e => Swal.fire('Error!', e.message || 'Failed to send payslips.', 'error'));
    }

    document.querySelectorAll('.email-payslip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch(`/business/{{ $business->slug }}/payroll/send-payslips`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ employee_payroll_id: this.getAttribute('data-employee-payroll-id') })
            })
            .then(r => r.ok ? r.json() : r.text().then(t => { throw new Error(t); }))
            .then(d => Swal.fire('Success!', d.message || 'Payslip queued for sending.', 'success'))
            .catch(e => Swal.fire('Error!', e.message || 'Failed to send payslip.', 'error'));
        });
    });

    document.querySelectorAll('.view-payslip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const employeeId = this.getAttribute('data-employee-id');
            const payrollId  = this.getAttribute('data-payroll-id');
            const modalBody  = document.getElementById('payslipModalBody');
            modalBody.innerHTML = '<div class="d-flex justify-content-center py-4"><div class="spinner-border text-secondary"></div></div>';
            fetch(`/business/{{ $business->slug }}/payroll/payslip/${employeeId}?payroll_id=${payrollId}`)
                .then(r => r.ok ? r.text() : r.text().then(t => { throw new Error('Server error: ' + t.substring(0,200)); }))
                .then(html => { modalBody.innerHTML = html; })
                .catch(err => { modalBody.innerHTML = '<div class="p-3 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + err.message + '</div>'; });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {

        const scrollBox = document.getElementById('payrollTableScroll');

        /* ── Scroll arrows ── */
        document.getElementById('scrollLeft').addEventListener('click', function () {
            scrollBox.scrollBy({ left: -300, behavior: 'smooth' });
        });
        document.getElementById('scrollRight').addEventListener('click', function () {
            scrollBox.scrollBy({ left: 300, behavior: 'smooth' });
        });
        scrollBox.addEventListener('scroll', function () {
            document.getElementById('scrollHint').style.opacity = '0';
        }, { once: true });

        /* ── Column Visibility ── */
        const colDefs = [
            { key: 'basic_salary',    label: 'Basic Salary',          default: true  },
            // { key: 'allowances',      label: 'Allowances',            default: true  },
            { key: 'allowances',      label: 'Allowances',            default: true  },
{ key: 'benefits',        label: 'Benefits',              default: true  },
            { key: 'overtime',        label: 'Overtime',              default: true  },
            { key: 'gross_pay',       label: 'Gross Pay',             default: true  },
            { key: 'shif',            label: 'SHIF',                  default: true  },
            { key: 'nssf',            label: 'NSSF',                  default: true  },
            { key: 'housing_levy',    label: 'Housing Levy',          default: true  },
            { key: 'helb',            label: 'HELB',                  default: false },
            { key: 'taxable_income',  label: 'Taxable Income',        default: false },
            { key: 'paye_before',     label: 'PAYE (Before Reliefs)', default: false },
            { key: 'reliefs',         label: 'Reliefs',               default: true  },
            { key: 'paye',            label: 'PAYE',                  default: true  },
            { key: 'deductions',      label: 'Deductions',            default: true  },
            { key: 'advances',        label: 'Advances',              default: true  },
            { key: 'loans',           label: 'Loans',                 default: true  },
            { key: 'net_pay',         label: 'Net Pay',               default: true  },
            { key: 'bank_name',       label: 'Bank Name',             default: true  },
            { key: 'account_number',  label: 'Account Number',        default: true  },
        ];
        const menu = document.getElementById('colVisMenu');
        const setColVisible = function (key, visible) {
            document.querySelectorAll(`[data-col="${key}"]`).forEach(function (el) {
                el.classList.toggle('col-hidden', !visible);
            });
        };
        const savedVis = JSON.parse(localStorage.getItem('payrollColVis') || 'null');
        colDefs.forEach(function (col) {
            const visible = savedVis ? (savedVis[col.key] !== false) : col.default;
            if (!visible) setColVisible(col.key, false);
            const li = document.createElement('li');
            li.className = 'px-1 py-0';
            li.innerHTML = `<div class="form-check"><input class="form-check-input" type="checkbox" value="${col.key}" id="colvis_${col.key}" ${visible ? 'checked' : ''}><label class="form-check-label user-select-none" for="colvis_${col.key}">${col.label}</label></div>`;
            menu.appendChild(li);
            li.querySelector('input').addEventListener('change', function () {
                setColVisible(col.key, this.checked);
                const state = {};
                document.querySelectorAll('#colVisMenu input[type=checkbox]').forEach(function (cb) { state[cb.value] = cb.checked; });
                localStorage.setItem('payrollColVis', JSON.stringify(state));
            });
        });

        /* ── Download links ── */
        document.querySelectorAll('.payroll-dl').forEach(function (a) {
            a.addEventListener('click', function (e) { e.preventDefault(); window.location.href = appendViewFilters(this.dataset.base); });
        });
        document.querySelectorAll('.nssf-dl').forEach(function (a) {
            a.addEventListener('click', function (e) { e.preventDefault(); window.location.href = appendViewFilters(this.dataset.base); });
        });
        document.querySelectorAll('.master-roll-dl').forEach(function (a) {
            a.addEventListener('click', function (e) { e.preventDefault(); window.location.href = appendViewFilters(this.dataset.base); });
        });
        document.querySelectorAll('.download-column').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const base = `{{ route('business.payroll.download_column', ['business' => $business->slug, 'id' => $payroll->id, 'column' => ':column', 'format' => ':format']) }}`
                    .replace(':column', this.getAttribute('data-column'))
                    .replace(':format', this.getAttribute('data-format'));
                window.location.href = appendViewFilters(base);
            });
        });
        document.querySelectorAll('.download-p9').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                window.location.href = `{{ route('business.payroll.download_p9', ['business' => $business->slug, 'year' => $payroll->payrun_year, 'format' => ':format']) }}`
                    .replace(':format', this.getAttribute('data-format'));
            });
        });
        document.querySelectorAll('.download-bank-advice').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const base = `{{ route('business.payroll.download_bank_advice', ['business' => ':business', 'year' => ':year', 'month' => ':month', 'format' => ':format']) }}`
                    .replace(':business', encodeURIComponent('{{ $business->slug }}'))
                    .replace(':year',     encodeURIComponent('{{ $payroll->payrun_year }}'))
                    .replace(':month',    encodeURIComponent('{{ $payroll->payrun_month }}'))
                    .replace(':format',   encodeURIComponent(this.getAttribute('data-format')));
                window.location.href = appendViewFilters(base);
            });
        });
        document.querySelectorAll('.download-single-p9').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                window.location.href = `/business/{{ $business->slug }}/payroll/p9/${this.getAttribute('data-employee-id')}/${this.getAttribute('data-year')}/pdf`;
            });
        });

        /* ── Download Totals PDF ── */
        document.getElementById('downloadTableFooterPdf').addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape' });
            doc.setFontSize(16); doc.text('Payroll Totals', 14, 20);
            doc.setFontSize(12);
            doc.text('Company: {{ addslashes($entity->company_name ?? $entity->name ?? "Company") }}', 14, 30);
            doc.text('Payroll Period: {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}', 14, 40);
            doc.text('Date: {{ now()->format("F d, Y") }}', 14, 50);
            const headers = ['Basic Salary','Allowances','Overtime','Gross Pay','SHIF','NSSF','Housing Levy','HELB','Taxable Income','PAYE (Before Reliefs)','Reliefs','PAYE','Deductions','Advances','Loans','Net Pay'];
            const data = [[
                payrollTotals.totalBasicSalary.toFixed(2),       payrollTotals.totalAllowances.toFixed(2),
                payrollTotals.totalOvertime.toFixed(2),          payrollTotals.totalGrossPay.toFixed(2),
                payrollTotals.totalShif.toFixed(2),              payrollTotals.totalNssf.toFixed(2),
                payrollTotals.totalHousingLevy.toFixed(2),       payrollTotals.totalHelb.toFixed(2),
                payrollTotals.totalTaxableIncome.toFixed(2),     payrollTotals.totalPayeBeforeReliefs.toFixed(2),
                payrollTotals.totalReliefs.toFixed(2),           payrollTotals.totalPaye.toFixed(2),
                payrollTotals.totalCustomDeductions.toFixed(2),  payrollTotals.totalAdvances.toFixed(2),
                payrollTotals.totalLoans.toFixed(2),             payrollTotals.totalNetPay.toFixed(2)
            ]];
            doc.autoTable({ head: [headers], body: data, startY: 60,
                styles: { fontSize: 9, cellPadding: 2 },
                headStyles: { fillColor: [100,100,100], textColor: [255,255,255] },
                columnStyles: Object.fromEntries(Array.from({length:16},(_,i)=>[i,{cellWidth:17}])),
                theme: 'grid' });
            doc.save(`Payroll_Totals_{{ $payroll->id }}.pdf`);
        });

        /* ── Charts ── */
        new Chart(document.getElementById('payrollPieChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Basic Salary','Overtime','Statutory Deductions','Net Pay'],
                datasets: [{ data: [payrollTotals.totalBasicSalary,payrollTotals.totalOvertime,payrollTotals.totalStatutoryDeductions,payrollTotals.totalNetPay], backgroundColor: ['#4CAF50','#FF9800','#F44336','#2196F3'], borderWidth:1, borderColor:'#fff' }]
            },
            options: { responsive:true, plugins:{ legend:{position:'top'}, tooltip:{ callbacks:{ label: ctx=>`${ctx.label}: ${ctx.parsed.toFixed(2)} ${payrollCurrency}` } } } }
        });
        new Chart(document.getElementById('payrollBarChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Gross Pay','Statutory Deductions','Net Pay'],
                datasets: [{ label:`Payroll Totals (${payrollCurrency})`, data:[payrollTotals.totalGrossPay,payrollTotals.totalStatutoryDeductions,payrollTotals.totalNetPay], backgroundColor:['#4CAF50','#F44336','#2196F3'], borderColor:['#388E3C','#D32F2F','#1976D2'], borderWidth:1 }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero:true, ticks:{callback:v=>`${v.toFixed(0)} ${payrollCurrency}`}, grid:{color:'rgba(0,0,0,0.1)'} },
                    x: { ticks:{font:{size:14}}, grid:{display:false} }
                },
                plugins: { legend:{display:false}, tooltip:{callbacks:{label:ctx=>`${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)} ${payrollCurrency}`}} }
            }
        });

    }); // end DOMContentLoaded
    </script>
    @endpush
</x-app-layout>

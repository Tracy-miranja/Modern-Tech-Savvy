<x-app-layout>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body mb-3">
                    <form action="" id="selectPayrollForm">
                        <div class="form-group">
                            <label for="payroll_id">Select a payroll for Downloads</label>
                            <select name="payroll_id" id="payroll_id" class="form-select">
                                @foreach($payrolls as $payroll)
                                    <option value="{{ $payroll->id }}">{{ $payroll->payroll_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-12">

            <div class="card mb-3">
                <div class="card-header">ITAX Payroll Downloads</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <button type="button" onclick="download(this)" data-name="bankAdviceTemplate" class="btn w-100 btn-primary" > <i class="fa-solid fa-download me-2"></i> Bank Advice Template</button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" onclick="download(this)" data-name="payrollReport" class="btn w-100 btn-primary" > <i class="fa-solid fa-download me-2"></i> Payroll Report Template</button>
                        </div>
                        <div class="col-md-4">
                            <a href="" class="btn w-100 btn-primary"> <i class="fa-solid fa-download me-2"></i> ITax Employee Details (Return/Remittance)</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn w-100 btn-primary"> <i class="fa-solid fa-download me-2"></i> KRA ITAX Return Schedule</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn w-100 btn-primary"> <i class="fa-solid fa-download me-2"></i> KRA ITAX Return Schedule (PDF)</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn w-100 btn-secondary"> <i class="fa-solid fa-download me-2"></i> Month by month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn w-100 btn-secondary"> <i class="fa-solid fa-download me-2"></i> Grouped by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn w-100 btn-secondary"> <i class="fa-solid fa-download me-2"></i> Grouped by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn w-100 btn-secondary"> <i class="fa-solid fa-download me-2"></i> Grouped by job category</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">SHIF/NHIF</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNhif" title="Export SHIF/NHIF report">SHIF/NHIF (Return/Remittance) report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNhif" title="SHIF/NHIF Schedule">SHIF/NHIF Schedule</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNhif" title="SHIF/NHIF Schedule">SHIF/NHIF Schedule (PDF)</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Month by month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by job category</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">NSSF</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNssf" title="Export NSSF report">New NSSF (Return/Remittance) format</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNssf" title="Export NSSF report">Up to June 2018</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNssf" title="Export NSSF report">Old NSSF format</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNssf" title="Export NSSF report">Schedule (PDF)</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Month by month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by job category</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Housing Levy</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idHousingLevy" title="Export housing Levy report">Housing Levy (KRA Return/Remittance) import report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idHousingLevy" title="payroll::payroll.view.actions.housing_levy.schedule_desc">Housing Levy Schedule</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idHousingLevy" title="payroll::payroll.view.actions.housing_levy.schedule_desc">Housing Levy report (PDF)</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Month by month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by job category</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Bank & Cash files</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Generic / General Bank report">Generic / General Bank report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Generic/General Bank report (PDF)">Generic/General Bank report (PDF)</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="payroll::payroll.view.actions.bank.kcb">KCB Bank Excel report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="payroll::payroll.view.actions.bank.kcb">KCB Bank Tab-Separated report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Barclays Bank report">Barclays Bank report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Co-operative Bank report">Co-operative Bank report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Bank of Africa report">Bank of Africa report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="NCBA Bank - EFT report">NCBA Bank - EFT report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="NCBA Bank - RTGS report">NCBA Bank - RTGS report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Equity Bank file (Eazzy Remmittance) report">Equity Bank file (Eazzy Remmittance) report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Equity Bank (Within) report">Equity Bank (Within) report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Equity Bank (Other) report">Equity Bank (Other) report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Equity Bank (API) report">Equity Bank (API) report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Diamond Trust Bank (DTB) report">Diamond Trust Bank (DTB) report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="I&amp;M Bank report">I&amp;M Bank report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Cash report">Cash report</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Master roll</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Detailed in Excel">Detailed in Excel</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Detailed in PDF">Detailed in PDF</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Summary in Excel">Summary in Excel</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Summary in PDF">Summary in PDF</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Group detailed excel by location">Group detailed excel by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Group detailed excel by department">Group detailed excel by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Group detailed excel by job category">Group detailed excel by job category</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Group detailed PDF by location">Group detailed PDF by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Group detailed PDF by department">Group detailed PDF by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Group detailed PDF by job category">Group detailed PDF by job category</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Group summary excel by location">Group summary excel by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Group summary excel by department">Group summary excel by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idMasterRoll" title="Group summary excel by job category">Group summary excel by job category</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Group summary PDF by location">Group summary PDF by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Group summary PDF by department">Group summary PDF by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" title="Group summary PDF by job category">Group summary PDF by job category</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Company payslip</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idP9" title="Excel">Excel</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" target="_blank" title="PDF">PDF</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Company payslip</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idP9" title="Excel">Excel</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" target="_blank" title="PDF">PDF</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">NITA report</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNita" title="Export NITA report">NITA (KRA Return/Remittance) import report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNita" title="Export NITA report">NITA report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idNita" title="Export NITA report">NITA report (PDF)</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">FBT report</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idP9" title="KRA FBT CSV report">KRA FBT CSV report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" target="_blank" title="FBT Excel report">FBT Excel report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" target="_blank" title="FBT PDF report">FBT PDF report</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Welfare report</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                     <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Excel</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="PDF">PDF</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">This month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">This year report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Month by month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by job category</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Loan report</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Excel</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="PDF">PDF</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">This month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">This year report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Month by month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by job category</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Leave Pay report</div>
                <div class="card-body mb-0">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Excel</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="PDF">PDF</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">This month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">This year report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Month by month report</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by department</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by location</a>
                        </div>
                        <div class="col-md-3">
                            <a href="" class="btn btn-primary w-100" id="idBank" title="Excel">Grouped by job category</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/main/downloads.js') }}" type="module"></script>
    @endpush

</x-app-layout>

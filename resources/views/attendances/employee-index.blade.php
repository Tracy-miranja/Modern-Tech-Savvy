<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ $page }}</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-sm" href="{{ route('myaccount.attendances.clock-in-out.index', $business->slug) }}">
                            <i class="bi bi-calendar-check me-1"></i> Clock In / Out
                        </a>
                        <button type="button" class="btn btn-outline-info btn-sm" id="openMyAttendanceReportsBtn">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> My Reports
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small mb-1">Hours worked this month</div>
                                <div class="fs-4 fw-bold">{{ $thisMonthHoursLabel }}</div>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        Generate your own Daily, Monthly, or Summary attendance report from "My Reports" - previews first, then print or download as PDF.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal')

    @push('scripts')
        <script src="{{ asset('js/main/report-modal.js') }}"></script>
        <script>
            ReportModal.init({ employeeOptionsUrl: null });

            document.getElementById('openMyAttendanceReportsBtn').addEventListener('click', function () {
                ReportModal.open([
                    {
                        key: 'daily',
                        label: 'My Daily Attendance',
                        filters: ['date'],
                        previewUrl: @json(route('myaccount.attendances.reports.daily.preview', $business->slug)),
                        downloadUrl: @json(route('myaccount.attendances.reports.daily.download', $business->slug)),
                    },
                    {
                        key: 'monthly',
                        label: 'My Monthly Attendance (totals)',
                        filters: ['date_range'],
                        previewUrl: @json(route('myaccount.attendances.reports.monthly.preview', $business->slug)),
                        downloadUrl: @json(route('myaccount.attendances.reports.monthly.download', $business->slug)),
                    },
                    {
                        key: 'summary',
                        label: 'My Attendance Summary',
                        filters: ['date_range'],
                        previewUrl: @json(route('myaccount.attendances.reports.summary.preview', $business->slug)),
                        downloadUrl: @json(route('myaccount.attendances.reports.summary.download', $business->slug)),
                    },
                ]);
            });
        </script>
    @endpush
</x-app-layout>

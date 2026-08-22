<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">Attendance Reports</h5>
                    <p class="text-muted small mb-0">Pick a report below to filter, preview, print, or download it.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="attendanceReportsGallery"></div>
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => $jobCategories ?? []])

    @push('scripts')
        <script>
        window.businessSlug = @json($currentBusiness->slug);
        </script>
        <script src="{{ asset('js/main/report-modal.js') }}"></script>
        <script>
            ReportModal.init({
                employeeOptionsUrl: @json(route('business.organogram.employee-options', $currentBusiness->slug)),
            });

            ReportModal.renderGallery('attendanceReportsGallery', [
                {
                    key: 'daily',
                    label: 'Daily Attendance Report',
                    icon: 'bi-calendar-day',
                    description: 'Clock-in/out for every employee on one day.',
                    filters: ['date', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.daily.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.daily.download', $currentBusiness->slug)),
                },
                {
                    key: 'monthly',
                    label: 'Monthly Attendance Report',
                    icon: 'bi-calendar-month',
                    description: 'Attendance totals per employee over a period.',
                    filters: ['date_range', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.monthly.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.monthly.download', $currentBusiness->slug)),
                },
                {
                    key: 'full',
                    label: 'Full Attendance Report',
                    icon: 'bi-journal-text',
                    description: 'Every clock-in/out record in a date range.',
                    filters: ['date_range', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.full.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.full.download', $currentBusiness->slug)),
                },
                {
                    key: 'summary',
                    label: 'Summary Report',
                    icon: 'bi-bar-chart',
                    description: 'Present/absent/late day counts per employee.',
                    filters: ['date_range', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.summary.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.summary.download', $currentBusiness->slug)),
                },
                {
                    key: 'lateness',
                    label: 'Lateness Report',
                    icon: 'bi-clock-history',
                    description: 'Every late clock-in in a date range, per employee.',
                    filters: ['date_range', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.lateness.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.lateness.download', $currentBusiness->slug)),
                },
                {
                    key: 'absent',
                    label: 'Absence Report',
                    icon: 'bi-person-x',
                    description: 'Days each employee was marked absent.',
                    filters: ['date_range', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.absent.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.absent.download', $currentBusiness->slug)),
                },
                {
                    key: 'overtime',
                    label: 'Overtime Report',
                    icon: 'bi-stopwatch',
                    description: 'Overtime hours worked per employee in a date range.',
                    filters: ['date_range', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.overtime.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.overtime.download', $currentBusiness->slug)),
                },
                {
                    key: 'per-member',
                    label: 'Per-Member Report',
                    icon: 'bi-person-lines-fill',
                    description: "One employee's full attendance history.",
                    filters: ['date_range', 'employee'],
                    previewUrl: @json(route('business.attendances.reports.per-member.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.attendances.reports.per-member.download', $currentBusiness->slug)),
                },
            ]);
        </script>
    @endpush
</x-app-layout>

<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">Leave Reports</h5>
                    <p class="text-muted small mb-0">Pick a report below to filter, preview, print, or download it.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="leaveReportsGallery"></div>
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => $jobCategories ?? [], 'leavePeriods' => $leavePeriods ?? [], 'leaveTypes' => $leaveTypes ?? []])

    @push('scripts')
        <script src="{{ asset('js/main/report-modal.js') }}"></script>
        <script>
            ReportModal.init({
                employeeOptionsUrl: @json(route('business.organogram.employee-options', $currentBusiness->slug)),
            });

            ReportModal.renderGallery('leaveReportsGallery', [
                {
                    key: 'balance',
                    label: 'Leave Balance Report',
                    icon: 'bi-piggy-bank',
                    description: 'Remaining leave days per employee for a leave period.',
                    filters: ['leave_period', 'leave_type', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.leave.reports.balance.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.leave.reports.balance.download', $currentBusiness->slug)),
                },
                {
                    key: 'full',
                    label: 'Full Leave Report',
                    icon: 'bi-journal-text',
                    description: 'Every leave request in a date range, with status and dates.',
                    filters: ['date_range', 'leave_type', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.leave.reports.full.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.leave.reports.full.download', $currentBusiness->slug)),
                },
                {
                    key: 'types',
                    label: 'Leave Types Usage Report',
                    icon: 'bi-bar-chart',
                    description: 'How much each leave type was used, business-wide.',
                    filters: ['date_range', 'department', 'job_category'],
                    previewUrl: @json(route('business.leave.reports.types.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.leave.reports.types.download', $currentBusiness->slug)),
                },
                {
                    key: 'per-member',
                    label: 'Per-Member Leave Report',
                    icon: 'bi-person-lines-fill',
                    description: "One employee's full leave history and balance summary.",
                    filters: ['date_range', 'employee'],
                    previewUrl: @json(route('business.leave.reports.per-member.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.leave.reports.per-member.download', $currentBusiness->slug)),
                },
                {
                    key: 'master',
                    label: 'Leave Master Report',
                    icon: 'bi-table',
                    description: 'The full entitlement breakdown per employee per leave type.',
                    filters: ['leave_period', 'leave_type', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.leave.reports.master.preview', $currentBusiness->slug)),
                    downloadUrl: @json(route('business.leave.reports.master.download', $currentBusiness->slug)),
                },
            ]);
        </script>
    @endpush
</x-app-layout>

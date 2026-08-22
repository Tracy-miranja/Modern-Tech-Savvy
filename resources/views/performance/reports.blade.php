<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">Performance Reports</h5>
                    <p class="text-muted small mb-0">Pick a report below to filter, preview, print, or download it.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="performanceReportsGallery"></div>
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => $jobCategories ?? []])

    @push('scripts')
        <script src="{{ asset('js/main/report-modal.js') }}"></script>
        <script>
        (function () {
            ReportModal.init({
                employeeOptionsUrl: @json(route('business.organogram.employee-options', $business->slug)),
                cycleOptionsUrl: @json(route('business.performance.cycles.fetch', $business->slug)),
            });

            ReportModal.renderGallery('performanceReportsGallery', [
                {
                    key: 'cycle',
                    label: 'Performance Cycle Report',
                    icon: 'bi-graph-up-arrow',
                    description: 'KPI, OKR, and overall scores for a performance cycle.',
                    filters: ['cycle', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.performance.reports.cycle.preview', $business->slug)),
                    downloadUrl: @json(route('business.performance.reports.cycle.download', $business->slug)),
                },
                {
                    key: 'three-sixty',
                    label: '360 Performance Report',
                    icon: 'bi-people',
                    description: 'Compiled peer feedback for one employee in a cycle.',
                    filters: ['cycle', 'employee'],
                    previewUrl: @json(route('business.performance.reports.three-sixty.preview', $business->slug)),
                    downloadUrl: @json(route('business.performance.reports.three-sixty.download', $business->slug)),
                },
            ]);
        })();
        </script>
    @endpush
</x-app-layout>

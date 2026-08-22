<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">Learning Reports</h5>
                    <p class="text-muted small mb-0">Pick a report below to filter, preview, print, or download it.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="learningReportsGallery"></div>
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => $jobCategories ?? []])

    @push('scripts')
        <script src="{{ asset('js/main/report-modal.js') }}"></script>
        <script>
        (function () {
            const employeeOptionsUrl = @json(route('business.organogram.employee-options', $business->slug));
            const courseOptionsUrl = @json(route('business.learning.courses.options', $business->slug));

            ReportModal.init({ employeeOptionsUrl, courseOptionsUrl });

            ReportModal.renderGallery('learningReportsGallery', [
                {
                    key: 'completions',
                    label: 'Learning Completions Report',
                    icon: 'bi-mortarboard',
                    description: 'Course completions, scores, and certificates per employee.',
                    filters: ['date_range', 'course', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.learning.reports.completions.preview', $business->slug)),
                    downloadUrl: @json(route('business.learning.reports.completions.download', $business->slug)),
                },
            ]);
        })();
        </script>
    @endpush
</x-app-layout>

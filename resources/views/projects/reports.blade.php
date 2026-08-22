<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">Project Reports</h5>
                    <p class="text-muted small mb-0">Pick a report below to filter, preview, print, or download it.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="projectReportsGallery"></div>
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
            const projectOptionsUrl = @json(route('business.projects.options', $business->slug));

            ReportModal.init({ employeeOptionsUrl, projectOptionsUrl });

            ReportModal.renderGallery('projectReportsGallery', [
                {
                    key: 'task-status',
                    label: 'Task Status Report',
                    icon: 'bi-kanban',
                    description: 'Task counts by status for a project or employee.',
                    filters: ['date_range', 'project', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.projects.reports.task-status.preview', $business->slug)),
                    downloadUrl: @json(route('business.projects.reports.task-status.download', $business->slug)),
                },
                {
                    key: 'time-tracking',
                    label: 'Time Tracking Report',
                    icon: 'bi-clock-history',
                    description: 'Hours logged per employee/task on a project.',
                    filters: ['date_range', 'project', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.projects.reports.time-tracking.preview', $business->slug)),
                    downloadUrl: @json(route('business.projects.reports.time-tracking.download', $business->slug)),
                },
            ]);
        })();
        </script>
    @endpush
</x-app-layout>

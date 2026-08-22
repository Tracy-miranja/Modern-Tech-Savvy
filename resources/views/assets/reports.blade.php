<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">Asset Reports</h5>
                    <p class="text-muted small mb-0">Pick a report below to filter, preview, print, or download it.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="assetReportsGallery"></div>
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal', ['departments' => $departments ?? []])

    @push('scripts')
        <script src="{{ asset('js/main/report-modal.js') }}"></script>
        <script>
            ReportModal.init({});

            ReportModal.renderGallery('assetReportsGallery', [
                {
                    key: 'register',
                    label: 'Asset Register',
                    icon: 'bi-boxes',
                    description: 'Every asset, its status, and who (if anyone) holds it.',
                    filters: ['asset_status', 'department'],
                    previewUrl: @json(route('business.assets.reports.register.preview', $business->slug)),
                    downloadUrl: @json(route('business.assets.reports.register.download', $business->slug)),
                },
            ]);
        </script>
    @endpush
</x-app-layout>

{{-- resources/views/attendances/holidays_index.blade.php --}}
<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $page ?? 'Holidays' }}</h5>
                    <a class="btn btn-outline-secondary btn-sm"
                       href="{{ route('business.attendances.index', $currentBusiness->slug) }}">
                        <i class="bi bi-arrow-left"></i> Back to Attendances
                    </a>
                </div>

                <div class="card-body" id="holidaysContainer">
                    {{ loader() }}
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // make business slug available without meta tags
            window.businessSlug = @json($currentBusiness->slug);
        </script>

        <script src="{{ asset('js/main/holiday.js') }}" type="module"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // ✅ getHolidays is defined by holiday.js as window.getHolidays
                if (typeof window.getHolidays === 'function') {
                    window.getHolidays();
                } else {
                    console.error('holiday.js loaded but window.getHolidays was not registered.');
                }
            });
        </script>
    @endpush
</x-app-layout>

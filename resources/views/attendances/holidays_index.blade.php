{{-- resources/views/attendances/holidays_index.blade.php --}}
<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ $page ?? 'Holidays' }}</h5>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if ($locations->isNotEmpty())
                            <select id="holidayLocationFilter" class="form-select form-select-sm" style="max-width:200px;">
                                <option value="">All locations</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}{{ $location->country ? ' (' . $location->country . ')' : '' }}</option>
                                @endforeach
                            </select>
                        @endif
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importHolidaysModal">
                            <i class="bi bi-cloud-download"></i> Auto-Load Holidays
                        </button>
                        <a class="btn btn-outline-secondary btn-sm"
                           href="{{ route('business.attendances.index', $currentBusiness->slug) }}">
                            <i class="bi bi-arrow-left"></i> Back to Attendances
                        </a>
                    </div>
                </div>
                @if ($locations->isNotEmpty())
                    <div class="px-3 pt-2">
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Holidays with no location apply business-wide, to every employee regardless of branch. Filter by location to see (or auto-load) that branch's own public holidays, e.g. Kenyan holidays for a Nairobi location and Ugandan holidays for a Kampala one.</small>
                    </div>
                @endif

                <div class="card-body" id="holidaysContainer">
                    {{ loader() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Import Holidays Modal -->
    <div class="modal fade" id="importHolidaysModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Auto-Load Public Holidays</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Loads the public holiday calendar for a country from a free public holiday service. Anything already on file is skipped, so this is safe to run again for another year.</p>
                    <form id="importHolidaysForm">
                        @if ($locations->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label">Location (optional)</label>
                                <select name="location_id" id="importLocationSelect" class="form-select">
                                    <option value="">— Business-wide (applies to everyone) —</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}{{ $location->country ? ' (' . $location->country . ')' : '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Pick a location to load holidays for just that branch's country (e.g. Kenya for a Nairobi location), instead of every employee in the business.</small>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Country</label>
                            <select name="country_code" id="importCountrySelect" class="form-select" required>
                                <option value="">Loading countries…</option>
                            </select>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" min="2000" max="2100" value="{{ now()->year }}" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitImportHolidaysBtn">Load Holidays</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // make business slug available without meta tags
            window.businessSlug = @json($currentBusiness->slug);
            window.businessLocations = @json($locations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name]));
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

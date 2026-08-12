<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                        <h5 class="mb-1">Leave Settings</h5>
                        <a class="btn btn-outline-warning btn-sm" href="{{ route('business.holidays.index', $business->slug) }}">
                            <i class="bi bi-calendar2-event me-1"></i> Manage Public Holidays
                        </a>
                    </div>
                    <small class="text-muted d-block mb-3">
                        Company-wide settings that apply across every leave type - shown as background stripes on the
                        <a href="{{ route('business.leave.calendar', $business->slug) }}">Leave Calendar</a> and excluded automatically from every leave-day calculation.
                        Public holidays are managed under <a href="{{ route('business.holidays.index', $business->slug) }}">Set Holidays</a>, per branch/country, and are also excluded automatically.
                    </small>

                    <h6 class="mb-2">Non-Working Days</h6>
                    <p class="text-muted small">Pick the days of the week the company doesn't work (e.g. weekends). These are excluded from leave-day counts for every leave type, including annual leave, and are shown on the calendar in grey.</p>

                    <form id="nonWorkingDaysForm" class="mb-1">
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            @foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index => $day)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input non-working-day-check" id="day-{{ $index }}" value="{{ $index }}"
                                        {{ in_array($index, $business->non_working_days ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="day-{{ $index }}">{{ $day }}</label>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="saveNonWorkingDaysBtn">Save</button>
                    </form>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                        <div>
                            <h6 class="mb-1">Company-Mandated Leave Days</h6>
                            <p class="text-muted small mb-0">
                                Days the company declares as leave for everyone, a department, or a location (e.g. a Dec 24-28 shutdown) - automatically deducted from each affected employee's balance for the leave type you choose, with no individual application needed.
                            </p>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addMandatoryLeavePeriod()">
                            <i class="bi bi-plus-circle"></i> Add
                        </button>
                    </div>

                    <div id="mandatoryLeavePeriodsContainer" class="mt-2">
                        {{ loader() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mandatoryLeavePeriodModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mandatoryLeavePeriodModalLabel">Add Company-Mandated Leave Days</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="mandatoryLeavePeriodModalBody"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.businessSlug = @json($business->slug);
        </script>
        <script src="{{ asset('js/main/mandatory-leave.js') }}" type="module"></script>
    @endpush

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const updateUrl = @json(route('business.leave.settings.update', ['business' => $business->slug]));

        document.getElementById('saveNonWorkingDaysBtn').addEventListener('click', async function () {
            const days = Array.from(document.querySelectorAll('.non-working-day-check:checked')).map(c => parseInt(c.value, 10));

            try {
                const resp = await fetch(updateUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ non_working_days: days }),
                });
                const payload = await resp.json();
                if (!resp.ok) {
                    toastr.error(payload.message || 'Could not save settings.');
                    return;
                }
                toastr.success('Leave settings saved.');
            } catch (e) {
                console.error(e);
                toastr.error('Could not save settings.');
            }
        });
    })();
    </script>
</x-app-layout>

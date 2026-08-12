<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0">{{ $title }}</h5>
                        <div class="d-flex flex-wrap gap-2">
                            @isset($showDepartmentFilter)
                                <select id="departmentFilter" class="form-select form-select-sm" style="max-width: 220px;">
                                    <option value="">All Departments</option>
                                    @foreach ($business->departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            @endisset
                            @isset($locations)
                                @if ($locations->isNotEmpty())
                                    <select id="locationFilter" class="form-select form-select-sm" style="max-width: 220px;">
                                        <option value="">All Locations</option>
                                        @foreach ($locations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}{{ $location->country ? ' (' . $location->country . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            @endisset
                        </div>
                    </div>
                    <div class="d-flex gap-3 small text-muted mb-2 flex-wrap">
                        <span><span class="badge" style="background:#0d6efd;">&nbsp;</span> Approved leave</span>
                        <span><span class="badge" style="background:#dc3545;">&nbsp;</span> Holiday (non-working)</span>
                        <span><span class="badge" style="background:#fd7e14;">&nbsp;</span> Holiday (working)</span>
                        <span><span class="badge" style="background:#adb5bd;">&nbsp;</span> Non-working day</span>
                        <span><span class="badge" style="background:#6f42c1;">&nbsp;</span> Company-mandated leave</span>
                    </div>
                    @if (empty($nonWorkingDays) && isset($showDepartmentFilter))
                        <p class="small text-muted mb-2">
                            <i class="bi bi-info-circle"></i> No non-working days configured yet -
                            <a href="{{ route('business.leave.settings', $business->slug) }}">set them in Leave Settings</a>.
                        </p>
                    @endif
                    <div class="calendar-scroll">
                        <div id="leaveCalendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet">
    <style>
        .calendar-scroll {
            overflow-x: auto;
        }
        #leaveCalendar {
            min-width: 320px;
        }
        @media (max-width: 576px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: .5rem;
            }
            .fc .fc-toolbar-title {
                font-size: 1.1rem;
            }
            .fc .fc-button {
                padding: .3rem .5rem;
                font-size: .8rem;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    <script>
    (function () {
        const eventsUrl = @json($eventsUrl);
        const nonWorkingDays = @json($nonWorkingDays ?? []);
        const calendarEl = document.getElementById('leaveCalendar');

        const nonWorkingDayEvents = nonWorkingDays.map(day => ({
            daysOfWeek: [day],
            display: 'background',
            color: '#adb5bd',
        }));

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: '',
            },
            eventSources: [
                nonWorkingDayEvents,
                function (info, successCallback, failureCallback) {
                    const departmentId = document.getElementById('departmentFilter')?.value;
                    const locationId = document.getElementById('locationFilter')?.value;
                    const url = new URL(eventsUrl, window.location.origin);
                    url.searchParams.set('start', info.startStr);
                    url.searchParams.set('end', info.endStr);
                    if (departmentId) {
                        url.searchParams.set('department_id', departmentId);
                    }
                    if (locationId) {
                        url.searchParams.set('location_id', locationId);
                    }

                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(resp => resp.json())
                        .then(successCallback)
                        .catch(failureCallback);
                },
            ],
        });

        calendar.render();

        document.getElementById('departmentFilter')?.addEventListener('change', function () {
            calendar.refetchEvents();
        });
        document.getElementById('locationFilter')?.addEventListener('change', function () {
            calendar.refetchEvents();
        });
    })();
    </script>
    @endpush
</x-app-layout>

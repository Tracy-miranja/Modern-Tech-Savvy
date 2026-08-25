<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="mb-0">{{ $title }}</h5>
                        <div class="d-flex flex-wrap gap-2">
                            @isset($showPlanner)
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary active" id="viewCalendarBtn">Calendar</button>
                                    <button type="button" class="btn btn-outline-primary" id="viewPlannerBtn">Planner</button>
                                </div>
                            @endisset
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
                        <span><span class="badge" style="background:#f5c115;">&nbsp;</span> Approved leave</span>
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
                    <div class="calendar-scroll" id="calendarView">
                        <div id="leaveCalendar"></div>
                    </div>

                    @isset($showPlanner)
                        <div id="plannerView" class="d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="plannerPrevMonthBtn"><i class="bi bi-chevron-left"></i></button>
                                <strong id="plannerMonthLabel"></strong>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="plannerNextMonthBtn"><i class="bi bi-chevron-right"></i></button>
                            </div>
                            <p class="small text-muted mb-2">
                                Days shaded above the <strong id="plannerThresholdLabel">{{ $capacityWarningPercent ?? 30 }}%</strong> capacity warning threshold (set in Leave Settings) show more of the filtered team on leave at once than usual - worth checking before approving another request for that date.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" id="plannerTable">
                                    <thead>
                                        <tr id="plannerCapacityRow"><th>Capacity</th></tr>
                                    </thead>
                                    <tbody id="plannerTableBody">
                                        <tr><td class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endisset
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
            if (typeof loadPlanner === 'function') loadPlanner();
        });
        document.getElementById('locationFilter')?.addEventListener('change', function () {
            calendar.refetchEvents();
            if (typeof loadPlanner === 'function') loadPlanner();
        });

        // ---- Planner (row-per-employee timeline + capacity strip) ---------
        // Reuses the exact same eventsUrl the calendar view already fetches
        // from - no separate backend query for the timeline itself, only
        // the team-headcount endpoint (the denominator for "% of team out").

        const teamHeadcountUrl = @json($teamHeadcountUrl ?? null);
        const capacityWarningPercent = @json($capacityWarningPercent ?? 30);
        let plannerMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);

        window.loadPlanner = async function () {
            if (!teamHeadcountUrl) return;

            const monthStart = new Date(plannerMonth.getFullYear(), plannerMonth.getMonth(), 1);
            const monthEnd = new Date(plannerMonth.getFullYear(), plannerMonth.getMonth() + 1, 0);
            document.getElementById('plannerMonthLabel').textContent = monthStart.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

            const departmentId = document.getElementById('departmentFilter')?.value;
            const locationId = document.getElementById('locationFilter')?.value;
            const toDateStr = (d) => d.toISOString().slice(0, 10);

            const eventsFetchUrl = new URL(eventsUrl, window.location.origin);
            eventsFetchUrl.searchParams.set('start', toDateStr(monthStart));
            eventsFetchUrl.searchParams.set('end', toDateStr(monthEnd));
            if (departmentId) eventsFetchUrl.searchParams.set('department_id', departmentId);
            if (locationId) eventsFetchUrl.searchParams.set('location_id', locationId);

            const headcountFetchUrl = new URL(teamHeadcountUrl, window.location.origin);
            if (departmentId) headcountFetchUrl.searchParams.set('department_id', departmentId);
            if (locationId) headcountFetchUrl.searchParams.set('location_id', locationId);

            const [events, headcountPayload] = await Promise.all([
                fetch(eventsFetchUrl, { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
                fetch(headcountFetchUrl, { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
            ]);

            const teamSize = headcountPayload.count || 1; // avoid divide-by-zero
            const leaveEvents = events.filter(e => e.extendedProps?.type === 'leave');
            const daysInMonth = monthEnd.getDate();

            // Per-day: which employees are on leave (a leave event's `end`
            // is exclusive, matching FullCalendar's convention already used
            // when the event was built).
            const employeesByDay = Array.from({ length: daysInMonth }, () => new Set());
            leaveEvents.forEach(e => {
                const start = new Date(e.start);
                const end = new Date(e.end);
                for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                    if (d.getMonth() === monthStart.getMonth() && d.getFullYear() === monthStart.getFullYear()) {
                        employeesByDay[d.getDate() - 1].add(e.extendedProps.employee_id);
                    }
                }
            });

            // Capacity header row
            const capacityRow = document.getElementById('plannerCapacityRow');
            capacityRow.innerHTML = '<th>Capacity</th>' + employeesByDay.map((set, i) => {
                const pct = Math.round((set.size / teamSize) * 100);
                const over = pct > capacityWarningPercent;
                const bg = over ? '#f8d7da' : (set.size > 0 ? '#fff3cd' : '');
                return `<th class="text-center small" style="background:${bg};" title="${set.size} of ${teamSize} on leave (${pct}%)">${i + 1}</th>`;
            }).join('');

            // Employee rows
            const employees = new Map();
            leaveEvents.forEach(e => {
                if (!employees.has(e.extendedProps.employee_id)) {
                    employees.set(e.extendedProps.employee_id, e.extendedProps.employee_name);
                }
            });

            const tbody = document.getElementById('plannerTableBody');
            if (employees.size === 0) {
                tbody.innerHTML = `<tr><td colspan="${daysInMonth + 1}" class="text-center text-muted">No approved leave in this month for the current filters.</td></tr>`;
                return;
            }

            tbody.innerHTML = [...employees.entries()].map(([employeeId, employeeName]) => {
                const cells = employeesByDay.map(set => set.has(employeeId)
                    ? '<td style="background:#f5c115;"></td>'
                    : '<td></td>').join('');
                return `<tr><td class="text-nowrap">${employeeName}</td>${cells}</tr>`;
            }).join('');
        };

        document.getElementById('viewPlannerBtn')?.addEventListener('click', function () {
            document.getElementById('calendarView').classList.add('d-none');
            document.getElementById('plannerView').classList.remove('d-none');
            this.classList.add('active');
            document.getElementById('viewCalendarBtn').classList.remove('active');
            loadPlanner();
        });
        document.getElementById('viewCalendarBtn')?.addEventListener('click', function () {
            document.getElementById('plannerView').classList.add('d-none');
            document.getElementById('calendarView').classList.remove('d-none');
            this.classList.add('active');
            document.getElementById('viewPlannerBtn').classList.remove('active');
        });
        document.getElementById('plannerPrevMonthBtn')?.addEventListener('click', function () {
            plannerMonth = new Date(plannerMonth.getFullYear(), plannerMonth.getMonth() - 1, 1);
            loadPlanner();
        });
        document.getElementById('plannerNextMonthBtn')?.addEventListener('click', function () {
            plannerMonth = new Date(plannerMonth.getFullYear(), plannerMonth.getMonth() + 1, 1);
            loadPlanner();
        });
    })();
    </script>
    @endpush
</x-app-layout>

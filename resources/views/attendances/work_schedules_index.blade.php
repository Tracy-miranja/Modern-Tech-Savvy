<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $page ?? 'Work Schedules' }}</h5>

                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary btn-sm"
                           href="{{ route('business.attendances.index', $currentBusiness->slug) }}">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>

                        <button class="btn btn-primary btn-sm" onclick="addWorkSchedule()">
                            <i class="bi bi-plus-circle"></i> Add Work Schedule
                        </button>
                        <button class="btn btn-primary" onclick="openBulkAssignModal()">
                            <i class="bi bi-people"></i> Bulk Assign Schedules
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Filter by Employee</label>
                            <select class="form-control" id="filterEmployee">
                                <option value="">All Employees</option>
                                @foreach(\App\Models\Employee::where('business_id', $currentBusiness->id)->with('user:id,name')->get() as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->user->name ?? ('Employee #'.$emp->id) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Filter by Shift</label>
                            <select class="form-control" id="filterShift">
                                <option value="">All Shifts</option>
                                <option value="no_shift">No Shift Assigned</option>
                                @foreach(\App\Models\Shift::where('business_id', $currentBusiness->id)->get() as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary w-100" type="button" onclick="applyWorkScheduleFilters()">
                                Apply
                            </button>
                            <button class="btn btn-outline-secondary w-100" type="button" onclick="resetWorkScheduleFilters()">
                                Reset
                            </button>
                        </div>
                    </div>

                    @php
                        $shifts = \App\Models\Shift::where('business_id', $currentBusiness->id)->get(['id','name']);
                    @endphp

                    <ul class="nav nav-tabs mb-3" id="shiftTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" type="button"
                                    onclick="loadShiftTab('all', this)">
                                All
                            </button>
                        </li>

                        @foreach($shifts as $shift)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" type="button"
                                        onclick="loadShiftTab('{{ $shift->id }}', this)">
                                    {{ $shift->name }}
                                </button>
                            </li>
                        @endforeach

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button"
                                    onclick="loadShiftTab('no_shift', this)">
                                No Shift
                            </button>
                        </li>
                    </ul>

                    <div id="workSchedulesContainer">
                        {{ loader() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="addWorkScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Work Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="workScheduleFormContainer">
                    {{-- JS injects form --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Assign Modal --}}
    <div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Bulk Assign Work Schedules</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    {{-- Shifts selector --}}
                    <div class="mb-3">
                        <label class="form-label">Select Shifts</label>
                        <div id="shiftPicker"></div>
                    </div>

                    {{-- Employees selector + filters --}}
                    <div class="mb-3">
                        <label class="form-label">Select Employees</label>
                        <div id="employeePicker"></div>
                    </div>

                    {{-- Working days --}}
                    <div class="mb-3">
                        <label class="form-label">Working Days</label><br/>
                        @php $days = [0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat']; @endphp
                        @foreach($days as $v=>$l)
                            <label class="me-2">
                                <input type="checkbox" class="bulk-days" value="{{ $v }}" {{ in_array($v,[1,2,3,4,5])?'checked':'' }}>
                                {{ $l }}
                            </label>
                        @endforeach
                    </div>

                    {{-- Dates --}}
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Effective From</label>
                            <input type="date" class="form-control" id="bulkEffectiveFrom">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Effective To (optional)</label>
                            <input type="date" class="form-control" id="bulkEffectiveTo">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="bulkNotes" rows="2"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="button" onclick="submitBulkAssign(this)">
                        <i class="bi bi-check-circle"></i> Assign Schedules
                    </button>
                </div>

            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            window.businessSlug = @json($currentBusiness->slug);
        </script>
    @php
    $employeesList = \App\Models\Employee::where('business_id', $currentBusiness->id)
    ->with(['user:id,name', 'department:id,name']) // adjust relationship name if different
    ->get()
    ->map(function($e){
        return [
            'id' => $e->id,
            'name' => $e->user->name ?? ('Employee #'.$e->id),
            'department_id' => $e->department_id ?? null,
            'department' => $e->department->name ?? 'N/A',
        ];
    })->values();

    $departmentsList = \App\Models\Department::where('business_id', $currentBusiness->id)
    ->get(['id','name'])
    ->values();

    $shiftsList = \App\Models\Shift::where('business_id', $currentBusiness->id)
    ->get()
    ->map(fn($s)=> ['id'=>$s->id,'name'=>$s->name])
    ->values();
    @endphp

    <script>
    window.allEmployees = @json($employeesList);
    window.allShifts = @json($shiftsList);
    window.allDepartments = @json($departmentsList);
    </script>



        <script src="{{ asset('js/main/work-schedule.js') }}" type="module"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                if (typeof window.getWorkSchedules === 'function') {
                    window.getWorkSchedules();
                } else {
                    console.error('work-schedule.js loaded but window.getWorkSchedules was not registered.');
                }
            });
        </script>
    @endpush
</x-app-layout>


<x-app-layout>

    <div class="row g-20">
        <div class="col-md-">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">{{ $page }}</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">

                                <div style="min-width:180px;">
                                    <input type="date"
                                        class="form-control form-control-sm"
                                        name="date"
                                        id="date"
                                        value="{{ request('date', now()->format('Y-m-d')) }}">
                                </div>

                                <a class="btn btn-primary btn-sm"
                                href="{{ route('business.attendances.clock-in', $currentBusiness->slug) }}">
                                    <i class="bi bi-calendar-check me-1"></i> Record Attendance
                                </a>

                                <a class="btn btn-outline-success btn-sm"
                                href="{{ route('business.attendances.payroll-hours', $currentBusiness->slug) }}">
                                    <i class="bi bi-calculator me-1"></i> Payroll Hours
                                </a>

                                <a class="btn btn-outline-secondary btn-sm"
                                href="{{ route('business.attendances.settings.index', $currentBusiness->slug) }}">
                                    <i class="bi bi-gear me-1"></i> Settings
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Employee</label>
                        <select class="form-select" id="filter_employee">
                        <option value="">All employees</option>

                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Day Type</label>
                        <select class="form-select" id="filter_day_type">
                        <option value="">All</option>
                        <option value="working">Working</option>
                        <option value="non_working">Non-working</option>
                        <option value="holiday">Holiday</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select class="form-select" id="filter_status">
                        <option value="">All</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Punctuality</label>
                        <select class="form-select" id="filter_punctuality">
                        <option value="">All</option>
                        <option value="ontime">On time</option>
                        <option value="late">Late only</option>
                        <option value="early">Early departure</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Search (remarks)</label>
                        <input class="form-control" id="filter_search" placeholder="e.g. sick, client visit...">
                    </div>
                </div>
                <div class="card-body" id="attendancesContainer">
                    {{ loader() }}
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="viewAttendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Attendance Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewAttendanceContainer">

      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Attendance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="editAttendanceContainer">

      </div>
    </div>
  </div>
</div>

@push('scripts')
        <script>
        window.businessSlug = @json($currentBusiness->slug);
        </script>

        <script src="{{ asset('js/main/attendances.js') }}" type="module"></script>
        <script>
            $(document).ready(() => {
                let date = $('#date').val();
                getAttendances(date);

                $('#date').on('change', function() {
                    let selectedDate = $(this).val();
                    getAttendances(selectedDate);
                });
            });
        </script>
    @endpush

</x-app-layout>

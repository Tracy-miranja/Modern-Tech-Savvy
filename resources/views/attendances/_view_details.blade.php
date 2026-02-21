@php
  $empName = $attendance->employee->user->name ?? 'N/A';
  $dateTxt = $attendance->date?->format('l, jS M Y') ?? '-';

  $shiftName = $attendance->shift?->name ?? 'No Shift';
  $shiftTime = $attendance->shift
    ? (\Carbon\Carbon::parse($attendance->shift->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($attendance->shift->end_time)->format('H:i'))
    : null;

  $dayType = $attendance->is_holiday
    ? 'Holiday'
    : (!$attendance->is_working_day ? 'Non-Working Day' : 'Working Day');

  $clockIn  = $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-';
  $clockOut = $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-';

  $expectedIn  = $attendance->expected_clock_in ? $attendance->expected_clock_in->format('H:i') : null;
  $expectedOut = $attendance->expected_clock_out ? $attendance->expected_clock_out->format('H:i') : null;

  $late  = round($attendance->late_minutes ?? 0);
  $early = round($attendance->early_departure_minutes ?? 0);

  $regular = number_format($attendance->regular_hours ?? 0, 2);
  $otReg   = number_format($attendance->overtime_regular ?? 0, 2);
  $otHol   = number_format($attendance->overtime_holiday ?? 0, 2);

  $remarks = trim((string)($attendance->remarks ?? ''));
@endphp

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between gap-3 mb-3">
  <div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <h5 class="mb-0">{{ $empName }}</h5>

      @if($attendance->is_absent)
        <span class="badge bg-danger">Absent</span>
      @else
        <span class="badge bg-success">Present</span>
      @endif

      @if($attendance->is_holiday)
        <span class="badge bg-warning text-dark">Holiday</span>
      @elseif(!$attendance->is_working_day)
        <span class="badge bg-info text-dark">Non-Working</span>
      @endif
    </div>

    <div class="text-muted small mt-1">
      <i class="bi bi-calendar3 me-1"></i>{{ $dateTxt }}
      <span class="mx-2">•</span>
      <i class="bi bi-clock-history me-1"></i>{{ $dayType }}
    </div>
  </div>

  <div class="text-end">
    <div class="text-muted small">Shift</div>
    <div class="fw-semibold">{{ $shiftName }}</div>
    @if($shiftTime)
      <div class="text-muted small">{{ $shiftTime }}</div>
    @endif
  </div>
</div>

<hr class="my-3">

{{-- KPI cards --}}
<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Clock In</div>
            <div class="fs-4 fw-bold">{{ $clockIn }}</div>
            @if($expectedIn)
              <div class="text-muted small">Expected: {{ $expectedIn }}</div>
            @endif
          </div>
          <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
            <i class="bi bi-box-arrow-in-right fs-4"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Clock Out</div>
            <div class="fs-4 fw-bold">{{ $clockOut }}</div>
            @if($expectedOut)
              <div class="text-muted small">Expected: {{ $expectedOut }}</div>
            @endif
          </div>
          <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
            <i class="bi bi-box-arrow-right fs-4"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Regular Hours</div>
        <div class="fs-4 fw-bold">{{ $regular }}</div>
        <div class="text-muted small">hrs</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">OT (Regular)</div>
        <div class="fs-4 fw-bold">{{ $otReg }}</div>
        <div class="text-muted small">hrs</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">OT (Holiday)</div>
        <div class="fs-4 fw-bold">{{ $otHol }}</div>
        <div class="text-muted small">hrs</div>
      </div>
    </div>
  </div>
</div>

{{-- Details section --}}
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="mb-0">Attendance Notes</h6>
      <div>
        @if($late > 0)
          <span class="badge bg-warning text-dark me-1"><i class="bi bi-exclamation-triangle me-1"></i>Late: {{ $late }}m</span>
        @endif
        @if($early > 0)
          <span class="badge bg-info text-dark"><i class="bi bi-arrow-return-left me-1"></i>Early: {{ $early }}m</span>
        @endif
        @if($late === 0 && $early === 0 && !$attendance->is_absent)
          <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>On Time</span>
        @endif
      </div>
    </div>

    <div class="text-muted small mb-2">Remarks</div>
    <div class="p-3 bg-light rounded">
      <div class="fw-semibold">{{ $remarks !== '' ? $remarks : '-' }}</div>
    </div>

    {{-- Optional: location/mac info if you store them --}}
    @php
      $mac = $attendance->device_mac ?? null;
      $lat = $attendance->punch_latitude ?? null;
      $lng = $attendance->punch_longitude ?? null;
    @endphp

    @if($mac || ($lat && $lng))
      <hr class="my-3">
      <div class="row g-3">
        @if($mac)
          <div class="col-md-6">
            <div class="text-muted small">Device MAC</div>
            <div class="fw-semibold">{{ $mac }}</div>
          </div>
        @endif
        @if($lat && $lng)
          <div class="col-md-6">
            <div class="text-muted small">Punch Location</div>
            <div class="fw-semibold">{{ $lat }}, {{ $lng }}</div>
          </div>
        @endif
      </div>
    @endif
  </div>
</div>

@php
  $hm = function($hours){
      $mins = (int) round(((float)$hours) * 60);
      $h = intdiv($mins, 60);
      $m = $mins % 60;
      return sprintf('%02d:%02d', $h, $m);
  };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
  <div>
    <div class="h6 mb-1">{{ $employee->user->name ?? 'N/A' }}</div>
    <div class="small text-muted">Period: {{ $start }} → {{ $end }}</div>
  </div>

  <div class="text-end small">
    @php
      $sumReg = 0; $sumOtR = 0; $sumOtH = 0;
      foreach($days as $d){
        if($d['attendance']){
          $sumReg += (float)$d['attendance']->regular_hours;
          $sumOtR += (float)$d['attendance']->overtime_regular;
          $sumOtH += (float)$d['attendance']->overtime_holiday;
        }
      }
      $sumOt = $sumOtR + $sumOtH;
      $sumAll = $sumReg + $sumOt;
@endphp
    <div><span class="text-muted">Regular:</span> <strong>{{ $hm($sumReg) }}</strong></div>
    <div><span class="text-muted">OT:</span> <strong>{{ $hm($sumOt) }}</strong></div>
    <div><span class="text-muted">Total:</span> <strong>{{ $hm($sumAll) }}</strong></div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-sm table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:140px;">Day</th>
        <th>Date</th>
        <th>Status</th>
        <th>Clock In</th>
        <th>Clock Out</th>
        <th>Regular</th>
        <th>OT</th>
        <th>Late/Early</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
      @foreach($days as $d)
        @php
          
          $dateObj = $d['date'];
          $a = $d['attendance'];

          $status = 'No Record';
          $clockIn = '-';
          $clockOut = '-';
          $reg = '00:00';
          $ot = '00:00';
          $lateEarly = '-';
          $remarks = '-';
          $rowClass = '';

          if ($a) {
            if ($a->is_absent) {
              $status = 'Absent';
              $rowClass = 'table-warning';
            } else {
              $status = $a->is_holiday ? 'Holiday' : ($a->is_working_day ? 'Working Day' : 'Non-Working');
            }

            $clockIn  = $a->clock_in ? $a->clock_in->format('H:i') : '-';
            $clockOut = $a->clock_out ? $a->clock_out->format('H:i') : '-';

            $reg = $hm($a->regular_hours ?? 0);
            $ot  = $hm(($a->overtime_regular ?? 0) + ($a->overtime_holiday ?? 0));

            $late = (int) round($a->late_minutes ?? 0);
            $early = (int) round($a->early_departure_minutes ?? 0);
            $lateEarly = "Late {$late}m / Early {$early}m";

            $remarks = $a->remarks ?: '-';
          }
@endphp

        <tr class="{{ $rowClass }}">
          <td><strong>{{ $dateObj->format('D') }}</strong></td>
          <td>{{ $dateObj->format('jS M Y') }}</td>
          <td>{{ $status }}</td>
          <td>{{ $clockIn }}</td>
          <td>{{ $clockOut }}</td>
          <td><strong>{{ $reg }}</strong></td>
          <td><span class="badge bg-warning text-dark">{{ $ot }}</span></td>
          <td class="small text-muted">{{ $lateEarly }}</td>
          <td>{{ \Illuminate\Support\Str::limit($remarks, 40) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
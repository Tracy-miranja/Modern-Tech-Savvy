@php
  // convert HH:MM:SS to minutes since 00:00
  $toMin = function($t){
    if(!$t) return 0;
    [$h,$m,$s] = array_pad(explode(':', $t), 3, 0);
    return ((int)$h)*60 + ((int)$m);
  };
@endphp

<div class="mb-3">
  <div class="h6 mb-1">{{ $employee->user->name ?? 'Employee' }}</div>
  <div class="small text-muted">Active schedules timeline (24hr)</div>
</div>

<div class="timeline-wrap border rounded p-3">
  <div class="timeline-header d-flex justify-content-between small text-muted mb-2">
    <span>00:00</span><span>06:00</span><span>12:00</span><span>18:00</span><span>24:00</span>
  </div>

  <div class="timeline-grid position-relative" style="height: 140px;">
    <div class="position-absolute top-0 bottom-0 start-0 end-0" style="background: repeating-linear-gradient(
      to right,
      rgba(0,0,0,0.05) 0,
      rgba(0,0,0,0.05) 1px,
      transparent 1px,
      transparent calc(100%/24)
    );"></div>

    @foreach($schedules as $i => $s)
      @php
        $shift = $s->shift;
        if(!$shift) continue;

        $startMin = $toMin($shift->start_time);
        $endMin = $toMin($shift->end_time);

        // overnight: treat end as next day
        if ($endMin <= $startMin) $endMin += 1440;

        // clamp to 0..1440 for display (shows overnight as wrapping - simplified)
        $left = max(0, min(1440, $startMin));
        $width = max(10, min(1440, $endMin) - $left);

        $leftPct = ($left/1440)*100;
        $widthPct = ($width/1440)*100;
      @endphp

      <div class="mb-2">
        <div class="small fw-semibold mb-1">
          {{ $shift->name }} <span class="text-muted">({{ substr($shift->start_time,0,5) }} - {{ substr($shift->end_time,0,5) }})</span>
          <span class="text-muted">• Effective: {{ $s->effective_from }} → {{ $s->effective_to ?? 'Open' }}</span>
        </div>

        <div class="position-relative" style="height: 22px; background:#f6f7fb; border-radius: 10px;">
          <div class="position-absolute top-0 bottom-0"
               style="left: {{ $leftPct }}%; width: {{ $widthPct }}%; background:#0d6efd; border-radius:10px;">
          </div>
        </div>
      </div>
    @endforeach

    @if($schedules->isEmpty())
      <div class="alert alert-info mb-0">No active schedules for this employee.</div>
    @endif
  </div>
</div>
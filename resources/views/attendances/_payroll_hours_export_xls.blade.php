@php
  $hm = function($hours){
      $mins = (int) round(((float)$hours) * 60);
      $h = intdiv($mins, 60);
      $m = $mins % 60;
      return sprintf('%02d:%02d', $h, $m);
  };
@endphp

<table border="1">
  <thead>
    <tr>
      <th colspan="7">Payroll Hours Summary ({{ $start }} to {{ $end }})</th>
    </tr>
    <tr>
      <th>Employee</th>
      <th>Total Regular</th>
      <th>OT Regular</th>
      <th>OT Holiday</th>
      <th>Total OT</th>
      <th>Total (Regular + OT)</th>
    </tr>
  </thead>
  <tbody>
    @foreach($rows as $r)
      @php
        $emp = $employees->get($r->employee_id);
        $name = $emp?->user?->name ?? 'N/A';
        $reg = (float) $r->total_regular_hours;
        $otR = (float) $r->total_ot_regular;
        $otH = (float) $r->total_ot_holiday;
        $otT = $otR + $otH;
        $grand = $reg + $otT;
      @endphp
      <tr>
        <td>{{ $name }}</td>
        <td>{{ $hm($reg) }}</td>
        <td>{{ $hm($otR) }}</td>
        <td>{{ $hm($otH) }}</td>
        <td>{{ $hm($otT) }}</td>
        <td>{{ $hm($grand) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

@php use App\Support\TimeFmt; @endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h6 class="mb-1">Payroll Hours Summary</h6>
    <div class="text-muted small">
      Period: <strong>{{ $start->format('d M Y') }}</strong> → <strong>{{ $end->format('d M Y') }}</strong>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-striped table-hover" id="payrollHoursTable">
    <thead>
      <tr>
        <th>Employee</th>
        <th class="text-center">Days Present</th>

        <th class="text-end">Total Regular</th>
        <th class="text-end">OT (Regular)</th>
        <th class="text-end">OT (Holiday/Double)</th>

        <th class="text-end">Total OT</th>
        <th class="text-end">Total Hours Worked</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
        <tr>
          <td>{{ $r['employee_name'] }}</td>
          <td class="text-center">{{ $r['days_present'] }}</td>

          <td class="text-end">
            <strong>{{ TimeFmt::hoursToHm($r['total_regular']) }}</strong>
            <div class="text-muted small">{{ number_format($r['total_regular'], 2) }}</div>
          </td>

          <td class="text-end">
            {{ TimeFmt::hoursToHm($r['total_ot_regular']) }}
            <div class="text-muted small">{{ number_format($r['total_ot_regular'], 2) }}</div>
          </td>

          <td class="text-end">
            {{ TimeFmt::hoursToHm($r['total_ot_holiday']) }}
            <div class="text-muted small">{{ number_format($r['total_ot_holiday'], 2) }}</div>
          </td>

          <td class="text-end">
            <span class="fw-bold">{{ TimeFmt::hoursToHm($r['total_ot']) }}</span>
            <div class="text-muted small">{{ number_format($r['total_ot'], 2) }}</div>
          </td>

          <td class="text-end">
            <span class="fw-bold">{{ TimeFmt::hoursToHm($r['total_hours']) }}</span>
            <div class="text-muted small">{{ number_format($r['total_hours'], 2) }}</div>
          </td>
        </tr>
      @endforeach
    </tbody>

    @php
      $sumRegular = collect($rows)->sum('total_regular');
      $sumOtReg   = collect($rows)->sum('total_ot_regular');
      $sumOtHol   = collect($rows)->sum('total_ot_holiday');
      $sumOt      = collect($rows)->sum('total_ot');
      $sumTotal   = collect($rows)->sum('total_hours');
    @endphp

    <tfoot>
      <tr class="table-secondary fw-bold">
        <td colspan="2">TOTAL</td>
        <td class="text-end">{{ TimeFmt::hoursToHm($sumRegular) }}</td>
        <td class="text-end">{{ TimeFmt::hoursToHm($sumOtReg) }}</td>
        <td class="text-end">{{ TimeFmt::hoursToHm($sumOtHol) }}</td>
        <td class="text-end">{{ TimeFmt::hoursToHm($sumOt) }}</td>
        <td class="text-end">{{ TimeFmt::hoursToHm($sumTotal) }}</td>
      </tr>
    </tfoot>
  </table>
</div>

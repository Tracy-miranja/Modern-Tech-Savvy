@php
  // Convert float hours -> HH:MM (display only)
  $hm = function($hours){
      $mins = (int) round(((float)$hours) * 60);
      $h = intdiv($mins, 60);
      $m = $mins % 60;
      return sprintf('%02d:%02d', $h, $m);
  };
@endphp

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div class="small text-muted">
    Period: <strong>{{ $start }}</strong> to <strong>{{ $end }}</strong>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-sm btn-outline-success" onclick="exportPayrollHoursExcel({ mode: 'filtered' })">
      <i class="bi bi-file-earmark-excel"></i> Export (Filtered)
    </button>

    <button class="btn btn-sm btn-success" onclick="exportPayrollHoursExcel({ mode: 'all' })">
      <i class="bi bi-download"></i> Export (All)
    </button>
  </div>
</div>

<table class="table table-striped table-hover" id="payrollHoursTable">
  <thead>
    <tr>
      <th>Employee</th>
      <th>Total Regular</th>
      <th>OT Regular</th>
      <th>OT Holiday</th>
      <th>Total OT</th>
      <th>Total (Regular + OT)</th>
      <th>Actions</th>
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
        <td><strong>{{ $hm($reg) }}</strong></td>
        <td>{{ $hm($otR) }}</td>
        <td>{{ $hm($otH) }}</td>
        <td><span class="badge bg-warning text-dark">{{ $hm($otT) }}</span></td>
        <td><span class="badge bg-primary">{{ $hm($grand) }}</span></td>
        <td>
          <button
            class="btn btn-sm btn-info"
            onclick="viewPayrollEmployeeDetails(this)"
            data-employee="{{ $r->employee_id }}"
            title="View daily breakdown"
          >
            <i class="bi bi-eye"></i> Breakdown
          </button>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

@if($rows->isEmpty())
  <div class="alert alert-info mb-0">No records found for the selected period.</div>
@endif

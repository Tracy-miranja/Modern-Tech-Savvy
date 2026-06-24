<x-app-layout title='{{ $page }}'>
  <div style="padding:24px;">

    {{-- HEADER --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
      <div>
        <div style="font-size:22px;font-weight:950;color:#111;">{{ $page }}</div>
        <div style="font-size:13px;color:#6b7280;margin-top:2px;">{{ $description }}</div>
      </div>
      <div style="display:flex;gap:8px;">
        <button onclick="refreshWarnings()" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;cursor:pointer;">
          <i class="bi bi-arrow-clockwise"></i>
        </button>
        <button onclick="openWarningModal()" style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-weight:700;font-size:13px;cursor:pointer;">
          <i class="bi bi-plus-lg me-1"></i> New Warning
        </button>
      </div>
    </div>

    {{-- KPI CARDS --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;">
      @php
        $total    = $warnings->count();
        $active   = $warnings->where('status','active')->count();
        $resolved = $warnings->where('status','resolved')->count();
        $thisMonth= $warnings->filter(fn($w)=>\Carbon\Carbon::parse($w->issue_date)->isCurrentMonth())->count();
      @endphp
      @foreach([
        ['label'=>'Total Warnings','value'=>$total,    'color'=>'#6366f1','icon'=>'bi-exclamation-triangle'],
        ['label'=>'Active',        'value'=>$active,   'color'=>'#ef4444','icon'=>'bi-exclamation-circle'],
        ['label'=>'Resolved',      'value'=>$resolved, 'color'=>'#10b981','icon'=>'bi-check-circle'],
        ['label'=>'This Month',    'value'=>$thisMonth,'color'=>'#f59e0b','icon'=>'bi-calendar3'],
      ] as $kpi)
      <div style="background:#fff;border-radius:12px;padding:16px 18px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 4px rgba(0,0,0,.07);border:1px solid #f1f1f1;">
        <div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#9ca3af;margin-bottom:4px;">{{ $kpi['label'] }}</div>
          <div style="font-size:26px;font-weight:900;color:#111;">{{ $kpi['value'] }}</div>
        </div>
        <div style="width:42px;height:42px;border-radius:10px;background:{{ $kpi['color'] }}18;display:flex;align-items:center;justify-content:center;">
          <i class="bi {{ $kpi['icon'] }}" style="font-size:18px;color:{{ $kpi['color'] }};"></i>
        </div>
      </div>
      @endforeach
    </div>

    {{-- TABLE CARD --}}
    <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);border:1px solid #f1f1f1;overflow:hidden;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;gap:10px;">
        <div style="font-weight:800;font-size:14px;color:#111;">
          Warning Records
          <span id="warningCount" style="background:#f3f4f6;color:#6b7280;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;margin-left:6px;">{{ $warnings->count() }}</span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          {{-- Date filter --}}
          <div style="position:relative;">
            <input type="date" id="dateFilter"
              style="border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px 7px 34px;font-size:13px;cursor:pointer;"
              onchange="filterByDate(this.value)">
            <i class="bi bi-calendar3" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;"></i>
          </div>
          {{-- Status filter --}}
          <select id="statusFilter" onchange="filterByStatus(this.value)"
            style="border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px;font-size:13px;">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="resolved">Resolved</option>
          </select>
          {{-- Search --}}
          <input type="text" id="warningSearch" placeholder="Search..."
            style="border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px;font-size:13px;width:200px;"
            oninput="filterWarningsTable()">
        </div>
      </div>

      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <thead>
            <tr style="background:#f9fafb;">
              @foreach(['Employee','Reason','Issue Date','Issued By','Status','Actions'] as $th)
              <th style="padding:11px 20px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;border-bottom:1px solid #f3f4f6;">{{ $th }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody id="warningsTableBody">
            @include('employees.warning._rows', ['warnings' => $warnings])
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- MODAL --}}
  <div id="warningModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:560px;margin:20px auto;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f3f4f6;position:sticky;top:0;background:#fff;z-index:1;">
        <div style="font-size:17px;font-weight:900;color:#111;" id="warningModalTitle">New Warning</div>
        <button onclick="closeWarningModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#9ca3af;line-height:1;">&times;</button>
      </div>
      <div style="padding:24px;" id="warningFormContainer">
        {{-- loaded dynamically --}}
      </div>
    </div>
  </div>

  @push('scripts')
<script src="{{ asset('js/main/warnings.js') }}" type="module"></script>
<script>
  const csrfToken       = '{{ csrf_token() }}';
  const warningsIndexUrl = '{{ route("business.employees.warning", $currentBusiness->slug) }}';
  const warningsFetchUrl = '{{ route("business.warnings.fetch",    $currentBusiness->slug) }}';
  const warningsEditUrl  = '{{ route("business.warnings.edit",     $currentBusiness->slug) }}';

  function showWarningModal(title) {
    document.getElementById('warningModalTitle').textContent = title;
    document.getElementById('warningFormContainer').innerHTML =
      '<div style="text-align:center;padding:30px;color:#9ca3af;">Loading...</div>';
    document.getElementById('warningModal').style.display = 'flex';
  }

  function closeWarningModal() {
    document.getElementById('warningModal').style.display = 'none';
  }

  function openWarningModal() {
    showWarningModal('New Warning');
    fetch(warningsEditUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ warning_id: null })
    })
    .then(r => r.json())
    .then(d => {
      document.getElementById('warningFormContainer').innerHTML = d.data;
    })
    .catch(() => closeWarningModal());
  }

  function editWarning(id) {
    showWarningModal('Edit Warning');
    fetch(warningsEditUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ warning_id: id })
    })
    .then(r => r.json())
    .then(d => {
      document.getElementById('warningFormContainer').innerHTML = d.data;
    })
    .catch(() => closeWarningModal());
  }

  function refreshWarnings() {
    fetch(warningsFetchUrl, {
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
      document.getElementById('warningsTableBody').innerHTML = d.data.html;
      document.getElementById('warningCount').textContent = d.data.count;
    });
  }

  function filterWarningsTable() {
    const search = (document.getElementById('warningSearch')?.value || '').toLowerCase();
    const status = (document.getElementById('statusFilter')?.value || '').toLowerCase();
    const date   =  document.getElementById('dateFilter')?.value || '';
    document.querySelectorAll('#warningsTableBody tr[data-date]').forEach(row => {
      const matchSearch = !search || row.textContent.toLowerCase().includes(search);
      const matchStatus = !status || (row.dataset.status || '') === status;
      const matchDate   = !date   || (row.dataset.date   || '') === date;
      row.style.display = (matchSearch && matchStatus && matchDate) ? '' : 'none';
    });
  }

  function filterByDate(val)   { filterWarningsTable(); }
  function filterByStatus(val) { filterWarningsTable(); }

  document.getElementById('warningModal').addEventListener('click', function(e) {
    if (e.target === this) closeWarningModal();
  });
</script>
@endpush
</x-app-layout>

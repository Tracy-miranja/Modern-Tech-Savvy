<x-app-layout title='{{ $page }}'>
  <div style="padding:24px;">

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
          <i class="bi bi-plus-lg me-1"></i> New Case
        </button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;">
      @php
        $open           = $warnings->where('stage', '!=', 'closed')->count();
        $investigation  = $warnings->where('stage', 'investigation')->count();
        $pendingHearing = $warnings->whereIn('stage', ['notification_to_hearing', 'disciplinary_hearing'])->count();
        $closed         = $warnings->where('stage', 'closed')->count();
      @endphp
      @foreach([
        ['label'=>'Open Cases',        'value'=>$open,           'color'=>'#f59e0b'],
        ['label'=>'Under Investigation','value'=>$investigation, 'color'=>'#6366f1'],
        ['label'=>'Pending Hearing',    'value'=>$pendingHearing,'color'=>'#ec4899'],
        ['label'=>'Closed',             'value'=>$closed,        'color'=>'#10b981'],
      ] as $kpi)
      <div style="background:#fff;border-radius:12px;padding:16px 18px;box-shadow:0 1px 4px rgba(0,0,0,.07);border:1px solid #f1f1f1;">
        <div style="font-size:13px;color:#6b7280;margin-bottom:6px;">{{ $kpi['label'] }}</div>
        <div style="font-size:26px;font-weight:900;color:{{ $kpi['color'] }};" id="kpi-{{ \Illuminate\Support\Str::slug($kpi['label']) }}">{{ $kpi['value'] }}</div>
      </div>
      @endforeach
    </div>

    <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);border:1px solid #f1f1f1;overflow:hidden;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;gap:10px;">
        <input type="text" id="warningSearch" placeholder="Search cases..."
          style="border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px;font-size:13px;width:220px;"
          oninput="debouncedRefresh()">

        <div style="display:flex;gap:8px;align-items:center;">
          <select id="stageFilter" onchange="refreshWarnings()" style="border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px;font-size:13px;">
            <option value="">All Stage</option>
            @foreach(\App\Models\Warning::STAGES as $stage)
              <option value="{{ $stage }}">{{ \App\Models\Warning::label($stage) }}</option>
            @endforeach
          </select>
          <select id="categoryFilter" onchange="refreshWarnings()" style="border:1px solid #e5e7eb;border-radius:8px;padding:7px 12px;font-size:13px;">
            <option value="">All Category</option>
            @foreach(\App\Models\Warning::CATEGORIES as $cat)
              <option value="{{ $cat }}">{{ \App\Models\Warning::label($cat) }}</option>
            @endforeach
          </select>
          <span id="warningCount" style="color:#6b7280;font-size:12px;">{{ $warnings->count() }} records</span>
        </div>
      </div>

      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <thead>
            <tr style="background:#f9fafb;">
              @foreach(['Case ID','Employee','Category','Offence','Reported','Stage','Outcome','Actions'] as $th)
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

  <div id="warningModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:560px;margin:20px auto;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f3f4f6;position:sticky;top:0;background:#fff;z-index:1;">
        <div style="font-size:17px;font-weight:900;color:#111;" id="warningModalTitle">New Disciplinary Case</div>
        <button onclick="closeWarningModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#9ca3af;line-height:1;">&times;</button>
      </div>
      <div style="padding:24px;" id="warningFormContainer"></div>
    </div>
  </div>

  @push('scripts')
<script>
  const csrfToken        = '{{ csrf_token() }}';
  const warningsFetchUrl = '{{ route("business.warnings.fetch", $currentBusiness->slug) }}';
  const warningsEditUrl  = '{{ route("business.warnings.edit",  $currentBusiness->slug) }}';
  const warningsStoreUrl = '{{ route("business.warnings.store", $currentBusiness->slug) }}';
  const warningsUpdateUrlBase = '{{ url("business/{$currentBusiness->slug}/warnings") }}';
  const warningsShowUrlBase   = warningsUpdateUrlBase;

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
    showWarningModal('New Disciplinary Case');
    fetch(warningsEditUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ warning_id: null })
    }).then(r => r.json()).then(d => {
      document.getElementById('warningFormContainer').innerHTML = d.data;
    }).catch(() => closeWarningModal());
  }

  function editWarning(id) {
    showWarningModal('Edit Disciplinary Case');
    fetch(warningsEditUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ warning_id: id })
    }).then(r => r.json()).then(d => {
      document.getElementById('warningFormContainer').innerHTML = d.data;
    }).catch(() => closeWarningModal());
  }

  function viewWarning(id) {
    showWarningModal('Case Details');
    fetch(`${warningsShowUrlBase}/${id}/show`, {
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
      document.getElementById('warningFormContainer').innerHTML = d.data;
    }).catch(() => closeWarningModal());
  }

  function deleteWarning(id) {
    if (!confirm('Delete this disciplinary case? This cannot be undone.')) return;
    fetch(`${warningsUpdateUrlBase}/${id}/delete`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).then(r => r.json()).then(() => refreshWarnings());
  }

  function saveWarning(btn) {
    const form = document.getElementById('warningForm');
    const warningId = form.querySelector('[name="warning_id"]')?.value;
    const formData = new FormData(form);
    const url = warningId ? `${warningsUpdateUrlBase}/${warningId}/update` : warningsStoreUrl;

    btn.disabled = true;
    fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: formData
    })
    .then(r => r.json())
    .then(d => {
      if (d.success === false) {
        alert(d.message || 'Failed to save case.');
        btn.disabled = false;
        return;
      }
      closeWarningModal();
      refreshWarnings();
    })
    .catch(() => { btn.disabled = false; alert('Something went wrong.'); });
  }

  let refreshTimer = null;
  function debouncedRefresh() {
    clearTimeout(refreshTimer);
    refreshTimer = setTimeout(refreshWarnings, 350);
  }

 function refreshWarnings() {
    const payload = {
      search:   document.getElementById('warningSearch')?.value   || '',
      stage:    document.getElementById('stageFilter')?.value     || '',
      category: document.getElementById('categoryFilter')?.value || '',
    };

    fetch(warningsFetchUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(async r => {
      const d = await r.json();
      if (!r.ok) {
        console.error('Failed to load warnings:', d);
        return;
      }
      document.getElementById('warningsTableBody').innerHTML = d.data.html;
      document.getElementById('warningCount').textContent = d.data.count + ' records';
      const c = d.data.counts;
      document.getElementById('kpi-open-cases').textContent = c.open;
      document.getElementById('kpi-under-investigation').textContent = c.investigation;
      document.getElementById('kpi-pending-hearing').textContent = c.pending_hearing;
      document.getElementById('kpi-closed').textContent = c.closed;
    });
  }

  document.getElementById('warningModal').addEventListener('click', function(e) {
    if (e.target === this) closeWarningModal();
  });
</script>
@endpush
</x-app-layout>

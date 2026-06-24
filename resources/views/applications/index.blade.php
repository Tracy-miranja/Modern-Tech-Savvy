<x-app-layout>
  <div class="apps-page">
    {{-- KPI ROW --}}
<div class="mb-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
    @foreach([
        ['label'=>'Total','id'=>'kpiTotal','icon'=>'bi-file-earmark-text','color'=>'#6366f1'],
        ['label'=>'Pending','id'=>'kpiPending','icon'=>'bi-clock','color'=>'#f59e0b'],
        ['label'=>'Under Review','id'=>'kpiReview','icon'=>'bi-eye','color'=>'#3b82f6'],
        ['label'=>'Shortlisted','id'=>'kpiShortlisted','icon'=>'bi-check-circle','color'=>'#10b981'],
        ['label'=>'Rejected','id'=>'kpiRejected','icon'=>'bi-x-circle','color'=>'#ef4444'],
        ['label'=>'Interviewed','id'=>'kpiInterviewed','icon'=>'bi-people','color'=>'#8b5cf6'],
    ] as $kpi)
    <div style="background:#fff;border-radius:12px;padding:16px 18px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid #f1f1f1;">
        <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#9ca3af;margin-bottom:4px;">{{ $kpi['label'] }}</div>
            <div id="{{ $kpi['id'] }}" style="font-size:26px;font-weight:900;color:#111;">—</div>
        </div>
        <div style="width:42px;height:42px;border-radius:10px;background:{{ $kpi['color'] }}18;display:flex;align-items:center;justify-content:center;">
            <i class="bi {{ $kpi['icon'] }}" style="font-size:18px;color:{{ $kpi['color'] }};"></i>
        </div>
    </div>
    @endforeach
</div>

{{-- FILTER CARD --}}
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid #f1f1f1;margin-bottom:16px;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:14px;">
        <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;display:block;margin-bottom:5px;">Job Position</label>
            <select id="jobFilter" class="form-select" style="border-radius:8px;border:1px solid #e5e7eb;font-size:13px;">
                <option value="">All Positions</option>
                @foreach ($jobPosts as $job)
                    <option value="{{ $job->id }}">{{ $job->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;display:block;margin-bottom:5px;">Status</label>
            <select id="statusFilter" class="form-select" style="border-radius:8px;border:1px solid #e5e7eb;font-size:13px;">
                <option value="">All Status</option>
                <option value="applied">Applied</option>
                <option value="pending">Pending</option>
                <option value="under_review">Under Review</option>
                <option value="shortlisted">Shortlisted</option>
                <option value="in_progress">In Progress</option>
                <option value="rejected">Rejected</option>
                <option value="finished">Finished</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;display:block;margin-bottom:5px;">County</label>
            <input type="text" id="locationFilter" class="form-control" placeholder="All Counties"
                style="border-radius:8px;border:1px solid #e5e7eb;font-size:13px;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;display:block;margin-bottom:5px;">Search</label>
            <input type="text" id="applicationFilter" class="form-control" placeholder="Name, email, or ref..."
                style="border-radius:8px;border:1px solid #e5e7eb;font-size:13px;">
        </div>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:12px;border-top:1px solid #f3f4f6;">
        <button class="btn btn-apply" id="btnApplyFilters" type="button"
            style="background:#f97316;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:700;">
            <i class="bi bi-funnel"></i> Apply Filters
        </button>
        <a class="btn btn-light" href="{{ route('business.applicants.create', $currentBusiness->slug) }}"
            style="border-radius:8px;font-size:13px;font-weight:600;border:1px solid #e5e7eb;">
            <i class="bi bi-person-add me-1"></i> Add Applicant
        </a>
        <a class="btn btn-light" href="{{ route('business.applications.create', $currentBusiness->slug) }}"
            style="border-radius:8px;font-size:13px;font-weight:600;border:1px solid #e5e7eb;">
            <i class="bi bi-plus-square-dotted me-1"></i> Create Application
        </a>
    </div>
</div>

    {{-- BULK BAR --}}
    <div class="apps-bulkbar mb-3">
      <div class="left">
        <div class="form-check m-0">
          <input class="form-check-input" type="checkbox" id="selectAllTop">
          <label class="form-check-label" for="selectAllTop">Select All</label>
        </div>

        <select id="bulkAction" class="form-select" style="width: 200px;">
          <option value="">Bulk Actions</option>
          <option value="shortlist">Shortlist</option>
          <option value="reject">Reject</option>
          <option value="delete">Delete</option>
          <option value="stage:applied">Stage: Applied</option>
          <option value="stage:shortlisted">Stage: Shortlisted</option>
          <option value="stage:in_progress">Stage: In Progress</option>
          <option value="stage:rejected">Stage: Rejected</option>
          <option value="stage:finished">Stage: Finished</option>
        </select>

        <button class="btn btn-bulk" id="btnBulkApply" type="button">
          <i class="bi bi-lightning-charge"></i> Apply
        </button>
      </div>
    </div>

    {{-- TABLE --}}
    <div class="apps-table-wrap" id="jobApplicationsContainer">
      {{ loader() }}
    </div>
  </div>

  @push('scripts')
    @include('modals.schedule-interview')
    <script src="{{ asset('js/main/job-applications.js') }}" type="module"></script>

    <script>
      const csrfToken = '{{ csrf_token() }}';

      // ── KPIs ────────────────────────────────────────────────────
      async function loadApplicationKpis() {
        try {
          const res = await fetch('{{ route("business.applications.kpis", $currentBusiness->slug) }}', {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
          });
          const data = await res.json();
          document.getElementById('kpiTotal').textContent       = data.total        ?? '0';
          document.getElementById('kpiPending').textContent     = data.pending       ?? '0';
          document.getElementById('kpiReview').textContent      = data.under_review  ?? '0';
          document.getElementById('kpiShortlisted').textContent = data.shortlisted   ?? '0';
          document.getElementById('kpiRejected').textContent    = data.rejected      ?? '0';
          document.getElementById('kpiInterviewed').textContent = data.interviewed   ?? '0';
        } catch(e) {
          console.error('KPI fetch failed', e);
        }
      }

      // ── Interview modal ──────────────────────────────────────────
      window.openScheduleInterviewModal = function(applicationId, applicantName, jobTitle) {
        document.getElementById('application_id_input').value = applicationId;
        document.getElementById('applicant_name').textContent = applicantName;
        new bootstrap.Modal(document.getElementById('scheduleInterviewModal')).show();
      };

      window.scheduleInterview = async function(btn) {
        btn.disabled = true;
        btn.textContent = 'Scheduling...';
        const formData = new FormData(document.getElementById('scheduleInterviewForm'));
        try {
          const res = await fetch('{{ route("business.interviews.store", $currentBusiness->slug) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
          });
          const data = await res.json();
          if (res.ok) {
            toastr?.success?.('Interview scheduled successfully');
            bootstrap.Modal.getInstance(document.getElementById('scheduleInterviewModal'))?.hide();
            document.getElementById('scheduleInterviewForm').reset();
            loadApplicationKpis();
          } else {
            toastr?.error?.(data.message || 'Failed to schedule interview');
          }
        } catch(e) {
          toastr?.error?.('Something went wrong');
        } finally {
          btn.disabled = false;
          btn.textContent = 'Schedule';
        }
      };

      // ── Filters ──────────────────────────────────────────────────
      document.getElementById('btnApplyFilters')?.addEventListener('click', () => {
        filterJobApplications(
          1,
          document.getElementById('applicationFilter')?.value || '',
          document.getElementById('jobFilter')?.value || '',
          document.getElementById('locationFilter')?.value || ''
        );
      });

      document.getElementById('applicationFilter')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') document.getElementById('btnApplyFilters')?.click();
      });

      document.getElementById('selectAllTop')?.addEventListener('change', function() {
        document.querySelectorAll('input[name="application_ids[]"]').forEach(cb => cb.checked = this.checked);
      });

      // ── Bulk actions ─────────────────────────────────────────────
      document.getElementById('btnBulkApply')?.addEventListener('click', async () => {
        const action = document.getElementById('bulkAction')?.value;
        if (!action) return toastr?.warning?.('Pick a bulk action first');
        const ids = Array.from(document.querySelectorAll('input[name="application_ids[]"]:checked')).map(i => i.value);
        if (!ids.length) return toastr?.warning?.('Select at least one application');

        if (action === 'shortlist') return shortlistApplications(document.getElementById('btnBulkApply'), ids);
        if (action === 'delete')    return deleteJobApplications(document.getElementById('btnBulkApply'));
        if (action === 'reject')    return updateApplicationStage(document.getElementById('btnBulkApply'), 'rejected', ids);
        if (action.startsWith('stage:')) {
          return updateApplicationStage(document.getElementById('btnBulkApply'), action.split(':')[1], ids);
        }
      });

      // ── Init ─────────────────────────────────────────────────────
      document.addEventListener('DOMContentLoaded', () => {
        getJobApplications();
        loadApplicationKpis();
      });
    </script>
@endpush
</x-app-layout>

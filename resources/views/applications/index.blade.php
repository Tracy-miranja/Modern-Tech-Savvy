<x-app-layout>
  <div class="apps-page">

    <div class="apps-kpis mb-3">
      <div class="apps-kpi">
        <div>
          <div class="label">Total</div>
          <div class="value" id="kpiTotal">—</div>
        </div>
        <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
      </div>

      <div class="apps-kpi">
        <div>
          <div class="label">Pending</div>
          <div class="value" id="kpiPending">—</div>
        </div>
        <div class="icon"><i class="bi bi-clock"></i></div>
      </div>

      <div class="apps-kpi">
        <div>
          <div class="label">Under Review</div>
          <div class="value" id="kpiReview">—</div>
        </div>
        <div class="icon"><i class="bi bi-eye"></i></div>
      </div>

      <div class="apps-kpi">
        <div>
          <div class="label">Shortlisted</div>
          <div class="value" id="kpiShortlisted">—</div>
        </div>
        <div class="icon"><i class="bi bi-check-circle"></i></div>
      </div>

      <div class="apps-kpi">
        <div>
          <div class="label">Rejected</div>
          <div class="value" id="kpiRejected">—</div>
        </div>
        <div class="icon"><i class="bi bi-x-circle"></i></div>
      </div>

      <div class="apps-kpi">
        <div>
          <div class="label">Interviewed</div>
          <div class="value" id="kpiInterviewed">—</div>
        </div>
        <div class="icon"><i class="bi bi-people"></i></div>
      </div>
    </div>

    <div class="apps-filters mb-3">
      <div class="grid">
        <div>
          <label>Job Position</label>
          <select id="jobFilter" class="form-select">
            <option value="">All Positions</option>
            @foreach ($jobPosts as $job)
              <option value="{{ $job->id }}">{{ $job->title }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label>Status</label>
          <select id="statusFilter" class="form-select">
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
          <label>County</label>
          <input type="text" id="locationFilter" class="form-control" placeholder="All Counties">
        </div>

        <div>
          <label>Search</label>
          <input type="text" id="applicationFilter" class="form-control" placeholder="Name, email, or ref...">
        </div>
      </div>

      <div class="mt-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-apply" id="btnApplyFilters" type="button">
          <i class="bi bi-funnel"></i> Apply Filters
        </button>

        <a class="btn btn-light" href="{{ route('business.applicants.create', $currentBusiness->slug) }}">
          <i class="bi bi-person-add me-2"></i> Add Applicant
        </a>

        <a class="btn btn-light" href="{{ route('business.applications.create', $currentBusiness->slug) }}">
          <i class="bi bi-plus-square-dotted me-2"></i> Create Application
        </a>
      </div>
    </div>

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

    <div class="apps-table-wrap" id="jobApplicationsContainer">
      {{ loader() }}
    </div>
  </div>

  @push('scripts')
    @include('modals.schedule-interview')
    <script src="{{ asset('js/main/job-applications.js') }}" type="module"></script>

    <script>
      const csrfToken = '{{ csrf_token() }}';

      // Wire buttons to your existing JS functions
      document.getElementById('btnApplyFilters')?.addEventListener('click', () => {
        filterJobApplications(
          1,
          document.getElementById('applicationFilter')?.value || '',
          document.getElementById('jobFilter')?.value || '',
          document.getElementById('locationFilter')?.value || ''
        );
      });

      // Optional: apply-on-enter for search
      document.getElementById('applicationFilter')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') document.getElementById('btnApplyFilters')?.click();
      });

      // Top select all should toggle the table checkboxes after render
      document.getElementById('selectAllTop')?.addEventListener('change', function(){
        document.querySelectorAll('input[name="application_ids[]"]').forEach(cb => cb.checked = this.checked);
      });

      // Bulk apply (uses your existing functions)
      document.getElementById('btnBulkApply')?.addEventListener('click', async () => {
        const action = document.getElementById('bulkAction')?.value;
        if (!action) return toastr?.warning?.('Pick a bulk action first');

        const ids = Array.from(document.querySelectorAll('input[name="application_ids[]"]:checked')).map(i => i.value);
        if (!ids.length) return toastr?.warning?.('Select at least one application');

        if (action === 'shortlist') return shortlistApplications(document.getElementById('btnBulkApply'), ids);
        if (action === 'delete') return deleteJobApplications(document.getElementById('btnBulkApply'));

        if (action === 'reject') return updateApplicationStage(document.getElementById('btnBulkApply'), 'rejected', ids);

        if (action.startsWith('stage:')) {
          const stage = action.split(':')[1];
          return updateApplicationStage(document.getElementById('btnBulkApply'), stage, ids);
        }
      });

      // Initial load
      document.addEventListener('DOMContentLoaded', () => getJobApplications());
    </script>
  @endpush
</x-app-layout>
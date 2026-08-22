<table class="table table-striped w-100" id="jobApplicationsTable">
  <thead>
    <tr>
      <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
      <th style="width:60px;">#</th>
      <th>Applicant</th>
      <th style="width:140px;">Phone</th>
      <th>Job Title</th>
      <th style="width:140px;">Status</th>
      <th style="width:140px;">Applied</th>
      <th style="width:220px;">Actions</th>
    </tr>
  </thead>

  <tbody>
    @forelse($applications as $index => $application)
      @php
        $stage = strtolower($application->stage ?? 'applied');
        $stage = in_array($stage, ['pending','applied','shortlisted','in_progress','rejected','finished']) ? $stage : 'applied';

        $badgeClass = match($stage){
          'pending' => 'pending',
          'shortlisted' => 'shortlisted',
          'rejected' => 'rejected',
          'in_progress' => 'in_progress',
          'finished' => 'in_progress', // you can create a finished class if you want
          default => 'applied'
        };

        $applicant = $application->applicant;
        $user = $applicant?->user;

        $displayName  = $user?->name ?: ($applicant?->fullname ?: ('Applicant #'.$applicant?->id));
        $displayEmail = $user?->email ?: null; // externals may not have email column in applicants
        $displayPhone = $user?->phone ?: ($applicant?->phone ?: '—');

        $avatarUrl = $user ? $user->getImageUrl() : asset('media/avatar.png');
      @endphp

      <tr>
        <td>
          <input type="checkbox" name="application_ids[]" value="{{ $application->id }}">
        </td>

        <td>{{ $applications->firstItem() + $index }}</td>

        <td>
          <div class="d-flex align-items-center" style="gap:10px;">
            <img
              class="img-48 border-circle"
              style="width:40px;height:40px;border-radius:999px;object-fit:cover;"
              src="{{ $avatarUrl }}"
              alt="{{ $displayName }}"
            >
            <div>
              <div style="font-weight:900;">{{ $displayName }}</div>

              @if($displayEmail)
                <div class="text-muted" style="font-size:12px;">{{ $displayEmail }}</div>
              @else
                <div class="text-muted" style="font-size:12px;">
                  <span class="badge bg-secondary">External Applicant</span>
                  @if(!empty($applicant?->idnumber))
                    <span class="ms-1">ID: {{ $applicant->idnumber }}</span>
                  @endif
                </div>
              @endif
            </div>
          </div>
        </td>

        <td>{{ $displayPhone }}</td>

        <td>{{ $application->jobPost?->title ?? '—' }}</td>

        <td>
          <span class="apps-badge {{ $badgeClass }}">
            {{ ucwords(str_replace('_',' ', $stage)) }}
          </span>
        </td>

        <td>{{ optional($application->created_at)->format('M d, Y') }}</td>

        <td class="text-nowrap">
          <a href="{{ route('business.applications.view', [$currentBusiness->slug, $application->id]) }}"
             class="apps-action-link me-3">
            <i class="bi bi-eye"></i> View
          </a>

          @if($stage !== 'shortlisted')
            <a href="javascript:void(0)" class="apps-action-link me-3"
               onclick="shortlistApplications(this, [{{ $application->id }}])">
              <i class="bi bi-check2-circle"></i> Shortlist
            </a>
          @endif

          <a href="javascript:void(0)" class="apps-action-link text-danger"
             onclick="deleteJobApplication({{ $application->id }})">
            <i class="bi bi-trash"></i> Delete
          </a>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="8" class="text-center text-muted">No applications found.</td>
      </tr>
    @endforelse
  </tbody>
</table>

<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
  <div>{{ $applications->links() }}</div>
</div>

<script>
  document.getElementById('selectAll')?.addEventListener('change', function(){
    document.querySelectorAll('input[name="application_ids[]"]').forEach(cb => cb.checked = this.checked);
  });
</script>

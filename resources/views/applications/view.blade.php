<x-app-layout>
  @php
    $applicant = $application->applicant;
    $user = $applicant?->user;

    $stage = strtolower($application->stage ?? 'applied');
    $stage = in_array($stage, ['pending','applied','shortlisted','in_progress','rejected','finished']) ? $stage : 'applied';

    $badgeClass = match($stage){
      'pending' => 'pending',
      'shortlisted' => 'shortlisted',
      'rejected' => 'rejected',
      'in_progress' => 'in_progress',
      'finished' => 'in_progress',
      default => 'applied'
    };

    $displayName  = $user?->name ?: ($applicant?->fullname ?: ('Applicant #'.$applicant?->id));
    $displayPhone = $user?->phone ?: ($applicant?->phone ?: '—');
    $displayEmail = $user?->email ?: '—';

    $nationality = $applicant?->country ?: '—';
    $isKenya = strtolower(trim((string)$nationality)) === 'kenya';
    $locationLabel = $isKenya ? 'Home County' : 'City';
    $locationValue = $isKenya ? ($applicant?->home_county ?: '—') : ($applicant?->city ?: '—');

    $dob = $applicant?->dob ? \Carbon\Carbon::parse($applicant->dob)->format('M d, Y') : '—';
    $age = $applicant?->age ?? '—';
    $gender = $applicant?->gender ? ucwords(str_replace('_',' ', $applicant->gender)) : '—';
    $plwd = isset($applicant?->plwd) ? ($applicant->plwd ? 'Yes' : 'No') : '—';

    $appCode = $application->id ? 'APP-' . str_pad($application->id, 6, '0', STR_PAD_LEFT) : 'Application';

    // group docs by type for quick display
    $docsByType = collect($documents ?? [])->groupBy('doc_type');
  @endphp

  <div class="apps-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 apps-view-actions">
      <div>
        <a href="{{ route('business.applications.index', $currentBusiness->slug) }}" class="apps-action-link">
          <i class="bi bi-arrow-left"></i> Back to Applications
        </a>

        <div style="font-size:28px;font-weight:950;margin-top:6px;">
          {{ $appCode }}
        </div>
        <div class="text-muted" style="font-weight:800;">
          {{ $application->jobPost?->title ?? '—' }}
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-orange" type="button">
          <i class="bi bi-search"></i> Screen Application
        </button>

        <button class="btn btn-dark" type="button" onclick="window.print()">
          <i class="bi bi-printer"></i> Print
        </button>
      </div>
    </div>

    <div class="row g-3">
      {{-- LEFT --}}
      <div class="col-lg-8">

        {{-- PERSONAL INFO --}}
        <div class="apps-section mb-3">
          <div class="title"><i class="bi bi-person"></i> Personal Information</div>

          <div class="row">
            <div class="col-md-6 mb-2">
              <div class="text-muted small">Full Name</div>
              <div style="font-weight:900;">{{ $displayName }}</div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">Phone Contact</div>
              <div style="font-weight:900;color:#f97316;">{{ $displayPhone }}</div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">Email Address</div>
              <div style="font-weight:900;color:#f97316;">{{ $displayEmail }}</div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">Applied On</div>
              <div style="font-weight:900;">{{ optional($application->created_at)->format('F j, Y g:i A') }}</div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">Nationality</div>
              <div style="font-weight:900;">{{ $nationality }}</div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">{{ $locationLabel }}</div>
              <div style="font-weight:900;">{{ $locationValue }}</div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">Gender</div>
              <div style="font-weight:900;">{{ $gender }}</div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">Date of Birth</div>
              <div style="font-weight:900;">{{ $dob }} <span class="text-muted">({{ $age }} yrs)</span></div>
            </div>

            <div class="col-md-6 mb-2">
              <div class="text-muted small">PLWD</div>
              <div style="font-weight:900;">{{ $plwd }}</div>
            </div>

            @if(!empty($applicant?->idnumber))
              <div class="col-md-6 mb-2">
                <div class="text-muted small">ID Number</div>
                <div style="font-weight:900;">{{ $applicant->idnumber }}</div>
              </div>
            @endif
          </div>

          @if(!empty($application->cover_letter))
            <hr class="my-3">
            <div class="text-muted small mb-1">Cover Letter</div>
            <div class="border rounded-3 p-3">
              {!! $application->cover_letter !!}
            </div>
          @endif
        </div>

        {{-- ACADEMICS --}}
        <div class="apps-section mb-3">
          <div class="title"><i class="bi bi-mortarboard"></i> Academic Qualifications</div>

          @if(empty($academics) || count($academics) === 0)
            <div class="text-muted">No academic qualifications provided.</div>
          @else
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Level</th>
                    <th>Qualification</th>
                    <th>Institution</th>
                    <th>Country</th>
                    <th>Year</th>
                    <th>Cert No.</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($academics as $i => $a)
                    <tr>
                      <td>{{ $i + 1 }}</td>
                      <td><strong>{{ $a->qualification_level }}</strong></td>
                      <td>{{ $a->qualification_name }}</td>
                      <td>{{ $a->institution_name }}</td>
                      <td>{{ $a->institution_country ?? '—' }}</td>
                      <td>{{ $a->year_completed ?? '—' }}</td>
                      <td>{{ $a->certificate_number ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            @if($docsByType->has('academic_attachment'))
              <div class="mt-2">
                <div class="text-muted small mb-2">Academic Attachments</div>
                <div class="row g-2">
                  @foreach($docsByType->get('academic_attachment') as $doc)
                    <div class="col-md-6">
                      <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                          <i class="bi bi-file-earmark-text" style="font-size:20px;color:#f97316;"></i>
                          <div>
                            <div style="font-weight:900;">{{ $doc->label ?? $doc->file_name ?? 'Academic Attachment' }}</div>
                            <div class="text-muted small">{{ $doc->mime_type ?? '' }}</div>
                          </div>
                        </div>

                        @if(!empty($doc->media_id))
                          <button class="btn btn-sm btn-light"
                            data-applicant-id="{{ $application->applicant_id }}"
                            data-media-id="{{ $doc->media_id }}"
                            onclick="downloadDocument(this)">
                            <i class="bi bi-download"></i>
                          </button>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
          @endif
        </div>

        {{-- MEMBERSHIPS --}}
        <div class="apps-section mb-3">
          <div class="title"><i class="bi bi-card-checklist"></i> Professional Memberships</div>

          @if(empty($memberships) || count($memberships) === 0)
            <div class="text-muted">No memberships provided.</div>
          @else
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Organization</th>
                    <th>Membership No.</th>
                    <th>Type</th>
                    <th>Year Joined</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($memberships as $i => $m)
                    <tr>
                      <td>{{ $i + 1 }}</td>
                      <td><strong>{{ $m->organization_name }}</strong></td>
                      <td>{{ $m->membership_number }}</td>
                      <td>{{ $m->membership_type ?? '—' }}</td>
                      <td>{{ $m->year_joined ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            @if($docsByType->has('membership_certificate'))
              <div class="mt-2">
                <div class="text-muted small mb-2">Membership Certificates</div>
                <div class="row g-2">
                  @foreach($docsByType->get('membership_certificate') as $doc)
                    <div class="col-md-6">
                      <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                          <i class="bi bi-award" style="font-size:20px;color:#f97316;"></i>
                          <div>
                            <div style="font-weight:900;">{{ $doc->label ?? $doc->file_name ?? 'Membership Certificate' }}</div>
                            <div class="text-muted small">{{ $doc->mime_type ?? '' }}</div>
                          </div>
                        </div>

                        @if(!empty($doc->media_id))
                          <button class="btn btn-sm btn-light"
                            data-applicant-id="{{ $application->applicant_id }}"
                            data-media-id="{{ $doc->media_id }}"
                            onclick="downloadDocument(this)">
                            <i class="bi bi-download"></i>
                          </button>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
          @endif
        </div>

        {{-- WORK EXPERIENCE --}}
        <div class="apps-section mb-3">
          <div class="title"><i class="bi bi-briefcase"></i> Work Experience</div>

          @if(empty($workExperiences) || count($workExperiences) === 0)
            <div class="text-muted">No work experience provided.</div>
          @else
            @foreach($workExperiences as $i => $wx)
              @php
                $start = $wx->start_date ? \Carbon\Carbon::parse($wx->start_date)->format('M Y') : '—';
                $end = ($wx->is_current ?? false) ? 'Present' : ($wx->end_date ? \Carbon\Carbon::parse($wx->end_date)->format('M Y') : '—');
              @endphp

              <div class="border rounded-3 p-3 mb-2">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                  <div>
                    <div style="font-weight:950;">{{ $wx->job_title }}</div>
                    <div class="text-muted" style="font-weight:800;">
                      {{ $wx->employer_name }}{{ $wx->location ? ' • '.$wx->location : '' }}
                    </div>
                  </div>
                  <div class="text-muted" style="font-weight:900;">
                    {{ $start }} — {{ $end }}
                  </div>
                </div>

                @if(!empty($wx->employer_contact))
                  <div class="text-muted small mt-1">Employer Contact: <strong>{{ $wx->employer_contact }}</strong></div>
                @endif

                @if(!empty($wx->achievements))
                  <div class="mt-2">
                    <div class="text-muted small mb-1">Achievements & Responsibilities</div>
                    <div class="border rounded-3 p-2">{!! nl2br(e($wx->achievements)) !!}</div>
                  </div>
                @endif
              </div>
            @endforeach
          @endif
        </div>

        {{-- DOCUMENTS --}}
        <div class="apps-section">
          <div class="title"><i class="bi bi-folder2-open"></i> Uploaded Documents</div>

          @php
            $allDocs = collect($documents ?? []);
          @endphp

          @if($allDocs->isEmpty())
            <p class="text-muted mb-0">No documents available.</p>
          @else
            <div class="row g-2">
              @foreach($allDocs as $doc)
                <div class="col-md-6">
                  <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi bi-file-earmark-text" style="font-size:20px;color:#f97316;"></i>
                      <div>
                        <div style="font-weight:900;">
                          {{ $doc->label ?? $doc->file_name ?? strtoupper($doc->doc_type) }}
                        </div>
                        <div class="text-muted small">
                          {{ strtoupper(str_replace('_',' ', $doc->doc_type)) }}
                        </div>
                      </div>
                    </div>

                    @if(!empty($doc->media_id))
                      <button class="btn btn-sm btn-light"
                        data-applicant-id="{{ $application->applicant_id }}"
                        data-media-id="{{ $doc->media_id }}"
                        onclick="downloadDocument(this)">
                        <i class="bi bi-download"></i>
                      </button>
                    @else
                      <span class="text-muted small">—</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

      </div>

      {{-- RIGHT --}}
      <div class="col-lg-4">
        <div class="apps-section apps-sticky">
          <div class="title"><i class="bi bi-clipboard-check"></i> Application Status</div>

          <div class="mb-2 text-muted small">Current Status</div>
          <div class="mb-3">
            <span class="apps-badge {{ $badgeClass }}">
              {{ ucwords(str_replace('_',' ', $stage)) }}
            </span>
          </div>

          <div class="mb-2 text-muted small">Applied On</div>
          <div style="font-weight:900;" class="mb-3">
            {{ optional($application->created_at)->format('F j, Y g:i A') }}
          </div>

          <div class="mb-2 text-muted small">Quick Actions</div>
          <div class="d-grid gap-2">
            <button class="btn btn-orange" type="button">
              <i class="bi bi-search"></i> Screen Application
            </button>

            {{-- Stage update dropdown (client-side) --}}
            <div class="dropdown">
              <button class="btn btn-dark dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-arrow-repeat"></i> Update Stage
              </button>
              <ul class="dropdown-menu w-100">
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'pending', [{{ $application->id }}])">Pending</a></li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'applied', [{{ $application->id }}])">Applied</a></li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'shortlisted', [{{ $application->id }}])">Shortlisted</a></li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'in_progress', [{{ $application->id }}])">In Progress</a></li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'rejected', [{{ $application->id }}])">Rejected</a></li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'finished', [{{ $application->id }}])">Finished</a></li>
              </ul>
            </div>

            <button class="btn btn-outline-danger" type="button" onclick="deleteJobApplication({{ $application->id }})">
              <i class="bi bi-trash"></i> Delete Application
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script src="{{ asset('js/main/job-applications.js') }}" type="module"></script>
    <script>window.csrfToken = '{{ csrf_token() }}';</script>
  @endpush
</x-app-layout>

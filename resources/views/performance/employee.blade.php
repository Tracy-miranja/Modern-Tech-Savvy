<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">{{ $employee->user->name ?? 'N/A' }} &ndash; Performance</h5>
                            <small class="text-muted">{{ $employee->employee_code }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($isHrOrAdmin && $routePrefix === 'business')
                                <button type="button" class="btn btn-outline-info btn-sm" id="openPerformanceReportsBtn">
                                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Reports
                                </button>
                            @endif
                            <label class="form-label small mb-0 me-2">Cycle</label>
                            <select id="cycleSelect" class="form-select form-select-sm d-inline-block" style="width:220px;"></select>
                        </div>
                    </div>

                    <div id="cycleInfoBanner" class="alert alert-light border d-none mb-3">
                        <div class="row g-2 small">
                            <div class="col-md-3"><strong>Cycle period:</strong> <span id="cycleInfoPeriod"></span></div>
                            <div class="col-md-3"><strong>Weights:</strong> <span id="cycleInfoWeights"></span></div>
                            <div class="col-md-3"><strong>Self-review due:</strong> <span id="cycleInfoSelfDue"></span></div>
                            <div class="col-md-3"><strong>Manager review due:</strong> <span id="cycleInfoManagerDue"></span></div>
                        </div>
                    </div>

                    <div id="noActiveCycleBanner" class="alert alert-warning d-none">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        There's no active performance cycle right now, so objectives, KPIs, and reviews can't be set yet.
                        @if ($isHrOrAdmin)
                            HR needs to <a href="{{ route('business.performance.cycles.index', $business->slug) }}">create and activate a cycle</a> first.
                        @else
                            Ask HR to activate a performance cycle.
                        @endif
                    </div>

                    <ul class="nav nav-tabs" id="performanceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-okrs" type="button">Objectives (OKRs)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kpis" type="button">KPIs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-review" type="button">Review</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-feedback" type="button">360 Feedback</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="tab-okrs">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small id="goalsLockNotice" class="text-muted d-none"><i class="bi bi-lock-fill me-1"></i>Goals are locked - the self-review window has opened. Progress can still be updated.</small>
                                <button type="button" id="newObjectiveBtn" class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#newObjectiveModal">
                                    <i class="bi bi-plus-circle me-1"></i> New Objective
                                </button>
                            </div>
                            <div id="objectivesContainer">
                                <p class="text-muted">Select a cycle to see objectives.</p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-kpis">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">KPIs are defined and assigned from Manage KPIs - this is a read-only view scoped to this employee.</small>
                                @if ($isHrOrAdmin)
                                    <a href="{{ route('business.performance.kpis.index', $business->slug) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Manage KPIs
                                    </a>
                                @endif
                            </div>
                            <div id="kpisContainer">
                                <p class="text-muted">Loading…</p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-review">
                            <div id="reviewContainer">
                                <p class="text-muted">Select a cycle to see the review.</p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-feedback">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Feedback about {{ $employee->user->name ?? 'this employee' }}</strong>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestFeedbackModal">
                                    <i class="bi bi-person-plus me-1"></i> Request Feedback
                                </button>
                            </div>
                            <div id="feedbackAboutContainer">
                                <p class="text-muted">Select a cycle to see feedback.</p>
                            </div>

                            @if ($isOwnProfile)
                                <hr>
                                <strong>Feedback Requests To Give</strong>
                                <div id="feedbackInboxContainer" class="mt-2">
                                    <p class="text-muted">Nothing to give yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="requestFeedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="requestFeedbackForm">
                        <label class="form-label">Peer to ask</label>
                        <select name="reviewer_employee_id" id="reviewerSelect" class="form-select" required>
                            <option value="">Loading employees...</option>
                        </select>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitFeedbackRequestBtn">Request</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="giveFeedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="giveFeedbackModalTitle">Give Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="giveFeedbackForm">
                        <input type="hidden" name="feedback_request_id">
                        @foreach (\App\Models\PerformanceFeedbackResponse::QUESTIONS as $key => $question)
                            <div class="mb-3">
                                <label class="form-label">{{ $question }}</label>
                                <textarea name="answers[{{ $key }}]" class="form-control" rows="3" required></textarea>
                            </div>
                        @endforeach
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitGiveFeedbackBtn">Submit Feedback</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newObjectiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Objective</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newObjectiveForm">
                        @if ($isHrOrAdmin)
                            <div class="mb-3">
                                <label class="form-label">Scope</label>
                                <select name="scope" id="objectiveScopeSelect" class="form-select">
                                    <option value="individual" selected>Individual (this employee)</option>
                                    <option value="department">Departmental OKR</option>
                                    <option value="company">Company-Wide Pillar</option>
                                </select>
                            </div>
                            <div class="mb-3 d-none" id="objectiveDepartmentField">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select">
                                    <option value="">Select department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Align to (optional)</label>
                            <select name="parent_objective_id" id="objectiveParentSelect" class="form-select">
                                <option value="">— Not aligned to a higher-level objective —</option>
                            </select>
                            <small class="text-muted">
                                Aligning to a departmental or company objective it serves.
                                @if (!$isHrOrAdmin)
                                    Individual objectives you propose here need the parent objective owner's approval before they count.
                                @endif
                            </small>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Weight % (relative to other objectives)</label>
                            <input type="number" name="weight" class="form-control" value="100" min="0.01" max="100" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitObjectiveBtn">Create</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newKeyResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Key Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newKeyResultForm">
                        <input type="hidden" name="objective_id">
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Target Value</label>
                                <input type="number" step="0.01" name="target_value" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Unit (optional)</label>
                                <input type="text" name="unit" class="form-control" placeholder="%, count, KES...">
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Weight % (relative to other key results)</label>
                            <input type="number" name="weight" class="form-control" value="100" min="0" max="100">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitKeyResultBtn">Add</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateProgressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Progress</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProgressForm">
                        <input type="hidden" name="key_result_id">
                        <div class="mb-1">
                            <label class="form-label">Current Value</label>
                            <input type="number" step="0.01" name="current_value" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitProgressBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="selfAssessmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Self-Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="selfAssessmentForm">
                        <label class="form-label">How did this cycle go for you?</label>
                        <textarea name="self_assessment" class="form-control" rows="5" required></textarea>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitSelfAssessmentBtn">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="managerAssessmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manager Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="managerAssessmentForm">
                        <div class="mb-3">
                            <label class="form-label">Assessment</label>
                            <textarea name="manager_assessment" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Competency Score (0-100)</label>
                            <input type="number" name="competency_score" class="form-control" min="0" max="100" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitManagerAssessmentBtn">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const businessSlug = @json($business->slug);
        const employeeId = @json($employee->id);
        const routePrefix = @json($routePrefix);
        const isOwnProfile = @json($isOwnProfile);

        const urls = {
            activeCycles: @json(route('myaccount.performance.cycles.active', ['business' => $business->slug])),
            objectives: @json(route($routePrefix . '.performance.objectives.fetch', ['business' => $business->slug, 'employee' => $employee->id])),
            storeObjective: @json(route($routePrefix . '.performance.objectives.store', ['business' => $business->slug, 'employee' => $employee->id])),
            storeKeyResultTemplate: @json(route($routePrefix . '.performance.key-results.store', ['business' => $business->slug, 'objective' => '__OID__'])),
            progressTemplate: @json(route($routePrefix . '.performance.key-results.progress', ['business' => $business->slug, 'keyResult' => '__KRID__'])),
            reviewTemplate: {!! json_encode(route($routePrefix . '.performance.review.fetch', ['business' => $business->slug, 'cycle' => '__CID__', 'employee' => $employee->id]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            selfAssessmentTemplate: @json(route($routePrefix . '.performance.review.self', ['business' => $business->slug, 'review' => '__RID__'])),
            managerAssessmentTemplate: @json(route($routePrefix . '.performance.review.manager', ['business' => $business->slug, 'review' => '__RID__'])),
            cascade: @json(route($routePrefix . '.performance.objectives.cascade', ['business' => $business->slug])),
            approveAlignmentTemplate: @json(route($routePrefix . '.performance.objectives.approve-alignment', ['business' => $business->slug, 'objective' => '__OID__'])),
            declineAlignmentTemplate: @json(route($routePrefix . '.performance.objectives.decline-alignment', ['business' => $business->slug, 'objective' => '__OID__'])),
            employeeOptions: @json(route('business.organogram.employee-options', ['business' => $business->slug])),
            feedbackAbout: @json(route($routePrefix . '.performance.feedback.fetch', ['business' => $business->slug, 'employee' => $employee->id])),
            requestFeedback: @json(route($routePrefix . '.performance.feedback.store', ['business' => $business->slug, 'employee' => $employee->id])),
            feedbackInbox: @json(route($routePrefix . '.performance.feedback.inbox', ['business' => $business->slug])),
            declineFeedbackTemplate: @json(route($routePrefix . '.performance.feedback.decline', ['business' => $business->slug, 'feedbackRequest' => '__FRID__'])),
            respondFeedbackTemplate: @json(route($routePrefix . '.performance.feedback.respond', ['business' => $business->slug, 'feedbackRequest' => '__FRID__'])),
            kpisForEmployee: @json(route($routePrefix . '.performance.kpis.for-employee', ['business' => $business->slug, 'employee' => $employee->id])),
        };

        let currentCycleId = null;
        let currentReview = null;
        let currentObjectives = [];
        let currentCycle = null;

        function isCycleGoalsLocked(cycle) {
            // Mirrors PerformanceCycle::goalsAreLocked() - goal-setting
            // stays open through the active period and only locks once the
            // self-review window has opened (cycles here are always
            // status=active, since only active cycles reach this selector).
            if (!cycle || !cycle.lock_goals_on_start || !cycle.self_review_due_date) return false;
            return new Date() > new Date(cycle.self_review_due_date);
        }

        function updateLockUI() {
            const locked = isCycleGoalsLocked(currentCycle);
            const newObjectiveBtn = document.getElementById('newObjectiveBtn');
            const lockNotice = document.getElementById('goalsLockNotice');
            if (newObjectiveBtn) newObjectiveBtn.classList.toggle('d-none', locked);
            if (lockNotice) lockNotice.classList.toggle('d-none', !locked);
        }

        function statusBadge(status, map) {
            return `<span class="badge bg-${map[status] ?? 'secondary'}">${status.replace('_', ' ')}</span>`;
        }

        function updateCycleInfoBanner() {
            const banner = document.getElementById('cycleInfoBanner');
            if (!currentCycle) {
                banner.classList.add('d-none');
                return;
            }
            banner.classList.remove('d-none');
            document.getElementById('cycleInfoPeriod').textContent = `${formatDate(currentCycle.start_date)} to ${formatDate(currentCycle.end_date)}`;
            document.getElementById('cycleInfoWeights').textContent =
                `KPI ${currentCycle.kpi_weight}% / OKR ${currentCycle.okr_weight}% / Competency ${currentCycle.competency_weight}%`;
            document.getElementById('cycleInfoSelfDue').textContent = currentCycle.self_review_due_date ? formatDate(currentCycle.self_review_due_date) : 'Not set';
            document.getElementById('cycleInfoManagerDue').textContent = currentCycle.manager_review_due_date ? formatDate(currentCycle.manager_review_due_date) : 'Not set';
        }

        async function loadCycles() {
            const resp = await fetch(urls.activeCycles, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const cycles = payload.data ?? [];
            const select = document.getElementById('cycleSelect');
            const noCycleBanner = document.getElementById('noActiveCycleBanner');

            if (!cycles.length) {
                select.innerHTML = '<option value="">No active cycles</option>';
                noCycleBanner.classList.remove('d-none');
                document.getElementById('cycleInfoBanner').classList.add('d-none');
                return;
            }

            noCycleBanner.classList.add('d-none');
            select.innerHTML = cycles.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            currentCycleId = cycles[0].id;
            currentCycle = cycles[0];
            updateLockUI();
            updateCycleInfoBanner();
            select.addEventListener('change', async function () {
                currentCycleId = this.value;
                currentCycle = cycles.find(c => String(c.id) === String(this.value)) ?? null;
                updateLockUI();
                updateCycleInfoBanner();
                await loadObjectives();
                loadReview();
                loadFeedbackAbout();
                if (isOwnProfile) loadFeedbackInbox();
            });

            await loadObjectives();
            loadReview();
            loadFeedbackAbout();
            if (isOwnProfile) loadFeedbackInbox();
        }

        async function loadKpis() {
            const container = document.getElementById('kpisContainer');
            try {
                const resp = await fetch(urls.kpisForEmployee, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const kpis = payload.data ?? [];

                if (!kpis.length) {
                    container.innerHTML = '<p class="text-muted">No KPIs assigned to this employee yet.</p>';
                    return;
                }

                container.innerHTML = kpis.map(k => `
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1">${k.name}</h6>
                                <span>${k.progress_percentage}%</span>
                            </div>
                            <p class="text-muted small mb-2">${k.description ?? ''}</p>
                            <div class="progress mb-1" style="height:6px;"><div class="progress-bar" style="width:${Math.min(100, k.progress_percentage)}%"></div></div>
                            <small class="text-muted">Latest: ${k.latest_result ?? '—'} ${k.comparison_operator ?? ''} Target: ${k.target_value ?? '—'}</small>
                        </div>
                    </div>`).join('');
            } catch (e) {
                console.error(e);
                container.innerHTML = '<p class="text-danger">Could not load KPIs.</p>';
            }
        }

        function objectiveProgress(o) {
            const totalWeight = o.key_results.reduce((a, kr) => a + parseFloat(kr.weight), 0) || 1;
            return o.key_results.reduce((a, kr) => {
                const p = (kr.target_value > 0 ? Math.min(100, Math.max(0, (kr.current_value / kr.target_value) * 100)) : 0);
                return a + (p * kr.weight);
            }, 0) / totalWeight;
        }

        async function loadObjectives() {
            const container = document.getElementById('objectivesContainer');
            if (!currentCycleId) return;

            const url = new URL(urls.objectives, window.location.origin);
            url.searchParams.set('performance_cycle_id', currentCycleId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const objectives = payload.data ?? [];
            currentObjectives = objectives;

            if (!objectives.length) {
                container.innerHTML = '<p class="text-muted">No objectives set for this cycle yet.</p>';
                return;
            }

            container.innerHTML = objectives.map(o => {
                const progress = objectiveProgress(o);

                const krRows = o.key_results.map(kr => {
                    const p = (kr.target_value > 0 ? Math.min(100, Math.max(0, (kr.current_value / kr.target_value) * 100)) : 0).toFixed(0);
                    return `
                        <li class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <span>${kr.description} <small class="text-muted">(${kr.current_value}/${kr.target_value}${kr.unit ? ' ' + kr.unit : ''})</small></span>
                            <span class="d-flex align-items-center gap-2">
                                <div class="progress" style="width:80px;height:6px;"><div class="progress-bar" style="width:${p}%"></div></div>
                                <button type="button" class="btn btn-link btn-sm p-0 update-progress-btn" data-kr-id="${kr.id}">Update</button>
                            </span>
                        </li>`;
                }).join('');

                const scopeBadge = o.scope && o.scope !== 'individual'
                    ? `<span class="badge bg-info text-dark text-capitalize">${o.scope}</span>` : '';
                const alignmentBadgeMap = { proposed: 'warning', approved: 'success', draft: 'secondary' };
                const alignmentBadge = o.parent_objective
                    ? `<span class="badge bg-${alignmentBadgeMap[o.alignment_status] ?? 'secondary'} text-capitalize">${o.alignment_status} alignment</span>`
                    : '';
                const confidenceMap = {
                    on_track: { cls: 'success', label: 'On Track' },
                    at_risk: { cls: 'warning', label: 'At Risk' },
                    critical: { cls: 'danger', label: 'Critical' },
                };
                const conf = confidenceMap[o.confidence] ?? confidenceMap.on_track;
                const confidenceBadge = `<span class="badge bg-${conf.cls}">${conf.label}</span>`;
                const gradeBandColors = { red: '#b3492e', amber: '#96721b', green: '#3e7a52', blue: '#3b5f8a' };
                const gradeBadge = (o.final_score !== null && o.final_score !== undefined)
                    ? `<span class="badge" style="background:${gradeBandColors[o.grade_band] ?? '#6c757d'}">Final: ${(o.final_score * 100).toFixed(0)}% (${o.grade_band ?? '—'})</span>`
                    : '';
                const parentLine = o.parent_objective
                    ? `<div class="small text-muted mb-2"><i class="bi bi-diagram-3"></i> Aligned to: ${o.parent_objective.title}</div>` : '';
                const alignmentActions = o.alignment_status === 'proposed'
                    ? `<button type="button" class="btn btn-success btn-sm approve-alignment-btn" data-objective-id="${o.id}">Approve Alignment</button>
                       <button type="button" class="btn btn-outline-danger btn-sm decline-alignment-btn" data-objective-id="${o.id}">Decline</button>`
                    : '';

                return `
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1">${o.title} ${scopeBadge} ${alignmentBadge} ${confidenceBadge} ${gradeBadge}</h6>
                                <span>${progress.toFixed(0)}%</span>
                            </div>
                            ${parentLine}
                            <p class="text-muted small mb-2">${o.description ?? ''}</p>
                            <ul class="list-unstyled mb-2">${krRows || '<li class="text-muted small">No key results yet.</li>'}</ul>
                            <div class="d-flex gap-2 flex-wrap">
                                ${isCycleGoalsLocked(currentCycle) ? '' : `
                                <button type="button" class="btn btn-outline-secondary btn-sm add-kr-btn" data-objective-id="${o.id}">
                                    <i class="bi bi-plus"></i> Add Key Result
                                </button>`}
                                ${alignmentActions}
                            </div>
                        </div>
                    </div>`;
            }).join('');

            container.querySelectorAll('.add-kr-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelector('#newKeyResultForm [name=objective_id]').value = this.dataset.objectiveId;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('newKeyResultModal')).show();
                });
            });
            container.querySelectorAll('.update-progress-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelector('#updateProgressForm [name=key_result_id]').value = this.dataset.krId;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('updateProgressModal')).show();
                });
            });
            container.querySelectorAll('.approve-alignment-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const url = urls.approveAlignmentTemplate.replace('__OID__', this.dataset.objectiveId);
                    try {
                        await postJson(url, {});
                        toastr.success('Alignment approved.');
                        loadObjectives();
                    } catch (e) { toastr.error(e.message); }
                });
            });
            container.querySelectorAll('.decline-alignment-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const { isConfirmed } = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Decline this alignment? It goes back to the employee as a draft.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes',
                    });
                    if (!isConfirmed) return;
                    const url = urls.declineAlignmentTemplate.replace('__OID__', this.dataset.objectiveId);
                    try {
                        await postJson(url, {});
                        toastr.success('Alignment declined.');
                        loadObjectives();
                    } catch (e) { toastr.error(e.message); }
                });
            });
        }

        async function loadReview() {
            const container = document.getElementById('reviewContainer');
            if (!currentCycleId) return;

            const url = urls.reviewTemplate.replace('__CID__', currentCycleId);
            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!resp.ok) {
                container.innerHTML = '<p class="text-muted">Review not available.</p>';
                return;
            }
            const payload = await resp.json();
            currentReview = payload.data;

            const statusMap = { pending_self: 'warning', pending_manager: 'info', completed: 'success' };

            let actionsHtml = '';
            if (isOwnProfile && currentReview.status === 'pending_self') {
                actionsHtml = `<button type="button" class="btn btn-primary btn-sm" id="openSelfAssessmentBtn">Submit Self-Assessment</button>`;
            } else if (!isOwnProfile && currentReview.status === 'pending_manager') {
                actionsHtml = `<button type="button" class="btn btn-primary btn-sm" id="openManagerAssessmentBtn">Submit Manager Assessment</button>`;
            }

            const confidenceMap = {
                on_track: { cls: 'success', label: 'On Track' },
                at_risk: { cls: 'warning', label: 'At Risk' },
                critical: { cls: 'danger', label: 'Critical' },
            };
            const okrSnapshotRows = currentObjectives.map(o => {
                const progress = objectiveProgress(o);
                const conf = confidenceMap[o.confidence] ?? confidenceMap.on_track;
                return `
                    <tr>
                        <td>${o.title}</td>
                        <td>${o.weight}%</td>
                        <td>${progress.toFixed(0)}%</td>
                        <td><span class="badge bg-${conf.cls}">${conf.label}</span></td>
                    </tr>`;
            }).join('');
            const okrSnapshotHtml = `
                <div class="mb-3">
                    <strong>OKR Snapshot</strong> <small class="text-muted">(read-only evidence, carried over from the Objectives tab)</small>
                    ${okrSnapshotRows ? `
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-borderless mb-0">
                                <thead><tr class="text-muted small"><th>Objective</th><th>Weight</th><th>Progress</th><th>Confidence</th></tr></thead>
                                <tbody>${okrSnapshotRows}</tbody>
                            </table>
                        </div>` : '<p class="text-muted small mb-0">No objectives set for this cycle.</p>'}
                </div>`;

            container.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>${statusBadge(currentReview.status, statusMap)}</div>
                    <div>${actionsHtml}</div>
                </div>
                <div class="row g-3">
                    <div class="col-md-3"><strong>KPI Score:</strong> ${currentReview.kpi_score ?? '—'}</div>
                    <div class="col-md-3"><strong>OKR Score:</strong> ${currentReview.okr_score ?? '—'}</div>
                    <div class="col-md-3"><strong>Competency:</strong> ${currentReview.competency_score ?? '—'}</div>
                    <div class="col-md-3"><strong>Overall:</strong> ${currentReview.overall_score ?? '—'}</div>
                </div>
                <hr>
                ${okrSnapshotHtml}
                <hr>
                <div class="mb-2"><strong>Self-Assessment:</strong><p class="text-muted">${currentReview.self_assessment ?? 'Not submitted yet.'}</p></div>
                <div><strong>Manager Assessment:</strong><p class="text-muted">${currentReview.manager_assessment ?? 'Not submitted yet.'}</p></div>
            `;

            document.getElementById('openSelfAssessmentBtn')?.addEventListener('click', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('selfAssessmentModal')).show();
            });
            document.getElementById('openManagerAssessmentBtn')?.addEventListener('click', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('managerAssessmentModal')).show();
            });
        }

        async function loadFeedbackAbout() {
            const container = document.getElementById('feedbackAboutContainer');
            if (!currentCycleId) return;

            const url = new URL(urls.feedbackAbout, window.location.origin);
            url.searchParams.set('performance_cycle_id', currentCycleId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!resp.ok) {
                container.innerHTML = '<p class="text-muted">Feedback not available.</p>';
                return;
            }
            const payload = await resp.json();
            const requests = payload.data ?? [];

            if (!requests.length) {
                container.innerHTML = '<p class="text-muted">No peers have been asked for feedback yet.</p>';
                return;
            }

            const statusMap = { pending: 'warning', submitted: 'success', declined: 'secondary' };
            container.innerHTML = requests.map(r => {
                const answersHtml = r.response
                    ? Object.entries(r.response.answers).map(([key, value]) => `
                        <div class="mb-1"><small class="text-muted d-block">${key.replace('_', ' ')}</small>${value}</div>`).join('')
                    : '';
                return `
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>${r.reviewer.user.name}</strong>
                                ${statusBadge(r.status, statusMap)}
                            </div>
                            ${answersHtml ? `<hr class="my-2">${answersHtml}` : ''}
                        </div>
                    </div>`;
            }).join('');
        }

        async function loadFeedbackInbox() {
            const container = document.getElementById('feedbackInboxContainer');
            if (!container || !currentCycleId) return;

            const url = new URL(urls.feedbackInbox, window.location.origin);
            url.searchParams.set('performance_cycle_id', currentCycleId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const requests = payload.data ?? [];

            if (!requests.length) {
                container.innerHTML = '<p class="text-muted">Nothing to give yet.</p>';
                return;
            }

            container.innerHTML = requests.map(r => `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span>Feedback about <strong>${r.subject.user.name}</strong></span>
                    <span class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm give-feedback-btn" data-request-id="${r.id}" data-subject-name="${r.subject.user.name}">Give Feedback</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm decline-feedback-btn" data-request-id="${r.id}">Decline</button>
                    </span>
                </div>`).join('');

            container.querySelectorAll('.give-feedback-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelector('#giveFeedbackForm [name=feedback_request_id]').value = this.dataset.requestId;
                    document.getElementById('giveFeedbackModalTitle').textContent = `Give Feedback: ${this.dataset.subjectName}`;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('giveFeedbackModal')).show();
                });
            });
            container.querySelectorAll('.decline-feedback-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const { isConfirmed } = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Decline this feedback request?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes',
                    });
                    if (!isConfirmed) return;
                    const url = urls.declineFeedbackTemplate.replace('__FRID__', this.dataset.requestId);
                    try {
                        await postJson(url, {});
                        toastr.success('Feedback request declined.');
                        loadFeedbackInbox();
                    } catch (e) { toastr.error(e.message); }
                });
            });
        }

        document.getElementById('requestFeedbackModal')?.addEventListener('show.bs.modal', async function () {
            const select = document.getElementById('reviewerSelect');
            try {
                const resp = await fetch(urls.employeeOptions, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const options = (payload.data ?? []).filter(e => String(e.id) !== String(employeeId));
                select.innerHTML = options.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }
        });

        document.getElementById('submitFeedbackRequestBtn')?.addEventListener('click', async function () {
            const form = document.getElementById('requestFeedbackForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            data.performance_cycle_id = currentCycleId;
            try {
                await postJson(urls.requestFeedback, data);
                toastr.success('Feedback requested.');
                bootstrap.Modal.getInstance(document.getElementById('requestFeedbackModal')).hide();
                form.reset();
                loadFeedbackAbout();
            } catch (e) { toastr.error(e.message); }
        });

        document.getElementById('submitGiveFeedbackBtn')?.addEventListener('click', async function () {
            const form = document.getElementById('giveFeedbackForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = {};
            new FormData(form).forEach((value, key) => {
                const match = key.match(/^answers\[(.+)\]$/);
                if (match) {
                    data.answers = data.answers ?? {};
                    data.answers[match[1]] = value;
                } else {
                    data[key] = value;
                }
            });
            const requestId = data.feedback_request_id;
            delete data.feedback_request_id;
            try {
                await postJson(urls.respondFeedbackTemplate.replace('__FRID__', requestId), data);
                toastr.success('Feedback submitted.');
                bootstrap.Modal.getInstance(document.getElementById('giveFeedbackModal')).hide();
                form.reset();
                loadFeedbackInbox();
            } catch (e) { toastr.error(e.message); }
        });

        async function postJson(url, data) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        document.getElementById('objectiveScopeSelect')?.addEventListener('change', function () {
            const deptField = document.getElementById('objectiveDepartmentField');
            deptField.classList.toggle('d-none', this.value !== 'department');
        });

        async function loadParentObjectiveOptions() {
            const select = document.getElementById('objectiveParentSelect');
            if (!select || !currentCycleId) return;

            const url = new URL(urls.cascade, window.location.origin);
            url.searchParams.set('performance_cycle_id', currentCycleId);

            try {
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const options = payload.data ?? [];

                select.innerHTML = '<option value="">— Not aligned to a higher-level objective —</option>'
                    + options.map(o => `<option value="${o.id}">[${o.scope}] ${o.title}</option>`).join('');
            } catch (e) {
                console.error(e);
            }
        }

        document.getElementById('newObjectiveModal').addEventListener('show.bs.modal', loadParentObjectiveOptions);

        document.getElementById('submitObjectiveBtn').addEventListener('click', async function () {
            const form = document.getElementById('newObjectiveForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            data.performance_cycle_id = currentCycleId;
            try {
                await postJson(urls.storeObjective, data);
                toastr.success('Objective created.');
                bootstrap.Modal.getInstance(document.getElementById('newObjectiveModal')).hide();
                form.reset();
                await loadObjectives();
                loadReview();
            } catch (e) { toastr.error(e.message); }
        });

        document.getElementById('submitKeyResultBtn').addEventListener('click', async function () {
            const form = document.getElementById('newKeyResultForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            const objectiveId = data.objective_id;
            delete data.objective_id;
            try {
                await postJson(urls.storeKeyResultTemplate.replace('__OID__', objectiveId), data);
                toastr.success('Key result added.');
                bootstrap.Modal.getInstance(document.getElementById('newKeyResultModal')).hide();
                form.reset();
                await loadObjectives();
                loadReview();
            } catch (e) { toastr.error(e.message); }
        });

        document.getElementById('submitProgressBtn').addEventListener('click', async function () {
            const form = document.getElementById('updateProgressForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            const krId = data.key_result_id;
            delete data.key_result_id;
            try {
                await postJson(urls.progressTemplate.replace('__KRID__', krId), data);
                toastr.success('Progress updated.');
                bootstrap.Modal.getInstance(document.getElementById('updateProgressModal')).hide();
                form.reset();
                await loadObjectives();
                loadReview();
            } catch (e) { toastr.error(e.message); }
        });

        document.getElementById('submitSelfAssessmentBtn').addEventListener('click', async function () {
            const form = document.getElementById('selfAssessmentForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            try {
                await postJson(urls.selfAssessmentTemplate.replace('__RID__', currentReview.id), data);
                toastr.success('Self-assessment submitted.');
                bootstrap.Modal.getInstance(document.getElementById('selfAssessmentModal')).hide();
                form.reset();
                loadReview();
            } catch (e) { toastr.error(e.message); }
        });

        document.getElementById('submitManagerAssessmentBtn').addEventListener('click', async function () {
            const form = document.getElementById('managerAssessmentForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            try {
                await postJson(urls.managerAssessmentTemplate.replace('__RID__', currentReview.id), data);
                toastr.success('Manager assessment submitted.');
                bootstrap.Modal.getInstance(document.getElementById('managerAssessmentModal')).hide();
                form.reset();
                loadReview();
            } catch (e) { toastr.error(e.message); }
        });

        loadCycles();
        loadKpis();
    })();
    </script>

    @if ($isHrOrAdmin && $routePrefix === 'business')
        @include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => []])

        <script src="{{ asset('js/main/report-modal.js') }}"></script>
        <script>
        (function () {
            ReportModal.init({
                employeeOptionsUrl: @json(route('business.organogram.employee-options', $business->slug)),
                cycleOptionsUrl: @json(route('business.performance.cycles.fetch', $business->slug)),
            });

            document.getElementById('openPerformanceReportsBtn').addEventListener('click', function () {
                ReportModal.open([
                    {
                        key: 'three-sixty',
                        label: '360 Performance Report (this employee)',
                        filters: ['cycle', 'employee'],
                        previewUrl: @json(route('business.performance.reports.three-sixty.preview', $business->slug)),
                        downloadUrl: @json(route('business.performance.reports.three-sixty.download', $business->slug)),
                    },
                    {
                        key: 'cycle',
                        label: 'Performance Cycle Report (roster)',
                        filters: ['cycle', 'department', 'job_category', 'employee'],
                        previewUrl: @json(route('business.performance.reports.cycle.preview', $business->slug)),
                        downloadUrl: @json(route('business.performance.reports.cycle.download', $business->slug)),
                    },
                ]);
            });
        })();
        </script>
    @endif
</x-app-layout>

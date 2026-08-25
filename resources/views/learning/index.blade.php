<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ $page }}</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#courseModal" id="addCourseBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Course
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="learningTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses-pane" type="button" role="tab">Courses</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="enrollments-tab" data-bs-toggle="tab" data-bs-target="#enrollments-pane" type="button" role="tab">Enrollments</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-pane" type="button" role="tab">Settings</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="courses-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Provider</th>
                                            <th>Duration</th>
                                            <th>Sessions</th>
                                            <th>Enrolled</th>
                                            <th>Status</th>
                                            <th style="width:260px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="coursesTableBody">
                                        <tr><td colspan="8" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="enrollments-pane" role="tabpanel">
                            <div class="d-flex justify-content-end mb-2">
                                <select class="form-select form-select-sm" id="enrollmentCourseFilter" style="width:220px;">
                                    <option value="">All courses</option>
                                </select>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Course</th>
                                            <th>Session</th>
                                            <th>Status</th>
                                            <th>Score</th>
                                            <th>Certificate</th>
                                            <th style="width:120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="enrollmentsTableBody">
                                        <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="settings-pane" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Course Categories</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr><th>Name</th><th>Courses</th><th>Active</th><th style="width:80px;">Action</th></tr>
                                                </thead>
                                                <tbody id="categoriesTableBody">
                                                    <tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Certificate & Reminder Defaults</h6>
                                        </div>
                                        <div class="card-body">
                                            <form id="learningSettingsForm">
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Certificate Validity (months)</label>
                                                        <input type="number" min="1" max="120" name="learning_certificate_validity_months" id="settingCertValidity" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Certificate Number Prefix</label>
                                                        <input type="text" maxlength="20" name="learning_certificate_number_prefix" id="settingCertPrefix" class="form-control" placeholder="CERT">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Session Reminder (days before)</label>
                                                        <input type="number" min="0" max="60" name="learning_session_reminder_days" id="settingSessionReminderDays" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Certificate Expiry Reminder (days before)</label>
                                                        <input type="number" min="0" max="180" name="learning_certificate_expiry_reminder_days" id="settingCertReminderDays" class="form-control" required>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm mt-3" id="saveLearningSettingsBtn">Save Settings</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Mandatory / Compliance Courses</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mandateModal">
                                                <i class="bi bi-plus-lg"></i> Add Mandate
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr><th>Course</th><th>Scope</th><th>Matching Employees</th><th>Active</th><th style="width:80px;">Action</th></tr>
                                                </thead>
                                                <tbody id="mandatesTableBody">
                                                    <tr><td colspan="5" class="text-center text-muted">Loading…</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="courseModalTitle">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="courseForm">
                        <input type="hidden" id="courseEditingId">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label small">Title</label>
                                <input type="text" name="title" id="courseTitle" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Category</label>
                                <select name="course_category_id" id="courseCategory" class="form-select">
                                    <option value="">No category</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Provider / Instructor</label>
                                <input type="text" name="provider" id="courseProvider" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Duration (hrs)</label>
                                <input type="number" step="0.5" min="0" name="duration_hours" id="courseDuration" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Status</label>
                                <select name="status" id="courseStatus" class="form-select">
                                    <option value="draft">Draft</option>
                                    <option value="active" selected>Active</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Description</label>
                                <textarea name="description" id="courseDescription" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitCourseBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Course Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        <label class="form-label small">Name</label>
                        <input type="text" name="name" id="categoryName" class="form-control" required>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitCategoryBtn">Add</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mandateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Mandatory / Compliance Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="mandateForm">
                        <div class="mb-2">
                            <label class="form-label small">Course</label>
                            <select id="mandateCourseSelect" class="form-select" required>
                                <option value="">Select a course…</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Applies To</label>
                            <select id="mandateScopeType" class="form-select">
                                <option value="organization">Entire Organization</option>
                                <option value="department">Specific Department(s)</option>
                                <option value="job_category">Specific Job Categor(y/ies)</option>
                            </select>
                        </div>
                        <div class="mb-1" id="mandateScopeIdsGroup" style="display:none;">
                            <label class="form-label small">Select</label>
                            <select id="mandateScopeIds" class="form-select select2-multiple" multiple style="width:100%;">
                                @foreach(($departments ?? []) as $department)
                                    <option value="{{ $department->id }}" data-scope="department">{{ $department->name }}</option>
                                @endforeach
                                @foreach(($jobCategories ?? []) as $jobCategory)
                                    <option value="{{ $jobCategory->id }}" data-scope="job_category">{{ $jobCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitMandateBtn">Add Mandate</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sessionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Training Schedule — <span id="sessionsCourseTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="sessionsCourseId">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Location</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Enrolled</th>
                                    <th style="width:110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="sessionsTableBody">
                                <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <form id="sessionForm" class="border-top pt-3">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Start Date</label>
                                <input type="date" name="start_date" id="sessionStartDate" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">End Date</label>
                                <input type="date" name="end_date" id="sessionEndDate" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Location</label>
                                <input type="text" name="location" id="sessionLocation" class="form-control" placeholder="Online / venue">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Capacity</label>
                                <input type="number" min="1" name="capacity" id="sessionCapacity" class="form-control">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="submitSessionBtn">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enroll Employee — <span id="enrollCourseTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="enrollCourseId">
                    <div class="mb-3">
                        <label class="form-label small">Employee</label>
                        <select id="enrollEmployeeSelect" class="form-select select2-multiple" style="width:100%;">
                            <option value="">Loading employees…</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small">Session (optional)</label>
                        <select id="enrollSessionSelect" class="form-select">
                            <option value="">No specific session</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitEnrollBtn">Enroll</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateEnrollmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="updateEnrollmentId">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Status</label>
                            <select id="updateEnrollmentStatus" class="form-select">
                                <option value="enrolled">Enrolled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="dropped">Dropped</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Score (%)</label>
                            <input type="number" step="0.01" min="0" max="100" id="updateEnrollmentScore" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="updateEnrollmentCertIssued">
                                <label class="form-check-label small" for="updateEnrollmentCertIssued">Certificate issued</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Certificate Number</label>
                            <input type="text" id="updateEnrollmentCertNumber" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Certificate Expiry</label>
                            <input type="date" id="updateEnrollmentCertExpiry" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitUpdateEnrollmentBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const coursesFetchUrl = @json(route('business.learning.courses.fetch', $business->slug));
        const coursesStoreUrl = @json(route('business.learning.courses.store', $business->slug));
        const courseUpdateUrlTemplate = @json(route('business.learning.courses.update', ['business' => $business->slug, 'course' => '__ID__']));
        const courseDestroyUrlTemplate = @json(route('business.learning.courses.destroy', ['business' => $business->slug, 'course' => '__ID__']));
        const sessionsFetchUrlTemplate = @json(route('business.learning.sessions.fetch', ['business' => $business->slug, 'course' => '__ID__']));
        const sessionsStoreUrlTemplate = @json(route('business.learning.sessions.store', ['business' => $business->slug, 'course' => '__ID__']));
        const sessionUpdateUrlTemplate = @json(route('business.learning.sessions.update', ['business' => $business->slug, 'courseSession' => '__ID__']));
        const sessionDestroyUrlTemplate = @json(route('business.learning.sessions.destroy', ['business' => $business->slug, 'courseSession' => '__ID__']));
        const enrollmentsFetchUrl = @json(route('business.learning.enrollments.fetch', $business->slug));
        const enrollStoreUrlTemplate = @json(route('business.learning.enrollments.store', ['business' => $business->slug, 'course' => '__ID__']));
        const enrollmentUpdateUrlTemplate = @json(route('business.learning.enrollments.update', ['business' => $business->slug, 'enrollment' => '__ID__']));
        const enrollmentDestroyUrlTemplate = @json(route('business.learning.enrollments.destroy', ['business' => $business->slug, 'enrollment' => '__ID__']));
        const employeeOptionsUrl = @json(route('business.organogram.employee-options', $business->slug));
        const courseOptionsUrl = @json(route('business.learning.courses.options', $business->slug));
        const categoriesFetchUrl = @json(route('business.learning.categories.fetch', $business->slug));
        const categoriesStoreUrl = @json(route('business.learning.categories.store', $business->slug));
        const categoryDestroyUrlTemplate = @json(route('business.learning.categories.destroy', ['business' => $business->slug, 'category' => '__ID__']));
        const mandatesFetchUrl = @json(route('business.learning.mandates.fetch', $business->slug));
        const mandatesStoreUrl = @json(route('business.learning.mandates.store', $business->slug));
        const mandateDestroyUrlTemplate = @json(route('business.learning.mandates.destroy', ['business' => $business->slug, 'mandate' => '__ID__']));
        const settingsUpdateUrl = @json(route('business.learning.settings.update', $business->slug));

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[ch]));
        }

        async function postJson(url, data, method = 'POST') {
            const resp = await fetch(url, {
                method,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data || {}),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        function courseStatusBadge(status) {
            const map = { draft: 'secondary', active: 'success', archived: 'dark' };
            return `<span class="badge bg-${map[status] ?? 'secondary'}">${status}</span>`;
        }

        function enrollmentStatusBadge(status) {
            const map = { enrolled: 'secondary', in_progress: 'primary', completed: 'success', dropped: 'danger' };
            return `<span class="badge bg-${map[status] ?? 'secondary'}">${(status || '').replace('_', ' ')}</span>`;
        }

        // ---- Courses -----------------------------------------------------

        async function loadCourses() {
            const tbody = document.getElementById('coursesTableBody');
            try {
                const resp = await fetch(coursesFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const courses = payload.data ?? [];

                if (!courses.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No courses yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = courses.map(c => `
                    <tr>
                        <td>${escapeHtml(c.title)}</td>
                        <td>${escapeHtml(c.category?.name ?? '—')}</td>
                        <td>${escapeHtml(c.provider ?? '—')}</td>
                        <td>${c.duration_hours ? c.duration_hours + 'h' : '—'}</td>
                        <td>${c.sessions_count ?? 0}</td>
                        <td>${c.enrollments_count ?? 0}</td>
                        <td>${courseStatusBadge(c.status)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary manage-sessions-btn" data-id="${c.id}" data-title="${escapeHtml(c.title)}">Schedule</button>
                            <button type="button" class="btn btn-sm btn-outline-primary enroll-btn" data-id="${c.id}" data-title="${escapeHtml(c.title)}">Enroll</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary edit-course-btn" data-id="${c.id}">Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-course-btn" data-id="${c.id}">Delete</button>
                        </td>
                    </tr>`).join('');

                window._coursesCache = courses;
                bindCourseRowHandlers();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Could not load courses.</td></tr>';
            }
        }

        function bindCourseRowHandlers() {
            document.querySelectorAll('.edit-course-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const course = (window._coursesCache || []).find(c => String(c.id) === this.dataset.id);
                    if (!course) return;
                    document.getElementById('courseModalTitle').textContent = 'Edit Course';
                    document.getElementById('courseEditingId').value = course.id;
                    document.getElementById('courseTitle').value = course.title;
                    document.getElementById('courseCategory').value = course.course_category_id ?? '';
                    document.getElementById('courseProvider').value = course.provider ?? '';
                    document.getElementById('courseDuration').value = course.duration_hours ?? '';
                    document.getElementById('courseStatus').value = course.status;
                    document.getElementById('courseDescription').value = course.description ?? '';
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('courseModal')).show();
                });
            });
            document.querySelectorAll('.delete-course-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const { isConfirmed } = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete this course?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes',
                    });
                    if (!isConfirmed) return;
                    try {
                        await postJson(courseDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                        toastr.success('Course deleted.');
                        loadCourses();
                    } catch (e) {
                        toastr.error(e.message || 'Could not delete course.');
                    }
                });
            });
            document.querySelectorAll('.manage-sessions-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('sessionsCourseId').value = this.dataset.id;
                    document.getElementById('sessionsCourseTitle').textContent = this.dataset.title;
                    document.getElementById('sessionForm').reset();
                    loadSessions(this.dataset.id);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('sessionsModal')).show();
                });
            });
            document.querySelectorAll('.enroll-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    document.getElementById('enrollCourseId').value = this.dataset.id;
                    document.getElementById('enrollCourseTitle').textContent = this.dataset.title;
                    await loadEnrollEmployeeOptions();
                    await loadEnrollSessionOptions(this.dataset.id);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('enrollModal')).show();
                });
            });
        }

        document.getElementById('addCourseBtn').addEventListener('click', function () {
            document.getElementById('courseModalTitle').textContent = 'Add Course';
            document.getElementById('courseForm').reset();
            document.getElementById('courseEditingId').value = '';
        });

        document.getElementById('submitCourseBtn').addEventListener('click', async function () {
            const form = document.getElementById('courseForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            const editingId = document.getElementById('courseEditingId').value;

            try {
                if (editingId) {
                    await postJson(courseUpdateUrlTemplate.replace('__ID__', editingId), data);
                    toastr.success('Course updated.');
                } else {
                    await postJson(coursesStoreUrl, data);
                    toastr.success('Course added.');
                }
                bootstrap.Modal.getInstance(document.getElementById('courseModal'))?.hide();
                loadCourses();
            } catch (e) {
                toastr.error(e.message || 'Could not save course.');
            }
        });

        // ---- Settings: Course Categories -----------------------------------

        async function loadCategories() {
            const tbody = document.getElementById('categoriesTableBody');
            const select = document.getElementById('courseCategory');
            try {
                const resp = await fetch(categoriesFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const categories = payload.data ?? [];

                select.innerHTML = '<option value="">No category</option>'
                    + categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');

                if (!categories.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No categories yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = categories.map(c => `
                    <tr>
                        <td>${escapeHtml(c.name)}</td>
                        <td>${c.courses_count ?? 0}</td>
                        <td>${c.is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger delete-category-btn" data-id="${c.id}">Delete</button></td>
                    </tr>`).join('');

                document.querySelectorAll('.delete-category-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Delete this category?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await postJson(categoryDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                            toastr.success('Category deleted.');
                            loadCategories();
                        } catch (e) {
                            toastr.error(e.message || 'Could not delete category.');
                        }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Could not load categories.</td></tr>';
            }
        }

        document.getElementById('submitCategoryBtn').addEventListener('click', async function () {
            const form = document.getElementById('categoryForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            try {
                await postJson(categoriesStoreUrl, { name: document.getElementById('categoryName').value });
                toastr.success('Category added.');
                bootstrap.Modal.getInstance(document.getElementById('categoryModal'))?.hide();
                form.reset();
                loadCategories();
            } catch (e) {
                toastr.error(e.message || 'Could not add category.');
            }
        });

        // ---- Settings: Mandatory / Compliance Courses -----------------------

        function mandateScopeLabel(mandate) {
            if (mandate.scope_type === 'organization') return 'Entire Organization';
            const kind = mandate.scope_type === 'department' ? 'Department(s)' : 'Job Categor(y/ies)';
            return `${(mandate.scope_ids || []).length} ${kind}`;
        }

        async function loadMandates() {
            const tbody = document.getElementById('mandatesTableBody');
            try {
                const resp = await fetch(mandatesFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const mandates = payload.data ?? [];

                if (!mandates.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No mandatory courses configured.</td></tr>';
                    return;
                }

                tbody.innerHTML = mandates.map(m => `
                    <tr>
                        <td>${escapeHtml(m.course?.title ?? '—')}</td>
                        <td>${mandateScopeLabel(m)}</td>
                        <td>${m.affected_employees_count ?? 0}</td>
                        <td>${m.is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger delete-mandate-btn" data-id="${m.id}">Delete</button></td>
                    </tr>`).join('');

                document.querySelectorAll('.delete-mandate-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Remove this mandate? Employees already enrolled through it keep their enrollment.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await postJson(mandateDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                            toastr.success('Mandate removed.');
                            loadMandates();
                        } catch (e) {
                            toastr.error(e.message || 'Could not remove mandate.');
                        }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Could not load mandates.</td></tr>';
            }
        }

        document.getElementById('mandateModal').addEventListener('show.bs.modal', function () {
            const courseSelect = document.getElementById('mandateCourseSelect');
            courseSelect.innerHTML = '<option value="">Select a course…</option>'
                + (window._coursesCache || []).map(c => `<option value="${c.id}">${escapeHtml(c.title)}</option>`).join('');
            if (window.jQuery) $('#mandateScopeIds').select2({ dropdownParent: $('#mandateModal') });
            onMandateScopeTypeChange();
        });

        function onMandateScopeTypeChange() {
            const scopeType = document.getElementById('mandateScopeType').value;
            const group = document.getElementById('mandateScopeIdsGroup');
            group.style.display = scopeType === 'organization' ? 'none' : '';
            document.querySelectorAll('#mandateScopeIds option').forEach(opt => {
                opt.hidden = opt.dataset.scope !== scopeType;
            });
            if (window.jQuery) $('#mandateScopeIds').val(null).trigger('change');
        }

        document.getElementById('mandateScopeType').addEventListener('change', onMandateScopeTypeChange);

        document.getElementById('submitMandateBtn').addEventListener('click', async function () {
            const courseId = document.getElementById('mandateCourseSelect').value;
            if (!courseId) { toastr.error('Select a course.'); return; }
            const scopeType = document.getElementById('mandateScopeType').value;
            const scopeIdsSelect = document.getElementById('mandateScopeIds');
            const scopeIds = window.jQuery ? ($(scopeIdsSelect).val() || []) : Array.from(scopeIdsSelect.selectedOptions).map(o => o.value);

            try {
                await postJson(mandatesStoreUrl, {
                    course_id: courseId,
                    scope_type: scopeType,
                    scope_ids: scopeType === 'organization' ? null : scopeIds,
                });
                toastr.success('Mandate added.');
                bootstrap.Modal.getInstance(document.getElementById('mandateModal'))?.hide();
                loadMandates();
            } catch (e) {
                toastr.error(e.message || 'Could not add mandate.');
            }
        });

        // ---- Settings: Certificate & Reminder Defaults -----------------------
        // Current values are already available server-side - rendered
        // straight into the inputs, no separate fetch needed.

        document.getElementById('settingCertValidity').value = @json($business->learning_certificate_validity_months);
        document.getElementById('settingCertPrefix').value = @json($business->learning_certificate_number_prefix);
        document.getElementById('settingSessionReminderDays').value = @json($business->learning_session_reminder_days);
        document.getElementById('settingCertReminderDays').value = @json($business->learning_certificate_expiry_reminder_days);

        document.getElementById('saveLearningSettingsBtn').addEventListener('click', async function () {
            const form = document.getElementById('learningSettingsForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());

            try {
                await postJson(settingsUpdateUrl, data);
                toastr.success('Learning settings updated.');
            } catch (e) {
                toastr.error(e.message || 'Could not update settings.');
            }
        });

        // ---- Sessions ------------------------------------------------------

        async function loadSessions(courseId) {
            const tbody = document.getElementById('sessionsTableBody');
            try {
                const resp = await fetch(sessionsFetchUrlTemplate.replace('__ID__', courseId), { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const sessions = payload.data ?? [];

                if (!sessions.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No sessions scheduled yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = sessions.map(s => `
                    <tr>
                        <td>${formatDate(s.start_date)}</td>
                        <td>${s.end_date ? formatDate(s.end_date) : '—'}</td>
                        <td>${escapeHtml(s.location ?? '—')}</td>
                        <td>${s.capacity ?? '—'}</td>
                        <td class="text-capitalize">${s.status}</td>
                        <td>${s.enrollments_count ?? 0}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-session-btn" data-id="${s.id}">Delete</button>
                        </td>
                    </tr>`).join('');

                document.querySelectorAll('.delete-session-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Delete this session?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await postJson(sessionDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                            toastr.success('Session deleted.');
                            loadSessions(document.getElementById('sessionsCourseId').value);
                            loadCourses();
                        } catch (e) {
                            toastr.error(e.message || 'Could not delete session.');
                        }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Could not load sessions.</td></tr>';
            }
        }

        document.getElementById('submitSessionBtn').addEventListener('click', async function () {
            const form = document.getElementById('sessionForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            const courseId = document.getElementById('sessionsCourseId').value;

            try {
                await postJson(sessionsStoreUrlTemplate.replace('__ID__', courseId), data);
                toastr.success('Session added.');
                form.reset();
                loadSessions(courseId);
                loadCourses();
            } catch (e) {
                toastr.error(e.message || 'Could not add session.');
            }
        });

        // ---- Enroll ----------------------------------------------------

        async function loadEnrollEmployeeOptions() {
            const select = document.getElementById('enrollEmployeeSelect');
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                select.innerHTML = employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
                if (window.jQuery) $(select).select2({ dropdownParent: $('#enrollModal') });
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }
        }

        async function loadEnrollSessionOptions(courseId) {
            const select = document.getElementById('enrollSessionSelect');
            try {
                const resp = await fetch(sessionsFetchUrlTemplate.replace('__ID__', courseId), { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const sessions = payload.data ?? [];
                select.innerHTML = '<option value="">No specific session</option>'
                    + sessions.map(s => `<option value="${s.id}">${formatDate(s.start_date)}${s.location ? ' — ' + escapeHtml(s.location) : ''}</option>`).join('');
            } catch (e) {
                select.innerHTML = '<option value="">No specific session</option>';
            }
        }

        document.getElementById('submitEnrollBtn').addEventListener('click', async function () {
            const courseId = document.getElementById('enrollCourseId').value;
            const employeeId = document.getElementById('enrollEmployeeSelect').value;
            if (!employeeId) { toastr.error('Select an employee.'); return; }

            try {
                await postJson(enrollStoreUrlTemplate.replace('__ID__', courseId), {
                    employee_id: employeeId,
                    course_session_id: document.getElementById('enrollSessionSelect').value || null,
                });
                toastr.success('Employee enrolled.');
                bootstrap.Modal.getInstance(document.getElementById('enrollModal'))?.hide();
                loadCourses();
                loadEnrollments();
            } catch (e) {
                toastr.error(e.message || 'Could not enroll employee.');
            }
        });

        // ---- Enrollments -------------------------------------------------

        async function loadEnrollments() {
            const tbody = document.getElementById('enrollmentsTableBody');
            const courseId = document.getElementById('enrollmentCourseFilter').value;
            try {
                const resp = await fetch(`${enrollmentsFetchUrl}${courseId ? '?course_id=' + courseId : ''}`, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const enrollments = payload.data ?? [];

                if (!enrollments.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No enrollments found.</td></tr>';
                    return;
                }

                tbody.innerHTML = enrollments.map(e => `
                    <tr>
                        <td>${escapeHtml(e.employee?.user?.name ?? 'N/A')}</td>
                        <td>${escapeHtml(e.course?.title ?? '—')}</td>
                        <td>${e.session ? formatDate(e.session.start_date) : '—'}</td>
                        <td>${enrollmentStatusBadge(e.status)}</td>
                        <td>${e.score !== null ? e.score + '%' : '—'}</td>
                        <td>${e.certificate_issued ? '<span class="badge bg-success">Issued</span>' : '—'}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary update-enrollment-btn"
                                data-id="${e.id}" data-status="${e.status}" data-score="${e.score ?? ''}"
                                data-cert-issued="${e.certificate_issued ? '1' : '0'}"
                                data-cert-number="${escapeHtml(e.certificate_number ?? '')}"
                                data-cert-expiry="${toDateInputValue(e.certificate_expiry_date)}">Update</button>
                        </td>
                    </tr>`).join('');

                bindEnrollmentRowHandlers();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Could not load enrollments.</td></tr>';
            }
        }

        function bindEnrollmentRowHandlers() {
            document.querySelectorAll('.update-enrollment-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('updateEnrollmentId').value = this.dataset.id;
                    document.getElementById('updateEnrollmentStatus').value = this.dataset.status;
                    document.getElementById('updateEnrollmentScore').value = this.dataset.score;
                    document.getElementById('updateEnrollmentCertIssued').checked = this.dataset.certIssued === '1';
                    document.getElementById('updateEnrollmentCertNumber').value = this.dataset.certNumber;
                    document.getElementById('updateEnrollmentCertExpiry').value = this.dataset.certExpiry;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('updateEnrollmentModal')).show();
                });
            });
        }

        document.getElementById('submitUpdateEnrollmentBtn').addEventListener('click', async function () {
            const id = document.getElementById('updateEnrollmentId').value;

            try {
                await postJson(enrollmentUpdateUrlTemplate.replace('__ID__', id), {
                    status: document.getElementById('updateEnrollmentStatus').value,
                    score: document.getElementById('updateEnrollmentScore').value || null,
                    certificate_issued: document.getElementById('updateEnrollmentCertIssued').checked,
                    certificate_number: document.getElementById('updateEnrollmentCertNumber').value || null,
                    certificate_expiry_date: document.getElementById('updateEnrollmentCertExpiry').value || null,
                });
                toastr.success('Enrollment updated.');
                bootstrap.Modal.getInstance(document.getElementById('updateEnrollmentModal'))?.hide();
                loadEnrollments();
            } catch (e) {
                toastr.error(e.message || 'Could not update enrollment.');
            }
        });

        async function loadEnrollmentCourseFilterOptions() {
            const select = document.getElementById('enrollmentCourseFilter');
            try {
                const resp = await fetch(courseOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const courses = payload.data ?? [];
                select.innerHTML = '<option value="">All courses</option>'
                    + courses.map(c => `<option value="${c.id}">${escapeHtml(c.title)}</option>`).join('');
            } catch (e) {
                // Leave the default "All courses" option in place.
            }
        }

        document.getElementById('enrollmentCourseFilter').addEventListener('change', loadEnrollments);

        loadCourses();
        loadEnrollments();
        loadEnrollmentCourseFilterOptions();
        loadCategories();
        loadMandates();
    })();
    </script>
    @endpush
</x-app-layout>

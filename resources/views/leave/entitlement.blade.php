<x-app-layout>
    <form method="POST" id="leaveEntitlementsForm">
        @csrf
        <div class="row g-20">

            <!-- Selection Panel -->
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="leave_period_id" class="form-label">Leave Period</label>
                                <select name="leave_period_id" id="leave_period_id" class="form-select" required>
                                    <option value="" disabled selected>Select Leave Period</option>
                                    @foreach ($leavePeriods as $leavePeriod)
                                        <option value="{{ $leavePeriod->id }}" data-slug="{{ $leavePeriod->slug }}">{{ $leavePeriod->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="locations" class="form-label">Location</label>
                                <select name="locations[]" id="locations" class="form-select select2-multiple" multiple>
                                    <option selected value="{{ $currentBusiness->slug }}">
                                        {{ $currentBusiness->company_name }}
                                    </option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->slug }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="departments" class="form-label">Departments</label>
                                <select name="departments[]" id="departments" class="form-select select2-multiple" multiple>
                                    <option value="all" selected>All Departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->slug }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="job_categories" class="form-label">Job Categories</label>
                                <select name="job_categories[]" id="job_categories" class="form-select select2-multiple" multiple>
                                    <option value="all" selected>All Job Categories</option>
                                    @foreach ($jobCategories as $jobCategory)
                                        <option value="{{ $jobCategory->slug }}">{{ $jobCategory->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="employment_terms" class="form-label">Employment Terms</label>
                                <select name="employment_terms[]" id="employment_terms" class="form-select select2-multiple" multiple>
                                    <option value="all" selected>All Terms</option>
                                    <option value="permanent">Permanent</option>
                                    <option value="contract">Contract</option>
                                    <option value="temporary">Temporary</option>
                                    <option value="internship">Internship</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Select Employees (Optional)</h6>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" id="selectAllBtn">
                                            <i class="bi bi-check-square"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="deselectAllBtn">
                                            <i class="bi bi-square"></i> Deselect All
                                        </button>
                                    </div>
                                </div>

                                <!-- Search Box -->
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text"
                                               class="form-control"
                                               id="employeeSearch"
                                               placeholder="Search employees by name or department...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="searchResultsCount"></small>
                                </div>

                                <!-- Employee Checkboxes Container -->
                                <div id="employee-checkboxes" class="form-group" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.75rem;">
                                    <!-- Dynamic employee checkboxes will be added here -->
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Leave Type and Entitlements Panel -->
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h6>Leave Type Entitlements</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Entitled Days</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="dynamicRows">
                                <!-- Dynamic rows will be appended here -->
                            </tbody>
                        </table>

                        <div class="d-flex justify-content-start mt-3">
                            <button type="button" class="btn btn-outline-primary" id="addRowButton">
                                <i class="bi bi-plus-circle"></i> Add Leave Type
                            </button>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="button" onclick="saveLeaveEntitlements(this)" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle"></i> Save Entitlement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

    @push('scripts')
        <script src="{{ asset('js/main/leave-entitlement.js') }}" type="module"></script>
        <script src="{{ asset('js/main/filter-employees.js') }}" type="module"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const filterIds = ['departments', 'job_categories', 'employment_terms', 'locations'];
                let allEmployeesData = []; // Store all employees for search functionality

                // Initialize Select2 for multiple select fields
                $('.select2-multiple').select2();

                async function triggerFilterEmployees() {
                    const sel = document.getElementById('leave_period_id');
                    const leavePeriodId   = sel.value || null;
                    const leavePeriodSlug = sel.options[sel.selectedIndex]?.dataset.slug || null;

                    const checkboxesContainer = document.getElementById('employee-checkboxes');
                    checkboxesContainer.innerHTML = '<p class="text-muted">Loading employees...</p>';

                    const filters = {
                        leave_period_id: leavePeriodId, // employees list usually wants the ID
                        departments: $('#departments').val() || [],
                        job_categories: $('#job_categories').val() || [],
                        employment_terms: $('#employment_terms').val() || [],
                        locations: $('#locations').val() || []
                    };

                    try {
                        // Build a payload that works for both id or slug
                        const entPayload = (leavePeriodId || leavePeriodSlug)
                        ? { leave_period_id: leavePeriodId || undefined, leave_period_slug: leavePeriodSlug || undefined }
                        : null;

                        const employeesPromise   = window.getAllEmployeesList(filters);
                        const entitlementsPromise = entPayload
                        ? window.getLeaveEntitlementsByPeriod(entPayload)    // <-- pass OBJECT, not scalar
                        : Promise.resolve({ data: [] });

                        const [employeesResponse, entitlementsResponse] = await Promise.all([
                        employeesPromise,
                        entitlementsPromise.catch(e => {
                            console.warn('Entitlements fetch failed, continuing with employees only:', e);
                            return { data: [] }; // don't break employees render
                        })
                        ]);

                        const allEmployees = employeesResponse.data || employeesResponse;
                        const entitlements = entitlementsResponse.data || entitlementsResponse;

                        // map + render
                        const entByEmp = Array.isArray(entitlements)
                        ? new Map(entitlements.map(e => [e.employee_id, e]))
                        : new Map();

                        const allEmployeesData = allEmployees.map(employee => ({
                        ...employee,
                        entitlement: entByEmp.get(employee.id) || null
                        }));

                        renderEmployees(allEmployeesData);
                        updateSearchCount(allEmployeesData.length, allEmployeesData.length);

                    } catch (error) {
                        console.error('Error fetching employees or entitlements:', error);
                        checkboxesContainer.innerHTML = "<p class=\"text-danger\">Error fetching employees. Please try again later.</p>";
                    }
                    }


                function renderEmployees(employees) {
                    const checkboxesContainer = document.getElementById('employee-checkboxes');

                    if (Array.isArray(employees) && employees.length > 0) {
                        checkboxesContainer.innerHTML = '';

                        employees.forEach(employee => {
                            const isChecked = employee.checked || false;
                            const employeeName = employee.name || 'Unknown';
                            const departmentName = employee.department || 'N/A';
                            const entitlement = employee.entitlement;

                            const badgeHtml = entitlement
                                ? `<span class="badge bg-success ms-2">${entitlement.entitled_days} days</span>`
                                : '';

                            const checkbox = `
                                <div class="form-check employee-item" data-employee-name="${employeeName.toLowerCase()}" data-department="${departmentName.toLowerCase()}">
                                    <input type="checkbox" class="form-check-input employee-checkbox"
                                           id="employee_${employee.id}"
                                           name="employees[]"
                                           value="${employee.id}"
                                           ${isChecked ? 'checked' : ''}>
                                    <label class="form-check-label" for="employee_${employee.id}">
                                        ${employeeName} (${departmentName})
                                        ${badgeHtml}
                                    </label>
                                </div>
                            `;
                            checkboxesContainer.insertAdjacentHTML('beforeend', checkbox);
                        });
                    } else {
                        checkboxesContainer.innerHTML = "<p class=\"text-muted\">No employees found.</p>";
                    }
                }

                function updateSearchCount(visible, total) {
                    const countElement = document.getElementById('searchResultsCount');
                    if (visible === total) {
                        countElement.textContent = `Showing all ${total} employees`;
                    } else {
                        countElement.textContent = `Showing ${visible} of ${total} employees`;
                    }
                }

                // Search functionality
                document.getElementById('employeeSearch').addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    const employeeItems = document.querySelectorAll('.employee-item');
                    let visibleCount = 0;

                    employeeItems.forEach(item => {
                        const name = item.dataset.employeeName;
                        const department = item.dataset.department;
                        const matches = name.includes(searchTerm) || department.includes(searchTerm);

                        if (matches) {
                            item.style.display = '';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    updateSearchCount(visibleCount, employeeItems.length);
                });

                // Clear search
                document.getElementById('clearSearchBtn').addEventListener('click', function() {
                    document.getElementById('employeeSearch').value = '';
                    const employeeItems = document.querySelectorAll('.employee-item');
                    employeeItems.forEach(item => item.style.display = '');
                    updateSearchCount(employeeItems.length, employeeItems.length);
                });

                // Select All
                document.getElementById('selectAllBtn').addEventListener('click', function() {
                    const visibleCheckboxes = document.querySelectorAll('.employee-item:not([style*="display: none"]) .employee-checkbox');
                    visibleCheckboxes.forEach(checkbox => checkbox.checked = true);
                });

                // Deselect All
                document.getElementById('deselectAllBtn').addEventListener('click', function() {
                    const visibleCheckboxes = document.querySelectorAll('.employee-item:not([style*="display: none"]) .employee-checkbox');
                    visibleCheckboxes.forEach(checkbox => checkbox.checked = false);
                });

                // Bind filters
                filterIds.forEach(id => {
                    $(`#${id}`).on('select2:select select2:unselect', triggerFilterEmployees);
                });

                $('#leave_period_id').on('change', triggerFilterEmployees);

                // Dynamic rows (Leave Type + Entitled Days)
                function addRow() {
                    const dynamicRows = document.getElementById('dynamicRows');
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                        <td>
                            <select name="leave_type_ids[]" class="form-select" required>
                                <option value="" disabled selected>Select Leave Type</option>
                                @foreach ($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="entitled_days[]" class="form-control" step="1" min="0" placeholder="e.g., 20" required>
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-danger removeRowButton" title="Remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    dynamicRows.appendChild(newRow);
                }

                document.getElementById('addRowButton').addEventListener('click', addRow);

                document.getElementById('dynamicRows').addEventListener('click', function(event) {
                    if (event.target.closest('.removeRowButton')) {
                        const row = event.target.closest('tr');
                        row.remove();
                    }
                });

                // Initial bootstrap
                triggerFilterEmployees();
                addRow();
            });
        </script>
    @endpush
</x-app-layout>

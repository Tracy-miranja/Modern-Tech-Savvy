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
                                        <option value="{{ $leavePeriod->id }}">{{ $leavePeriod->name }}</option>
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
                            <h6 class="mb-3">Select Employees (Optional)</h6>
                            <div id="employee-checkboxes" class="form-group">
                                <!-- Dynamic employee checkboxes will be added here -->
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

                // Initialize Select2 for multiple select fields
                $('.select2-multiple').select2();

                async function triggerFilterEmployees() {
                    const leavePeriodId = document.getElementById('leave_period_id').value;
                    const checkboxesContainer = document.getElementById('employee-checkboxes');

                    // Show loading state
                    checkboxesContainer.innerHTML = '<p class="text-muted">Loading employees...</p>';

                    // Collect filters from selects
                    const filters = {
                        leave_period_id: leavePeriodId,
                        departments: $('#departments').val() || [],
                        job_categories: $('#job_categories').val() || [],
                        employment_terms: $('#employment_terms').val() || [],
                        locations: $('#locations').val() || []
                    };

                    try {
                        console.log('Calling getAllEmployeesList with filters:', filters);
                        console.log('typeof getAllEmployeesList:', typeof window.getAllEmployeesList);
                        console.log('typeof getLeaveEntitlementsByPeriod:', typeof window.getLeaveEntitlementsByPeriod);

                        // Wait for both to complete
                        const [allEmployees, entitlements] = await Promise.all([
                            window.getAllEmployeesList(filters),
                            leavePeriodId ? window.getLeaveEntitlementsByPeriod(leavePeriodId) : Promise.resolve([])
                        ]);

                        console.log('Employees received:', allEmployees);
                        console.log('Entitlements received:', entitlements);

                        if (Array.isArray(allEmployees) && allEmployees.length > 0) {
                            checkboxesContainer.innerHTML = ''; // Clear loading state

                            allEmployees.forEach(employee => {
                                const entitlement = Array.isArray(entitlements)
                                    ? entitlements.find(e => e.employee_id === employee.id)
                                    : null;

                                const employeeName = employee.user ? employee.user.name : 'Unknown';
                                const departmentName = employee.department ? employee.department.name : 'N/A';
                                const badgeHtml = entitlement ? `<span class="badge bg-success ms-2">${entitlement.entitled_days} days</span>` : '';

                                const checkbox = `
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                               id="employee_${employee.id}"
                                               name="employees[]"
                                               value="${employee.id}"
                                               ${entitlement ? 'checked' : ''}>
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
                    } catch (error) {
                        console.error('Error fetching employees or entitlements:', error);
                        console.error('Error stack:', error.stack);
                        checkboxesContainer.innerHTML = "<p class=\"text-danger\">Error fetching employees. Please try again later.</p>";
                    }
                }

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

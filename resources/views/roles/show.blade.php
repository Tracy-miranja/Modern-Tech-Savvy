<x-app-layout title="Role Details">
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $role->name }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Created:</strong> {{ $role->created_at->format('d M Y H:i') }}</p>
                            <p><strong>Updated:</strong> {{ $role->updated_at->format('d M Y H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Permissions</h6>
                            @if($role->permissions->isEmpty())
                            <p>No permissions assigned.</p>
                            @else
                            <ul class="list-group">
                                @foreach($role->permissions as $permission)
                                <li class="list-group-item">{{ $permission->name }}</li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6>Assign Role to Employee</h6>
                        <form id="assignRoleForm" class="needs-validation" novalidate>
                            @csrf
                            <input type="hidden" name="role_id" value="{{ $role->id }}">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Select Employee <span
                                        class="text-danger">*</span></label>
                                <select name="user_id" id="user_id" class="form-select" required>
                                    <option value="">Select an employee</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select an employee.</div>
                            </div>

                            @if($role->name === 'chief-of-staff')
                            <div class="mb-3">
                                <label for="departments" class="form-label">Assign Departments <span
                                        class="text-danger">*</span></label>
                                <select name="departments[]" id="departments" class="form-select" multiple required>

                                    @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Hold Ctrl (or Cmd on Mac) to select multiple departments.</small>
                                <div class="invalid-feedback">Please select at least one department.</div>
                            </div>
                            @endif

                            <button type="button" onclick="assignRole(this)" class="btn btn-primary">Assign
                                Role</button>
                        </form>
                    </div>
                    <div class="mt-4">
                        <h6>Employees with this Role</h6>
                        <table class="table table-hover table-striped" id="assignedUsersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Departments</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roleUsers as $index => $user)
                                <tr data-user-id="{{ $user->id }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($role->name === 'chief-of-staff')
                                            <div class="departments-display" id="depts-{{ $user->id }}">
                                                @if($user->employee && $user->employee->departments && $user->employee->departments->count() > 0)
                                                    @foreach($user->employee->departments as $dept)
                                                    <span class="badge bg-info" data-dept-id="{{ $dept->id }}">{{ $dept->name }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">No departments assigned</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($role->name === 'chief-of-staff')
                                        <button class="btn btn-warning btn-sm me-2" data-user="{{ $user->id }}"
                                            data-role="{{ $role->id }}" onclick="editDepartments(this)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        @endif
                                        <button class="btn btn-danger btn-sm" data-user="{{ $user->id }}"
                                            data-role="{{ $role->id }}" onclick="removeRole(this)">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('business.roles.index', ['business' => $businessSlug]) }}"
                            class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing departments -->
    @if($role->name === 'chief-of-staff')
    <div class="modal fade" id="editDepartmentsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Departments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editDepartmentsForm">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="mb-3">
                            <label for="editDepartments" class="form-label">Select Departments</label>
                            <select name="departments[]" id="editDepartments" class="form-select" multiple required>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveDepartments()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
    <script src="{{ asset('js/main/roles.js') }}" type="module"></script>
    <script>
        function editDepartments(button) {
            const userId = button.getAttribute('data-user');
            const deptDisplay = document.getElementById('depts-' + userId);

            document.getElementById('editUserId').value = userId;

            // Get current departments from badges
            const badges = deptDisplay.querySelectorAll('.badge');
            const currentDepts = Array.from(badges).map(b => b.getAttribute('data-dept-id')).filter(Boolean);

            // Set selected options
            const select = document.getElementById('editDepartments');
            Array.from(select.options).forEach(option => {
                option.selected = currentDepts.includes(option.value);
            });

            const modal = new bootstrap.Modal(document.getElementById('editDepartmentsModal'));
            modal.show();
        }

        function saveDepartments() {
            const userId = document.getElementById('editUserId').value;
            const departments = Array.from(document.getElementById('editDepartments').selectedOptions).map(o => o.value);

            if (departments.length === 0) {
                alert('Please select at least one department');
                return;
            }

            const url = '{{ route("business.roles.update-departments", ["business" => $businessSlug]) }}';
            console.log('Sending request to:', url);
            console.log('Payload:', { user_id: userId, role_id: {{ $role->id }}, departments: departments });

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    user_id: userId,
                    role_id: {{ $role->id }},
                    departments: departments
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);

                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Response text:', text);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success === true) {
                    toastr.success('Departments updated successfully');
                    location.reload();
                } else {
                    toastr.error('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Failed to update departments: ' + error.message);
            });
        }
    </script>
    @endpush
</x-app-layout>

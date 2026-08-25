
<x-app-layout title="{{ $page }}">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark">{{ $page }}</h2>
                    <span id="contractActionCount"
                        class="badge bg-primary-soft text-primary px-3 py-2">{{ $contractActions->count() }}
                        Actions</span>
                </div>

                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-4">Contracts and Licenses Nearing Expiry (Within 30 Days)</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Contract End Date</th>
                                        <th>License Expiry Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($employees as $employee)
                                    @if($employee->employmentDetails && ($employee->employmentDetails->contract_end_date && \Carbon\Carbon::parse($employee->employmentDetails->contract_end_date)->diffInDays(\Carbon\Carbon::now()) <= 30) || ($employee->employmentDetails->license_expiry_date && \Carbon\Carbon::parse($employee->employmentDetails->license_expiry_date)->diffInDays(\Carbon\Carbon::now()) <= 30))
                                    <tr>
                                        <td>{{ $employee->user->name }}</td>
                                        <td>
                                            @if($employee->employmentDetails && $employee->employmentDetails->contract_end_date)
                                            {{ \Carbon\Carbon::parse($employee->employmentDetails->contract_end_date)->format('M d, Y') }}
                                            @if(\Carbon\Carbon::parse($employee->employmentDetails->contract_end_date)->diffInDays(\Carbon\Carbon::now()) <= 30)
                                            <br><small class="text-danger">Nearing expiry</small>
                                            @endif
                                            @else
                                            N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($employee->employmentDetails && $employee->employmentDetails->license_expiry_date)
                                            {{ \Carbon\Carbon::parse($employee->employmentDetails->license_expiry_date)->format('M d, Y') }}
                                            @if(\Carbon\Carbon::parse($employee->employmentDetails->license_expiry_date)->diffInDays(\Carbon\Carbon::now()) <= 30)
                                            <br><small class="text-danger">Nearing expiry</small>
                                            @endif
                                            @else
                                            N/A
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="sendReminder({{ $employee->id }})">
                                                <i class="fa fa-envelope"></i> Send Reminder
                                            </button>
                                        </td>
                                    </tr>
                                    @endif
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No contracts or licenses nearing expiry.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-4">Batch Termination</h4>
                        <form id="batchTerminationForm" class="needs-validation" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label for="termination_reason" class="form-label fw-medium text-dark">Reason for
                                    Termination</label>
                                <input type="text" name="reason" id="termination_reason" class="form-control" required>
                                @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="termination_description" class="form-label fw-medium text-dark">Description
                                    (Optional)</label>
                                <textarea name="description" id="termination_description" class="form-control"
                                    rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="termination_action_date" class="form-label fw-medium text-dark">Termination
                                    Date</label>
                                <input type="date" name="action_date" id="termination_action_date" class="form-control"
                                    value="{{ now()->toDateString() }}" required>
                                @error('action_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium text-dark">Select Employees</label>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="selectAllEmployees"></th>
                                                <th>Employee</th>
                                                <th>Status</th>
                                                <th>Employment Term</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($terminationEmployees as $employee)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="employee_ids[]"
                                                        value="{{ $employee->id }}" @if($employee->employmentDetails &&
                                                    $employee->employmentDetails->status === 'terminated') disabled
                                                    @endif>
                                                </td>
                                                <td>{{ $employee->user->name }}</td>
                                                <td>{{ ucfirst($employee->employmentDetails->status ?? 'active') }}</td>
                                                <td>{{ ucfirst($employee->employmentDetails->employment_term ?? 'N/A') }}
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center">No employees available.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-center">
                                        {{ $terminationEmployees->links() }}
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="action_type" value="termination">
                            <button type="button" class="btn btn-danger btn-modern" onclick="batchTerminate(this)">
                                <i class="fa fa-ban me-2"></i> Terminate Selected
                            </button>
                        </form>
                    </div>
                </div>

                <div>
                    <h4 class="fw-semibold text-dark mt-4 mb-4">Contract Actions</h4>
                    <div id="contractActionsContainer">
                        @include('employees.contracts._cards')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .bg-primary-soft {
            background-color: #e7f1ff;
        }

        .btn-modern {
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            box-shadow: none;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .pagination {
            margin-top: 1rem;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/contract-actions.js') }}" type="module"></script>
    <script>
        // Select all checkboxes on current page
        $('#selectAllEmployees').on('change', function() {
            $('input[name="employee_ids[]"]:not(:disabled)').prop('checked', this.checked);
        });

        // Update select all checkbox based on individual checkboxes
        $('input[name="employee_ids[]"]').on('change', function() {
            let allChecked = $('input[name="employee_ids[]"]:not(:disabled)').length ===
                $('input[name="employee_ids[]"]:not(:disabled):checked').length;
            $('#selectAllEmployees').prop('checked', allChecked);
        });
    </script>
    @endpush
</x-app-layout>

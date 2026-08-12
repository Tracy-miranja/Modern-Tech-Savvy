<x-app-layout>
    <div class="row g-20">

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form action="" method="post" id="leaveTypeForm">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="name_select">Name</label>
                            <select id="name_select" name="name" class="form-select" required>
                                <option value="">Select a leave type</option>
                                @foreach (getLeaveTypeNames() as $leaveTypeName)
                                    <option value="{{ $leaveTypeName }}">{{ $leaveTypeName }}</option>
                                @endforeach
                                <option value="__other__">Other (specify)...</option>
                            </select>
                            <input type="text" id="name_custom" placeholder="Enter a custom leave type name"
                                class="form-control mt-2 d-none">
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Leave description"></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="requires_approval">Requires approval</label>
                                <select name="requires_approval" id="requires_approval" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="is_paid">Is paid</label>
                                <select name="is_paid" id="is_paid" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="allowance_accruable">Allowance accruable</label>
                                <select name="allowance_accruable" id="allowance_accruable" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="allows_half_day">Allows half day</label>
                                <select name="allows_half_day" id="allows_half_day" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="requires_attachment">Requires attachment</label>
                                <select name="requires_attachment" id="requires_attachment" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="max_continuous_days">Max continuous days</label>
                                <input type="number" name="max_continuous_days" id="max_continuous_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');">
                            </div>

                            <div class="col-md-12">
                                <label for="min_notice_days">Min notice days</label>
                                <input type="number" name="min_notice_days" id="min_notice_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>
                        </div>

                        <h6 class="mt-3">Leave Policies</h6>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="department">Department</label>
                                <select name="department" id="department" class="form-select">
                                    <option value="all">All</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->slug }}"> {{ $department->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="job_category">Job categories</label>
                                <select name="job_category" id="job_category" class="form-select">
                                    <option value="all">All</option>
                                    @foreach ($job_categories as $job_category)
                                        <option value="{{ $job_category->slug }}"> {{ $job_category->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="gender_applicable">Gender applicable</label>
                                <select name="gender_applicable" id="gender_applicable" class="form-select">
                                    <option value="all">All</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="prorated_for_new_employees">Prorated for new employees</label>
                                <select name="prorated_for_new_employees" id="prorated_for_new_employees"
                                    class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="default_days">Default days</label>
                                <input type="number" name="default_days" id="default_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-4">
                                <label for="accrual_frequency">Accrual frequency</label>
                                <select name="accrual_frequency" id="accrual_frequency" class="form-select">
                                    <option value="monthly">{{ ucfirst('monthly') }}</option>
                                    <option value="quarterly">{{ ucfirst('quarterly') }}</option>
                                    <option value="yearly">{{ ucfirst('yearly') }}</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="accrual_amount">Accrual amount</label>
                                <input type="number" name="accrual_amount" id="accrual_amount"
                                    class="form-control" step="0.01" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-6">
                                <label for="max_carryover_days">Max carryover days</label>
                                <input type="number" name="max_carryover_days" id="max_carryover_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-6">
                                <label for="minimum_service_days_required">minimum service days required</label>
                                <input type="number" name="minimum_service_days_required"
                                    id="minimum_service_days_required" class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-6">
                                <label for="effective_date">Effective date</label>
                                <input type="date" class="form-control datepicker" id="effective_date"
                                    name="effective_date" required>
                            </div>

                            <div class="col-md-6">
                                <label for="end_date">End date</label>
                                <input type="date" class="form-control datepicker" id="end_date" name="end_date">
                            </div>

                            <div class="col-md-4">
                                <label for="exclude_public_holidays">Exclude Public Holidays</label>
                                <select name="exclude_public_holidays" id="exclude_public_holidays" class="form-select">
                                    <option value="1" selected>Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <small class="text-muted d-block mt-1">
                                    Skip business holidays (from the Holidays calendar) when counting days for this leave type.
                                </small>
                            </div>

                            <div class="col-md-4">
                                <label for="exclude_non_working_days">Exclude Non-Working Days</label>
                                <select name="exclude_non_working_days" id="exclude_non_working_days" class="form-select">
                                    <option value="1" selected>Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <small class="text-muted d-block mt-1">
                                    Skip the company's non-working days (set in Leave Settings, e.g. weekends) when counting days for this leave type.
                                </small>
                            </div>

                            <div class="col-md-4">
                                <label for="approval_levels">Approval Levels</label>
                                <select name="approval_levels" id="approval_levels" class="form-select">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label d-block">Who Approves Each Level</label>
                                <div id="approval_chain_rows" class="d-flex flex-wrap gap-2" data-approval-chain="[]"></div>
                                <small class="text-muted d-block mt-1">
                                    "Employee's Manager" walks the requester's real reporting line. "HR" routes straight to HR regardless of who manages the requester - e.g. pick this for every level if all leave requests for this type should go entirely to HR. "Department Head" requires the approver to hold the Head of Department role in the requester's own department.
                                </small>
                            </div>

                            <div class="col-md-4">
                                <label for="allows_backdating">Allows Backdating</label>
                                <select name="allows_backdating" id="allows_backdating" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0" selected>No</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="is_stepwise">Is Stepwise</label>
                                <select name="is_stepwise" id="is_stepwise" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0" selected>No</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="stepwise_rules">Stepwise Rules</label>
                                <textarea name="stepwise_rules" id="stepwise_rules" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary w-100" onclick="saveLeaveType(this)">
                                    Save Leave Type
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div id="leaveTypeContainer">
                <div class="card">
                    <div class="card-body"> {{ loader() }} </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        @include('modals.leave-type')
        <script src="{{ asset('js/main/leave-type.js') }}" type="module"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof getLeaveType === 'function') {
                    getLeaveType();
                }
            });
        </script>
    @endpush
</x-app-layout>

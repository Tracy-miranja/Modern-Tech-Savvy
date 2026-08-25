<?php namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\LeaveEntitlement;
use App\Models\LeavePolicy;
use App\Models\LeavePeriod;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\LeaveRequestSubmitted;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\LeaveStatusNotification;

class LeaveRequestController extends Controller
{
    use HandleTransactions;

public function fetch(Request $request)
{
    $business = Business::findBySlug(session('active_business_slug'));
    if (!$business) {
        return RequestResponse::badRequest('Active business not found in session.');
    }

    $status = strtolower($request->get('status', 'pending'));
    $query = LeaveRequest::with(['employee.user', 'leaveType'])
        ->where('business_id', $business->id);

    $user = auth()->user();
    $emp = $user->activeEmployee();
    $activeRole = session('active_role');

    if ($activeRole === 'business-employee' && $emp) {

        $reportIds = $emp->directReports()->pluck('id');
        $query->where(function ($q) use ($emp, $reportIds) {
            $q->where('employee_id', $emp->id);
            if ($reportIds->isNotEmpty()) {
                $q->orWhereIn('employee_id', $reportIds);
            }
        });
    }

    if ($activeRole === 'head-of-department' && $emp) {
        if (empty($emp->department_id)) {
            $query->whereRaw('1=0');
        } else {
            $deptId = (int) $emp->department_id;
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }
    }

    if ($activeRole === 'chief-of-staff' && $emp) {
        $deptIds = $emp->assignedDepartmentIds();
        if (empty($deptIds)) {
            $query->whereRaw('1=0');
        } else {
            $query->whereHas('employee', function ($q) use ($deptIds) {
                $q->whereIn('department_id', $deptIds);
            });
        }
    }

    if (in_array($status, ['pending', 'approved', 'rejected', 'declined', 'cancelled'], true)) {
        $query->status($status);
    }

    if ($request->filled('department_id')) {
        $departmentId = (int) $request->input('department_id');
        $query->whereHas('employee', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    if ($request->filled('location_id')) {
        $locationId = (int) $request->input('location_id');
        $query->whereHas('employee', function ($q) use ($locationId) {
            $q->where('location_id', $locationId);
        });
    }

    if ($request->filled('leave_type_id')) {
        $query->where('leave_type_id', (int) $request->input('leave_type_id'));
    }

    if ($request->filled('leave_period_id')) {
        $leavePeriod = LeavePeriod::where('business_id', $business->id)->find($request->input('leave_period_id'));
        if ($leavePeriod) {
            $query->where('start_date', '<=', $leavePeriod->end_date)
                ->where('end_date', '>=', $leavePeriod->start_date);
        }
    }

    $leaveRequests = $query->latest('id')->get();
    $currentBusiness = $business;

    $html = view('leave._leave_requests_table', compact('leaveRequests', 'currentBusiness'))
        ->with('status', $status)
        ->render();

    return RequestResponse::ok('Leave requests fetched successfully.', $html);
}

protected function canUserViewLeaveRequest(User $user, LeaveRequest $leave): bool
{
    $active = session('active_role');

    $activeEmployee = $user->activeEmployee();

    if ($active === 'business-employee') {
        return (int)optional($activeEmployee)->id === (int)$leave->employee_id;
    }

    if (in_array($active, ['business-hr','business-admin'], true)) {
        return (int)$activeEmployee?->business_id === (int)$leave->business_id
            || (int)$user->business_id === (int)$leave->business_id;
    }

    if ($active === 'head-of-department') {
        return (int)$activeEmployee?->department_id === (int)optional($leave->employee)->department_id;
    }

    if ($active === 'chief-of-staff') {
        $dept = (int)optional($leave->employee)->department_id;
        return $dept > 0 && in_array($dept, $activeEmployee?->assignedDepartmentIds() ?? [], true);
    }

    return false;
}

    public function show(Request $request, $reference = null)
    {
        $ref = $reference ?? $request->get('reference_number');
        if (!$ref) {
            abort(404, 'Reference number missing.');
        }

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            abort(404, 'Active business not found.');
        }

        $leave = LeaveRequest::with(['employee.user', 'leaveType', 'approvedBy'])
            ->where('business_id', $business->id)
            ->where('reference_number', $ref)
            ->firstOrFail();

        if (!$this->canUserViewLeaveRequest(auth()->user(), $leave)) {
            abort(403, 'You are not allowed to view this request.');
        }

        return view('leave.show', ['leave' => $leave, 'business' => $business]);
    }

    public function store(Request $request)
    {

        $base = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
        ]);

        $leaveType = LeaveType::findOrFail($base['leave_type_id']);

        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'half_day' => 'nullable|boolean',
            'half_day_type' => 'nullable|string|in:morning,afternoon|required_if:half_day,1',

            'attachment' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048',
            'attach_later' => 'nullable|boolean',

            'handover_notes' => 'nullable|string|max:5000',
            'handover_attachment' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048',
            'reliever_employee_id' => 'nullable|integer|exists:employees,id|different:employee_id',
        ]);

        return $this->handleTransaction(function () use ($validated, $leaveType, $request) {

            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Active business not found in session.');
            }

            $employeeId = $validated['employee_id'] ?? (auth()->user()->activeEmployee()->id ?? null);
            if (!$employeeId) {
                return RequestResponse::badRequest('No employee selected for this leave request.');
            }

            $employee = Employee::with('user')->findOrFail($employeeId);

            if ((int)$employee->business_id !== (int)$business->id) {
                return RequestResponse::badRequest('Selected employee does not belong to the current business.');
            }

            $relieverId = $validated['reliever_employee_id'] ?? null;
            if ($relieverId) {
                if ((int)$relieverId === (int)$employeeId) {
                    return RequestResponse::badRequest('You cannot assign yourself as your own reliever.');
                }
                $reliever = Employee::where('business_id', $business->id)->where('id', $relieverId)->first();
                if (!$reliever) {
                    return RequestResponse::badRequest('Selected reliever does not belong to the current business.');
                }
            }

            if (property_exists($leaveType, 'business_id') && !is_null($leaveType->business_id)) {
                if ((int)$leaveType->business_id !== (int)$business->id) {
                    return RequestResponse::badRequest('This leave type is not available in the current business.');
                }
            }

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            if ($startDate->lt(today()) && empty($leaveType->allows_backdating)) {
                return RequestResponse::badRequest('Backdating is not allowed for this leave type.');
            }

            if (!$leaveType->allows_half_day && !empty($validated['half_day'])) {
                return RequestResponse::badRequest('Half-day is not allowed for this leave type.');
            }

            if (empty($leaveType->allows_backdating) && is_numeric($leaveType->min_notice_days ?? null)) {
                $diff = now()->startOfDay()->diffInDays($startDate->copy()->startOfDay(), false);
                if ($diff < (int)$leaveType->min_notice_days) {
                    return RequestResponse::badRequest("Minimum notice is {$leaveType->min_notice_days} day(s) before the start date.");
                }
            }

            $intervalPolicy = LeavePolicy::where('leave_type_id', $leaveType->id)
                ->whereDate('effective_date', '<=', $startDate)
                ->where(function ($q) use ($startDate) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate);
                })
                ->where(function ($q) use ($employee) {
                    $q->whereNull('department_id')->orWhere('department_id', $employee->department_id);
                })
                ->where(function ($q) use ($employee) {
                    $q->whereNull('job_category_id')->orWhere('job_category_id', $employee->job_category_id);
                })
                ->whereNotNull('min_interval_days')
                ->orderByRaw('department_id IS NULL, job_category_id IS NULL')
                ->first();

            if ($intervalPolicy && $intervalPolicy->min_interval_days) {
                $lastRequest = LeaveRequest::where('business_id', $business->id)
                    ->where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->whereNull('rejection_reason')
                    ->where('end_date', '<', $startDate->toDateString())
                    ->orderByDesc('end_date')
                    ->first();

                if ($lastRequest) {
                    $eligibleFrom = Carbon::parse($lastRequest->end_date)->addDays((int) $intervalPolicy->min_interval_days);
                    if ($startDate->lt($eligibleFrom)) {
                        return RequestResponse::badRequest(
                            "You must wait until {$eligibleFrom->toDateString()} before submitting another {$leaveType->name} request (minimum {$intervalPolicy->min_interval_days} day(s) between requests)."
                        );
                    }
                }
            }

            $coveringPeriod = LeavePeriod::where('business_id', $business->id)
                ->whereDate('start_date', '<=', $startDate)
                ->whereDate('end_date', '>=', $startDate)
                ->first();
            if ($coveringPeriod && $coveringPeriod->isClosed()) {
                return RequestResponse::badRequest("The leave period \"{$coveringPeriod->name}\" covering this date has been closed - no new requests can be dated within it.");
            }

            $totalDays = LeaveRequest::calculateTotalDays(
                $startDate,
                $endDate,
                (bool)($validated['half_day'] ?? false),
                $leaveType,
                $business->id,
                $employee->location_id
            );

            if (!empty($leaveType->max_continuous_days) && $totalDays > (float)$leaveType->max_continuous_days) {
                return RequestResponse::badRequest("You cannot take more than {$leaveType->max_continuous_days} day(s) for this leave type at once.");
            }

            if (LeaveRequest::hasOverlap($employeeId, $startDate, $endDate)) {
                return RequestResponse::badRequest('You already have a pending/approved leave that overlaps with these dates.');
            }

            $attachmentPath = null;
            $requiresDocumentation = false;
            $isTentative = false;

            if ($leaveType->requires_attachment) {
                $attachLater = (bool)($validated['attach_later'] ?? false);

                if ($request->hasFile('attachment')) {
                    try {
                        $attachmentPath = $request->file('attachment')->store('attachments', 'public');
                    } catch (\Exception $e) {
                        Log::error("Attachment upload failed: ".$e->getMessage());
                        return RequestResponse::badRequest('Failed to upload attachment. Please try again.');
                    }
                } elseif ($attachLater) {
                    $requiresDocumentation = true;
                    if (!empty($leaveType->is_stepwise)) {
                        $isTentative = true;
                    }
                } else {
                    return RequestResponse::badRequest('Attachment is required for this leave type. You may choose to upload later.');
                }
            } else {
                if ($request->hasFile('attachment')) {
                    try {
                        $attachmentPath = $request->file('attachment')->store('attachments', 'public');
                    } catch (\Exception $e) {
                        Log::error("Attachment upload failed: ".$e->getMessage());
                        return RequestResponse::badRequest('Failed to upload attachment. Please try again.');
                    }
                }
            }

            $handoverAttachmentPath = null;
            if ($request->hasFile('handover_attachment')) {
                try {
                    $handoverAttachmentPath = $request->file('handover_attachment')->store('attachments', 'public');
                } catch (\Exception $e) {
                    Log::error("Handover attachment upload failed: ".$e->getMessage());
                    return RequestResponse::badRequest('Failed to upload handover attachment. Please try again.');
                }
            }

            $entitlementQuery = LeaveEntitlement::where('business_id', $business->id)
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveType->id);

            $coveringPeriodIds = \App\Models\LeavePeriod::where('business_id', $business->id)
                ->whereDate('start_date', '<=', $startDate)
                ->whereDate('end_date', '>=', $startDate)
                ->pluck('id');

            if ($coveringPeriodIds->isNotEmpty()) {
                $entitlementQuery->whereIn('leave_period_id', $coveringPeriodIds);
            }

            $remaining = $entitlementQuery->first()?->getRemainingDays() ?? 0;

            if ($remaining < $totalDays) {
                return RequestResponse::badRequest("You have {$remaining} remaining day(s) for this leave type, but you requested {$totalDays}.");
            }

            $leaveRequest = LeaveRequest::create([
                'reference_number' => LeaveRequest::generateUniqueReferenceNumber($business->id),
                'employee_id' => $employeeId,
                'business_id' => $business->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'half_day' => (bool)($validated['half_day'] ?? false),
                'half_day_type' => $validated['half_day_type'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'handover_notes' => $validated['handover_notes'] ?? null,
                'handover_attachment' => $handoverAttachmentPath,
                'attachment' => $attachmentPath,
                'requires_documentation' => $requiresDocumentation,
                'is_tentative' => $isTentative,
                'current_approval_level' => 0,
            ]);

            if ($relieverId) {
                $delegation = \App\Models\LeaveDelegation::create([
                    'business_id' => $business->id,
                    'employee_id' => $employeeId,
                    'delegate_id' => $relieverId,
                    'leave_request_id' => $leaveRequest->id,
                    'duties_delegated' => $validated['handover_notes'] ?? '',
                ]);

                $relieverUser = optional($reliever)->user;
                if ($relieverUser) {
                    \Illuminate\Support\Facades\Notification::send(
                        $relieverUser,
                        new \App\Notifications\LeaveDelegationAssigned($delegation)
                    );
                }
            }

            $this->handleLeaveApprovalProcess($leaveRequest);

            return RequestResponse::ok('Leave request created successfully.');
        });
    }

    protected function handleLeaveApprovalProcess(LeaveRequest $leaveRequest)
    {
        $leaveType = $leaveRequest->leaveType;
        $approvalLevels = (int)($leaveType->approval_levels ?? 1);

        $requiresApproval = ($leaveType->requires_approval ?? true) || $approvalLevels > 0;

        if (!$requiresApproval) {

            if (!$leaveRequest->requires_documentation || $leaveRequest->attachment) {
                $this->autoApproveLeave($leaveRequest);
                return;
            }

        }

        LeaveEntitlement::recomputeUsageFor(
            (int) $leaveRequest->employee_id,
            (int) $leaveRequest->leave_type_id,
            (int) $leaveRequest->business_id
        );

        $this->sendApplicationNotifications($leaveRequest);
    }

    protected function autoApproveLeave(LeaveRequest $leaveRequest)
    {
        try {

            $leaveRequest->approved_by = auth()->id() ?? 1;
            $leaveRequest->approved_at = now();
            $leaveRequest->is_tentative = false;
            $leaveRequest->current_approval_level = 1;

            $history = [];
            $history[] = [
                'level' => 1,
                'approver_id' => $leaveRequest->approved_by,
                'approver_name' => 'System Auto-Approval',
                'approved_at' => now()->toDateTimeString(),
                'comments' => 'Auto-approved (no approval required for this leave type)',
            ];
            $leaveRequest->approval_history = $history;
            $leaveRequest->save();

            LeaveEntitlement::recomputeUsageFor(
                (int) $leaveRequest->employee_id,
                (int) $leaveRequest->leave_type_id,
                (int) $leaveRequest->business_id
            );

            $this->sendFinalApprovalNotificationsWithDelay($leaveRequest);

        } catch (\Exception $e) {
            Log::error("Error auto-approving leave {$leaveRequest->id}: " . $e->getMessage());
        }
    }

    public function requests(Request $request, $slug = null)
    {
        $slug = $slug ?? $request->leave_type_slug;
        if (!$slug) abort(404, 'Leave type slug missing.');

        $activeRole = session('active_role');
        $hodDeptId  = auth()->user()->activeEmployee()->department_id ?? null;

        $leaveTypeQuery = LeaveType::where('slug', $slug)
            ->with(['leavePolicies']);

        $leaveTypeQuery->with(['leaveRequests' => function ($q) use ($activeRole, $hodDeptId) {
            $q->with(['employee.user']);
            if ($activeRole === 'head-of-department') {
                if (empty($hodDeptId)) {
                    $q->whereRaw('1=0');
                } else {
                    $q->whereHas('employee', function ($qq) use ($hodDeptId) {
                        $qq->where('department_id', (int)$hodDeptId);
                    });
                }
            }
        }]);

        $leaveType = $leaveTypeQuery->firstOrFail();

        return view('leave.leave_type_requests', compact('leaveType'));
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => 'required|exists:leave_requests,reference_number',
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|required_if:status,rejected|string',
            'comments' => 'nullable|string|max:500',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            try {
                $leaveRequest = LeaveRequest::where('reference_number', $validated['reference_number'])->firstOrFail();

                if (!$leaveRequest->canUserApprove(auth()->user())) {
                    return RequestResponse::badRequest('You do not have permission to approve this leave request at this level.');
                }

                if ($leaveRequest->status !== 'pending') {
                    return RequestResponse::badRequest('This leave request has already been processed.');
                }

                if ($validated['status'] === 'approved') {
                    $approverId = auth()->id();
                    $activeRole = $this->resolveActiveRole();

                    if (!$activeRole) {
                        return RequestResponse::badRequest('Active role not found in session.');
                    }

                    $alreadyApprovedSameRole = collect($leaveRequest->approval_history ?? [])
                        ->contains(function ($e) use ($approverId, $activeRole) {
                            $sameUser = (int)($e['approver_id'] ?? 0) === (int)$approverId;
                            $entryRole = strtolower((string)($e['approver_role'] ?? ''));
                            return $sameUser && ($entryRole !== '' && $entryRole === $activeRole);
                        });

                    if ($alreadyApprovedSameRole) {
                        return RequestResponse::badRequest('You have already approved this request with your current role.');
                    }

                    return $this->processApproval($leaveRequest, $validated['comments'] ?? null);
                }

                return $this->processRejection($leaveRequest, $validated['rejection_reason'], $validated['comments'] ?? null);
            } catch (\Exception $e) {
                Log::error("Error in leave status method: " . $e->getMessage());
                return RequestResponse::badRequest('Failed to process leave request. Please try again.');
            }
        });
    }

    protected function processApproval(LeaveRequest $leaveRequest, $comments = null)
    {
        try {
            $nextLevel      = $leaveRequest->getNextApprovalLevel();
            $requiredLevels = (int) (optional($leaveRequest->leaveType)->approval_levels ?? 1);

            $approverId = auth()->id();
            if (!$approverId) {
                return RequestResponse::badRequest('Invalid approver session.');
            }

            $activeRole = $this->resolveActiveRole();
            if (!$activeRole) {
                return RequestResponse::badRequest('Active role not found in session.');
            }

            $history = is_array($leaveRequest->approval_history ?? null) ? $leaveRequest->approval_history : [];

            $alreadyApprovedSameRole = collect($history)->contains(function ($entry) use ($approverId, $activeRole) {
                $sameUser = (int)($entry['approver_id'] ?? 0) === (int)$approverId;
                $entryRole = strtolower((string)($entry['approver_role'] ?? ''));

                return $sameUser && ($entryRole !== '' && $entryRole === $activeRole);
            });

            if ($alreadyApprovedSameRole) {
                return RequestResponse::badRequest('You have already approved this request with your current role.');
            }

            if ($nextLevel >= $requiredLevels) {
                if ($leaveRequest->requires_documentation && !$leaveRequest->attachment) {
                    return RequestResponse::badRequest('Cannot finalize approval: documentation is required.');
                }
            }

            $leaveRequest->current_approval_level = $nextLevel;
            $history[] = [
                'level'         => $nextLevel,
                'approver_id'   => $approverId,
                'approver_name' => auth()->user()->name,
                'approver_role' => $activeRole,
                'approved_at'   => now()->toDateTimeString(),
                'comments'      => $comments,
            ];
            $leaveRequest->approval_history = $history;
            $leaveRequest->rejection_reason = null;
            $leaveRequest->save();

            if ($leaveRequest->needsMoreApprovals()) {
                $this->sendNextLevelNotificationsWithDelay($leaveRequest);
                return RequestResponse::ok("Leave advanced to approval level {$nextLevel}. Waiting for final approval.", [
                    'new_status' => 'pending',
                ]);
            }

            $this->finalizeApprovalSafely($leaveRequest);
            $this->sendFinalApprovalNotificationsWithDelay($leaveRequest);

            return RequestResponse::ok('Leave request approved successfully.', [
                'new_status' => 'approved',
            ]);
        } catch (\Exception $e) {
            Log::error("Error processing leave approval for {$leaveRequest->reference_number}: " . $e->getMessage(), [
                'active_role' => $this->resolveActiveRole(),
                'user_id'     => auth()->id(),
            ]);
            return RequestResponse::badRequest('Failed to process approval. Please try again.');
        }
    }

    protected function processRejection(LeaveRequest $leaveRequest, $rejectionReason, $comments = null)
    {
        $leaveRequest->approved_by = null;
        $leaveRequest->approved_at = null;
        $leaveRequest->current_approval_level = 0;
        $leaveRequest->approval_history = [];
        $leaveRequest->rejection_reason = $rejectionReason;
        $leaveRequest->is_tentative = false;
        $leaveRequest->save();

        LeaveEntitlement::recomputeUsageFor(
            (int) $leaveRequest->employee_id,
            (int) $leaveRequest->leave_type_id,
            (int) $leaveRequest->business_id
        );

        try {
            $leaveRequest->employee->user->notify(new LeaveStatusNotification($leaveRequest));
        } catch (\Exception $e) {
            Log::error("Failed to send leave rejection notification for {$leaveRequest->reference_number}: " . $e->getMessage());
        }

        return RequestResponse::ok('Leave request rejected successfully.', [
            'new_status' => 'rejected',
        ]);
    }

    protected function sendApplicationNotifications(LeaveRequest $leaveRequest)
    {
        try {
            $employee  = $leaveRequest->employee;
            $leaveType = $leaveRequest->leaveType;
            $business  = $leaveRequest->business;

            Mail::to($employee->user->email)->queue(new LeaveRequestSubmitted($leaveRequest));

            $approvalLevels = (int)($leaveType->approval_levels ?? 1);

            if ($approvalLevels > 0) {

                $recipients = $this->findNextApprovers($leaveRequest, $business)->pluck('email');

                foreach ($recipients as $index => $email) {
                    $delay = now()->addSeconds(($index + 1) * 5);
                    Mail::to($email)->later($delay, new LeaveRequestSubmitted($leaveRequest));
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending application notifications for {$leaveRequest->reference_number}: " . $e->getMessage());
        }
    }

    protected function sendNextLevelNotificationsWithDelay(LeaveRequest $leaveRequest)
    {
        try {

            $business   = $leaveRequest->business;
            $recipients = $this->findNextApprovers($leaveRequest, $business)->pluck('email');

            foreach ($recipients->unique() as $index => $email) {
                $delay = now()->addSeconds(($index + 1) * 5);
                Mail::to($email)->later($delay, new LeaveRequestSubmitted($leaveRequest));
            }

            try {
                $leaveRequest->employee->user->notify(new LeaveStatusNotification($leaveRequest));
            } catch (\Throwable $e) {
                Log::warning("Employee progress notification failed: ".$e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error("Error sending next level notifications for {$leaveRequest->reference_number}: " . $e->getMessage());
        }
    }

    protected function sendFinalApprovalNotificationsWithDelay(LeaveRequest $leaveRequest)
    {
        try {
            $business = $leaveRequest->business;

            $leaveRequest->employee->user->notify(new LeaveStatusNotification($leaveRequest));

            $recipients = collect();
            $recipients = $recipients->merge($this->findBusinessAdmins($business)->pluck('email'));
            $recipients = $recipients->merge($this->approverRecipientsForLeave($leaveRequest));
            $recipients = $recipients->merge($this->findBusinessHR($business)->pluck('email'));

            foreach ($recipients->unique() as $index => $email) {
                $delay = now()->addSeconds(($index + 1) * 10);
                Mail::to($email)->later($delay, new LeaveRequestSubmitted($leaveRequest));
            }
        } catch (\Exception $e) {
            Log::error("Error sending final approval notifications for {$leaveRequest->reference_number}: " . $e->getMessage());
        }
    }

    protected function finalizeApprovalSafely(LeaveRequest $leaveRequest): void
    {
        try {
            $approverId = auth()->id();
            if (!$approverId) {
                throw new \Exception('No authenticated user found for approval');
            }

            $leaveRequest->approved_by = $approverId;
            $leaveRequest->approved_at = now();
            $leaveRequest->is_tentative = false;
            $leaveRequest->current_approval_level = max((int)$leaveRequest->current_approval_level, 1);
            $leaveRequest->save();

            LeaveEntitlement::recomputeUsageFor(
                (int) $leaveRequest->employee_id,
                (int) $leaveRequest->leave_type_id,
                (int) $leaveRequest->business_id
            );

        } catch (\Exception $e) {
            Log::error("Error finalizing approval for leave {$leaveRequest->id}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function deductLeaveEntitlementSafely(LeaveRequest $leaveRequest): void
    {
        try {
            $entitlementQuery = LeaveEntitlement::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id);

            $coveringPeriodIds = \App\Models\LeavePeriod::where('business_id', $leaveRequest->business_id)
                ->whereDate('start_date', '<=', $leaveRequest->start_date)
                ->whereDate('end_date', '>=', $leaveRequest->start_date)
                ->pluck('id');

            if ($coveringPeriodIds->isNotEmpty()) {
                $entitlementQuery->whereIn('leave_period_id', $coveringPeriodIds);
            }

            $entitlement = $entitlementQuery->first();

            if (!$entitlement) {
                Log::warning("No entitlement found for employee {$leaveRequest->employee_id} leave_type {$leaveRequest->leave_type_id} when finalizing.");
                return;
            }

            if (method_exists($entitlement, 'deductDays')) {
                $entitlement->deductDays((float)$leaveRequest->total_days);
            } elseif (!is_null($entitlement->getAttribute('used_days'))) {
                $entitlement->used_days = (float)($entitlement->used_days ?? 0) + (float)$leaveRequest->total_days;
                $entitlement->save();
            } else {

                $entitlement->getRemainingDays();
                Log::info("Entitlement updated using getRemainingDays for entitlement #{$entitlement->id}.");
            }
        } catch (\Exception $e) {
            Log::error("Error recomputing entitlement for leave {$leaveRequest->id}: " . $e->getMessage());

        }
    }

    protected function finalizeApproval(LeaveRequest $leaveRequest): void
    {
        $this->finalizeApprovalSafely($leaveRequest);
    }

    protected function sendNextLevelNotifications(LeaveRequest $leaveRequest)
    {
        $this->sendNextLevelNotificationsWithDelay($leaveRequest);
    }

    protected function sendFinalApprovalNotifications(LeaveRequest $leaveRequest)
    {
        $this->sendFinalApprovalNotificationsWithDelay($leaveRequest);
    }

    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => 'required|exists:leave_requests,reference_number',
            'attachment' => 'required|file|mimes:pdf,jpg,png,doc,docx|max:2048',
        ]);

        $leaveRequest = LeaveRequest::where('reference_number', $validated['reference_number'])->firstOrFail();

        if (!$this->canUploadOnBehalf($leaveRequest)) {
            return RequestResponse::badRequest(
                'You are not allowed to upload documents for this leave.'
            );
        }

        try {
            $path = $request->file('attachment')->store('attachments', 'public');
        } catch (\Exception $e) {
            Log::error("Failed to upload attachment for leave {$leaveRequest->id}: " . $e->getMessage());
            return RequestResponse::badRequest('Failed to upload attachment. Please try again.');
        }

        $leaveRequest->attachment = $path;
        $leaveRequest->requires_documentation = false;
        $leaveRequest->is_tentative = false;
        $leaveRequest->save();

        if ($leaveRequest->status === 'pending' && $leaveRequest->needsMoreApprovals()) {
            foreach ($this->findNextApprovers($leaveRequest, $leaveRequest->business) as $approver) {
                Mail::to($approver->email)->queue(new LeaveRequestSubmitted($leaveRequest));
            }
        }

        return RequestResponse::ok(
            'Document uploaded successfully. Your request will now proceed for approval.'
        );
    }

    protected function canUploadOnBehalf(LeaveRequest $leaveRequest): bool
    {
        $user = auth()->user();
        $employee = $user?->activeEmployee();
        if (!$employee) {
            return false;
        }

        if ((int) $employee->id === (int) $leaveRequest->employee_id) {
            return true;
        }

        if ((int) $employee->business_id !== (int) $leaveRequest->business_id) {
            return false;
        }

        $activeRole = strtolower((string) (session('active_role') ?? $user->getRoleNames()->first() ?? ''));

        return in_array($activeRole, [
            'head-of-department',
            'chief-of-staff',
            'business-hr',
            'business-admin',
        ], true);
    }

    public function revoke(Request $request)
    {
        $validated = $request->validate([
            'reference_number'     => 'required|exists:leave_requests,reference_number',
            'return_to_work_date'  => 'required|date',
            'reason'               => 'nullable|string|max:500',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $leave = LeaveRequest::where('reference_number', $validated['reference_number'])->firstOrFail();

            if ($leave->status !== 'approved') {
                return response()->json(['status' => 'error', 'message' => 'Only approved leaves can be revoked.'], 400);
            }

            if (!$leave->canUserRevoke(auth()->user())) {
                return response()->json(['status' => 'error', 'message' => 'You do not have permission to revoke this leave.'], 403);
            }

            try {
                $refund = $leave->revokeToReturnDate(
                    Carbon::parse($validated['return_to_work_date']),
                    $validated['reason'] ?? null,
                    auth()->user()
                );

                LeaveEntitlement::recomputeUsageFor(
                    (int) $leave->employee_id,
                    (int) $leave->leave_type_id,
                    (int) $leave->business_id
                );

                try {
                    $leave->employee->user->notify(new LeaveStatusNotification($leave));
                } catch (\Throwable $e) {
                    Log::warning('Notification failed after revoke: '.$e->getMessage());
                }

                return response()->json([
                    'status'         => 'success',
                    'message'        => "Leave shortened successfully. Refunded {$refund} day(s).",
                    'new_end_date'   => optional($leave->end_date)->toDateString(),
                    'refunded_days'  => $refund,
                    'new_total_days' => $leave->total_days,
                ]);
            } catch (\Throwable $e) {
                Log::error("Revoke failed for {$leave->reference_number}: ".$e->getMessage());
                return response()->json(['status' => 'error', 'message' => $e->getMessage() ?: 'Failed to revoke leave.'], 400);
            }
        });
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => 'required|exists:leave_requests,reference_number',
            'reason'            => 'nullable|string|max:500',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $leave = LeaveRequest::where('reference_number', $validated['reference_number'])->firstOrFail();

            if (!in_array($leave->status, ['pending', 'approved'], true)) {
                return response()->json(['status' => 'error', 'message' => 'Only pending or approved requests can be cancelled.'], 400);
            }

            if (!$leave->canUserCancel(auth()->user())) {
                return response()->json(['status' => 'error', 'message' => 'You do not have permission to cancel this leave, or it has already started (use Revoke instead).'], 403);
            }

            try {
                $leave->cancel($validated['reason'] ?? null, auth()->user());

                LeaveEntitlement::recomputeUsageFor(
                    (int) $leave->employee_id,
                    (int) $leave->leave_type_id,
                    (int) $leave->business_id
                );

                try {
                    $leave->employee->user->notify(new LeaveStatusNotification($leave));
                } catch (\Throwable $e) {
                    Log::warning('Notification failed after cancel: '.$e->getMessage());
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Leave request cancelled successfully.',
                ]);
            } catch (\Throwable $e) {
                Log::error("Cancel failed for {$leave->reference_number}: ".$e->getMessage());
                return response()->json(['status' => 'error', 'message' => $e->getMessage() ?: 'Failed to cancel leave.'], 400);
            }
        });
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => 'required|exists:leave_requests,reference_number',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $leaveRequest = LeaveRequest::where('reference_number', $validated['reference_number'])->firstOrFail();

            if ($leaveRequest->status !== 'pending') {
                return RequestResponse::badRequest('Cannot delete approved or rejected requests.');
            }

            $authEmployeeId = auth()->user()->activeEmployee()->id ?? null;
            if (!$authEmployeeId || $authEmployeeId !== (int)$leaveRequest->employee_id) {
                return RequestResponse::badRequest('You can only delete your own leave requests.');
            }

            $employeeId = (int) $leaveRequest->employee_id;
            $leaveTypeId = (int) $leaveRequest->leave_type_id;
            $businessId = (int) $leaveRequest->business_id;

            $leaveRequest->delete();

            LeaveEntitlement::recomputeUsageFor($employeeId, $leaveTypeId, $businessId);

            return RequestResponse::ok('Leave request deleted successfully.');
        });
    }

    protected function findNextApprovers(LeaveRequest $leaveRequest, Business $business)
    {
        $employee = $leaveRequest->employee;
        $level = max(1, $leaveRequest->getNextApprovalLevel());
        $approverType = $leaveRequest->leaveType?->approverTypeForLevel($level) ?? 'organogram';

        if ($approverType === 'hr') {
            return $this->findBusinessHR($business);
        }

        if ($approverType === 'department_head') {
            $departmentId = $employee?->department_id;
            if ($departmentId) {
                $heads = User::role('head-of-department')
                    ->whereHas('employee', function ($q) use ($business, $departmentId) {
                        $q->where('business_id', $business->id)->where('department_id', $departmentId);
                    })->get();
                if ($heads->isNotEmpty()) {
                    return $heads;
                }
            }
            return $this->findBusinessHR($business);
        }

        $manager = $employee?->managerChain()->get($level - 1);
        if ($manager && $manager->user) {
            return collect([$manager->user]);
        }

        return $this->findBusinessHR($business);
    }

    protected function findHODApprovers(Business $business)
    {
        return User::role('head-of-department')
            ->whereHas('employee', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })->get();
    }

    protected function findBusinessHR(Business $business)
    {
        return User::role('business-hr')
            ->whereHas('employee', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })->get();
    }

    protected function findBusinessAdmins(Business $business)
    {
        return User::role('business-admin')
            ->where(function ($q) use ($business) {
                $q->whereHas('employee', function ($qq) use ($business) {
                    $qq->where('business_id', $business->id);
                })

                ->orWhereHas('business', function ($qb) use ($business) {
                    $qb->where('id', $business->id);
                })

                ->orWhereHas('businesses', function ($qb) use ($business) {
                    $qb->where('businesses.id', $business->id);
                });
            })
            ->get();
    }

    protected function findHODApproversForDepartment(Business $business, int $departmentId)
    {
        $ownership = app(\App\Services\OrganizationOwnershipService::class)->computeDepartmentOwnership($business);
        $derivedHod = $ownership[$departmentId]['hod'] ?? null;

        if ($derivedHod) {
            $employee = Employee::with('user')->find($derivedHod['employee_id']);
            return $employee?->user ? collect([$employee->user]) : collect();
        }

        return User::role('head-of-department')
            ->whereHas('employee', function ($q) use ($business, $departmentId) {
                $q->where('business_id', $business->id)
                ->where('department_id', $departmentId);
            })
            ->get();
    }

    protected function findChiefOfStaffApproversForDepartment(Business $business, int $departmentId)
    {
        return User::role('chief-of-staff')
            ->whereHas('employee', function ($q) use ($business, $departmentId) {
                $q->where('business_id', $business->id)
                ->whereHas('departments', function ($qq) use ($departmentId) {
                    $qq->where('departments.id', $departmentId);
                });
            })
            ->get();
    }

    protected function approverRecipientsForLeave(LeaveRequest $leaveRequest)
    {
        $business = $leaveRequest->business ?? Business::find($leaveRequest->business_id);
        $employee = $leaveRequest->employee ?? Employee::find($leaveRequest->employee_id);

        $deptId = (int)($employee?->department_id ?? 0);

        $recipients = collect();

        $recipients = $recipients->merge($this->findBusinessHR($business)->pluck('email'));

        if ($deptId > 0) {
            $recipients = $recipients->merge(
                $this->findHODApproversForDepartment($business, $deptId)->pluck('email')
            );

            $recipients = $recipients->merge(
                $this->findChiefOfStaffApproversForDepartment($business, $deptId)->pluck('email')
            );
        }

        return $recipients
            ->filter()
            ->map(fn ($e) => strtolower(trim($e)))
            ->unique()
            ->values();
    }

    public function downloadPdf(Business $business, $reference)
    {
        $leave = LeaveRequest::with(['employee.user','leaveType','approvedBy'])
            ->where('business_id', $business->id)
            ->where('reference_number', $reference)
            ->firstOrFail();

        if (!$this->canUserViewLeaveRequest(auth()->user(), $leave)) {
            abort(403);
        }

        if ($leave->status !== 'approved') {
            abort(403, 'Only approved leaves can be exported.');
        }

        $pdf = \PDF::loadView('leave.pdf', [
            'leave'    => $leave,
            'business' => $business,
        ])->setPaper('A4', 'portrait');

        return $pdf->download("Leave-{$leave->reference_number}.pdf");
    }

    // --------------------------
    public function debugLeaveIssues($employeeId, $leaveTypeId, $startDate, $endDate)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $employee = Employee::with('user')->find($employeeId);
        $leaveType = LeaveType::find($leaveTypeId);

        $debugInfo = [
            'employee_info' => [
                'id' => $employee->id ?? 'NOT_FOUND',
                'name' => $employee->user->name ?? 'NOT_FOUND',
                'business_id' => $employee->business_id ?? 'NOT_FOUND',
                'department_id' => $employee->department_id ?? 'NULL',
                'job_category_id' => $employee->job_category_id ?? 'NULL',
                'gender' => $employee->gender ?? $employee->user->gender ?? 'NULL',
            ],
            'business_info' => [
                'id' => $business->id ?? 'NOT_FOUND',
                'slug' => $business->slug ?? 'NOT_FOUND',
            ],
            'leave_type_info' => [
                'id' => $leaveType->id ?? 'NOT_FOUND',
                'name' => $leaveType->name ?? 'NOT_FOUND',
            ],
            'date_info' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'parsed_start' => Carbon::parse($startDate)->toDateString(),
                'parsed_end' => Carbon::parse($endDate)->toDateString(),
            ]
        ];

        $existingRequests = LeaveRequest::where('employee_id', $employeeId)
            ->where('business_id', $business->id)
            ->get(['id', 'reference_number', 'start_date', 'end_date', 'approved_by', 'rejection_reason', 'current_approval_level']);

        $debugInfo['existing_requests'] = $existingRequests->toArray();

        $overlapQuery = LeaveRequest::where('employee_id', $employeeId)
            ->where('business_id', $business->id)
            ->whereNull('rejection_reason')
            ->where('start_date', '<=', Carbon::parse($endDate)->toDateString())
            ->where('end_date', '>=', Carbon::parse($startDate)->toDateString());

        $overlappingRequests = $overlapQuery->get(['id', 'reference_number', 'start_date', 'end_date', 'approved_by', 'rejection_reason']);

        $debugInfo['overlapping_requests'] = $overlappingRequests->toArray();
        $debugInfo['has_overlap'] = $overlappingRequests->count() > 0;

        $empGender = $this->normalizeGender($employee->gender ?? $employee->user->gender ?? null);

        $allPolicies = LeavePolicy::where('leave_type_id', $leaveTypeId)->get();
        $debugInfo['all_policies'] = $allPolicies->toArray();

        $policyQuery = LeavePolicy::where('leave_type_id', $leaveTypeId);

        if ($employee->department_id) {
            $policyQuery->where(function ($q) use ($employee) {
                $q->where('department_id', $employee->department_id)
                    ->orWhereNull('department_id');
            });
        } else {
            $policyQuery->whereNull('department_id');
        }

        if ($employee->job_category_id) {
            $policyQuery->where(function ($q) use ($employee) {
                $q->where('job_category_id', $employee->job_category_id)
                    ->orWhereNull('job_category_id');
            });
        } else {
            $policyQuery->whereNull('job_category_id');
        }

        $policyQuery->where(function ($q) use ($empGender) {
            $q->where('gender_applicable', 'all')
                ->orWhere('gender_applicable', $empGender)
                ->orWhereNull('gender_applicable');
        });

        $matchingPolicies = $policyQuery->get();

        $debugInfo['matching_policies'] = $matchingPolicies->toArray();
        $debugInfo['policy_exists'] = $matchingPolicies->count() > 0;
        $debugInfo['normalized_gender'] = $empGender;

        return response()->json($debugInfo, 200, [], JSON_PRETTY_PRINT);
    }

    protected function normalizeGender($gender)
    {
        if (!$gender) return 'all';

        $gender = strtolower(trim($gender));

        if (in_array($gender, ['male', 'm'])) {
            return 'male';
        } elseif (in_array($gender, ['female', 'f'])) {
            return 'female';
        }

        return 'all';
    }

    // Inside LeaveRequestController (private helper)
    protected function resolveActiveRole(): ?string
    {
        $r = session('active_role');
        if (is_string($r) && $r !== '') {
            return strtolower($r);
        }
        $u = auth()->user();
        if ($u && method_exists($u, 'getRoleNames')) {
            return strtolower((string)($u->getRoleNames()->first() ?? ''));
        }
        return null;
    }

    public function export(Request $request, Business $business)
    {
        try {
            $format  = $request->input('format', 'excel');
            $status  = $request->input('status', 'all');
            $data    = $request->input('data', []);
            $filters = $request->input('filters', []);

            if (!is_array($data)) $data = [];
            if (!is_array($filters)) $filters = [];

            $totalDays = collect($data)->sum(function ($row) {
                $val = strip_tags((string) ($row['days'] ?? 0));
                $val = preg_replace('/[^0-9.\-]/', '', $val);
                return (float) $val;
            });

            if ($format === 'excel') {
                return $this->exportToExcel($business, $data, $status, $filters, $totalDays);
            }

            if ($format === 'pdf') {
                return $this->exportToPDF($business, $data, $status, $filters, $totalDays);
            }

            return response()->json(['message' => 'Invalid format'], 400);

        } catch (\Throwable $e) {
            \Log::error('Leave export failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function exportToExcel(
        Business $business,
        array $data,
        string $status,
        array $filters,
        float $totalDays
    ) {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Leave Requests Export')
            ->setSubject('Leave Requests')
            ->setDescription('Exported leave requests data');

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', strtoupper($business->company_name ?? $business->name ?? 'LEAVE REQUESTS REPORT'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'LEAVE REQUESTS REPORT — Status: ' . ucfirst($status));
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 4;
        $sheet->setCellValue('A' . $row, 'Business: ' . ($business->company_name ?? $business->name ?? 'N/A'));
        $sheet->setCellValue('E' . $row, 'Generated: ' . now()->format('M d, Y H:i'));
        $row++;

        $sheet->setCellValue('A' . $row, 'Status: ' . ucfirst($status));
        $sheet->setCellValue('E' . $row, 'Generated By: ' . (auth()->user()->name ?? 'System'));

        if (!empty(array_filter($filters))) {
            $row += 2;
            $sheet->setCellValue('A' . $row, 'FILTERS APPLIED:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            $map = [
                'leave_type'      => 'Leave Type ID',
                'employee'        => 'Employee ID',
                'department'      => 'Department ID',
                'job_category'    => 'Job Category ID',
                'start_date'      => 'From Date',
                'end_date'        => 'To Date',
                'days_range'      => 'Days Range',
                'approval_status' => 'Approval Status',
                'documentation'   => 'Documentation',
                'tentative'       => 'Tentative',
            ];

            foreach ($map as $key => $label) {
                if (!empty($filters[$key])) {
                    $sheet->setCellValue('A' . $row, $label . ': ' . (string)$filters[$key]);
                    $row++;
                }
            }
        }

        $row += 2;
        $headerRow = $row;

        $headers = ['Ref. No.', 'Employee', 'Leave Type', 'Start Date', 'Days', 'End Date', 'Status', 'Notes'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.$row, $h);
            $col++;
        }

        $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        $row++;
        foreach ($data as $item) {
            $sheet->setCellValue('A'.$row, strip_tags((string)($item['ref'] ?? '')));
            $sheet->setCellValue('B'.$row, strip_tags((string)($item['employee'] ?? '')));
            $sheet->setCellValue('C'.$row, strip_tags((string)($item['leave_type'] ?? '')));
            $sheet->setCellValue('D'.$row, strip_tags((string)($item['start_date'] ?? '')));

            $daysVal = strip_tags((string)($item['days'] ?? '0'));
            $daysVal = preg_replace('/[^0-9.\-]/', '', $daysVal);
            $sheet->setCellValue('E'.$row, (float)$daysVal);

            $sheet->setCellValue('F'.$row, strip_tags((string)($item['end_date'] ?? '')));
            $sheet->setCellValue('G'.$row, strip_tags((string)($item['status'] ?? '')));
            $sheet->setCellValue('H'.$row, '');

            $row++;
        }

        $lastRow = $row - 1;

        $sheet->getStyle('A'.$headerRow.':H'.$lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
        ]);

        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $row += 2;
        $sheet->setCellValue('A'.$row, 'Total Records:');
        $sheet->setCellValue('B'.$row, count($data));
        $sheet->setCellValue('D'.$row, 'Total Days Requested:');
        $sheet->setCellValue('E'.$row, $totalDays);
        $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true);

        $writer   = new Xlsx($spreadsheet);
        $fileName = 'leave_requests_'.$status.'_'.time().'.xlsx';

        return response()->streamDownload(function () use ($writer) {

            while (ob_get_level() > 0) { ob_end_clean(); }
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }

    protected function exportToPDF(
        Business $business,
        array $data,
        string $status,
        array $filters,
        float $totalDays
    ) {
        $generatedAt  = now()->format('M d, Y H:i:s');
        $totalRecords = count($data);

        $pdf = Pdf::loadView('exports.leave-requests-pdf', [
            'businessName' => $business->company_name ?? $business->name ?? config('app.name'),
            'data'         => $data,
            'status'       => $status,
            'filters'      => $filters,
            'generatedAt'  => $generatedAt,
            'totalRecords' => $totalRecords,
            'totalDays'    => $totalDays,
        ])->setPaper('A4', 'landscape');

        $fileName = 'leave_requests_'.$status.'_'.time().'.pdf';

        $output = $pdf->output();

        while (ob_get_level() > 0) { ob_end_clean(); }

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }
}
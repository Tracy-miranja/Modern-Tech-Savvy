<?php namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\LeaveEntitlement;
use App\Models\LeavePolicy;
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

    /**
     * Fetch leave requests for tabs.
     * - Employees: only their own (by ACTIVE role)
     * - Others (HOD/HR/Admin/Head): all in current business
     */
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
    $emp = $user->employee;
    $activeRole = session('active_role');

    if ($activeRole === 'business-employee' && $emp) {
        $query->where('employee_id', $emp->id);
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

    // NEW: chief-of-staff → requests from assigned departments (pivot)
    if ($activeRole === 'chief-of-staff' && $emp) {
        $deptIds = $emp->assignedDepartmentIds(); // uses the helper we added on Employee
        if (empty($deptIds)) {
            $query->whereRaw('1=0');
        } else {
            $query->whereHas('employee', function ($q) use ($deptIds) {
                $q->whereIn('department_id', $deptIds);
            });
        }
    }

    if (in_array($status, ['pending', 'approved', 'rejected', 'declined'], true)) {
        $query->status($status);
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

    // Owner
    if ($active === 'business-employee') {
        return (int)optional($user->employee)->id === (int)$leave->employee_id;
    }

    // HR / Admin / Head
    if (in_array($active, ['business-hr','business-admin','business-head'], true)) {
        return (int)$user->employee?->business_id === (int)$leave->business_id
            || (int)$user->business_id === (int)$leave->business_id;
    }

    // HOD: same department
    if ($active === 'head-of-department') {
        return (int)$user->employee?->department_id === (int)optional($leave->employee)->department_id;
    }

    // Chief-of-staff: department in assigned pivot
    if ($active === 'chief-of-staff') {
        $dept = (int)optional($leave->employee)->department_id;
        return $dept > 0 && in_array($dept, $user->employee?->assignedDepartmentIds() ?? [], true);
    }

    return false;
}


    /**
     * Show one leave request.
     * - Employee: only own request
     * - Others: any request in the same business
     */
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

    /**
     * Store a new leave request (simplified policy: only business membership, dates, entitlement, docs).
     */
    public function store(Request $request)
    {
        // 1) Minimal validation to safely fetch LeaveType first
        $base = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
        ]);

        /** @var \App\Models\LeaveType $leaveType */
        $leaveType = LeaveType::findOrFail($base['leave_type_id']);

        // 2) Full validation (attachment rules are permissive; business rules enforced in code below)
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
        ]);

        return $this->handleTransaction(function () use ($validated, $leaveType, $request) {
            // --- Business context ---
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Active business not found in session.');
            }

            // --- Resolve employee (explicit or current user) ---
            $employeeId = $validated['employee_id'] ?? (auth()->user()->employee->id ?? null);
            if (!$employeeId) {
                return RequestResponse::badRequest('No employee selected for this leave request.');
            }

            /** @var \App\Models\Employee $employee */
            $employee = Employee::with('user')->findOrFail($employeeId);

            // Ensure employee belongs to this business
            if ((int)$employee->business_id !== (int)$business->id) {
                return RequestResponse::badRequest('Selected employee does not belong to the current business.');
            }

            // (Optional) If LeaveType is business-scoped, enforce it (safe even if column doesn't exist/null)
            if (property_exists($leaveType, 'business_id') && !is_null($leaveType->business_id)) {
                if ((int)$leaveType->business_id !== (int)$business->id) {
                    return RequestResponse::badRequest('This leave type is not available in the current business.');
                }
            }

            // --- Dates & guards ---
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            if ($startDate->lt(today()) && empty($leaveType->allows_backdating)) {
                return RequestResponse::badRequest('Backdating is not allowed for this leave type.');
            }

            if (!$leaveType->allows_half_day && !empty($validated['half_day'])) {
                return RequestResponse::badRequest('Half-day is not allowed for this leave type.');
            }

            // Only enforce minimum-notice if the leave type does NOT allow backdating
            if (empty($leaveType->allows_backdating) && is_numeric($leaveType->min_notice_days ?? null)) {
                $diff = now()->startOfDay()->diffInDays($startDate->copy()->startOfDay(), false);
                if ($diff < (int)$leaveType->min_notice_days) {
                    return RequestResponse::badRequest("Minimum notice is {$leaveType->min_notice_days} day(s) before the start date.");
                }
            }

            $totalDays = LeaveRequest::calculateTotalDays(
                $startDate,
                $endDate,
                (bool)($validated['half_day'] ?? false),
                $leaveType
            );

            if (!empty($leaveType->max_continuous_days) && $totalDays > (float)$leaveType->max_continuous_days) {
                return RequestResponse::badRequest("You cannot take more than {$leaveType->max_continuous_days} day(s) for this leave type at once.");
            }

            // --- OVERLAP GUARD ---
            if (LeaveRequest::hasOverlap($employeeId, $startDate, $endDate)) {
                return RequestResponse::badRequest('You already have a pending/approved leave that overlaps with these dates.');
            }
            // --- END OVERLAP GUARD ---

            // --- Attachment handling ---
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
                        $isTentative = true; // UI may show "Upload to complete"
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

            // --- Entitlement check ---
            $remaining = LeaveEntitlement::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveType->id)
                ->first()?->getRemainingDays() ?? 0;

            if ($remaining < $totalDays) {
                return RequestResponse::badRequest("You have {$remaining} remaining day(s) for this leave type, but you requested {$totalDays}.");
            }

            // --- Create request ---
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
                'attachment' => $attachmentPath,
                'requires_documentation' => $requiresDocumentation,
                'is_tentative' => $isTentative,
                'current_approval_level' => 0,
            ]);

            // --- Handle approval process based on leave type settings ---
            $this->handleLeaveApprovalProcess($leaveRequest);

            return RequestResponse::ok('Leave request created successfully.');
        });
    }

    /**
     * Handle the approval process for a newly created leave request
     */
    protected function handleLeaveApprovalProcess(LeaveRequest $leaveRequest)
    {
        $leaveType = $leaveRequest->leaveType;
        $approvalLevels = (int)($leaveType->approval_levels ?? 1);

        // Check if this leave type requires approval
        $requiresApproval = $leaveType->requires_approval ?? true;

        // If leave type doesn't require approval, auto-approve it
        if (!$requiresApproval) {
            // Only auto-approve if documentation is not required OR documentation is already provided
            if (!$leaveRequest->requires_documentation || $leaveRequest->attachment) {
                $this->autoApproveLeave($leaveRequest);
                return;
            }
            // If documentation is required but not provided, leave it pending until document is uploaded
        }

        // If approval is required, send notifications to approvers
        // Leave will remain in pending status until someone approves it
        $this->sendApplicationNotifications($leaveRequest);
    }

    /**
     * Auto-approve a leave request (for leave types that don't require approval)
     */
    protected function autoApproveLeave(LeaveRequest $leaveRequest)
    {
        try {
            // Set the system as the approver for auto-approved leaves
            $leaveRequest->approved_by = auth()->id() ?? 1; // Use current user or system user
            $leaveRequest->approved_at = now();
            $leaveRequest->is_tentative = false;
            $leaveRequest->current_approval_level = 1;

            // Add to approval history
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

            // Handle entitlement deduction
            $this->deductLeaveEntitlementSafely($leaveRequest);

            // Send auto-approval notifications
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
        $hodDeptId  = auth()->user()->employee->department_id ?? null;

        $leaveTypeQuery = LeaveType::where('slug', $slug)
            ->with(['leavePolicies']);

        // Scope the nested leaveRequests relation
        $leaveTypeQuery->with(['leaveRequests' => function ($q) use ($activeRole, $hodDeptId) {
            $q->with(['employee.user']);
            if ($activeRole === 'head-of-department') {
                if (empty($hodDeptId)) {
                    $q->whereRaw('1=0'); // HOD without department sees nothing
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


    /**
     * Approve/Reject with level checks.
     */
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


    /**
     * Enhanced processApproval with better error handling
     */
    protected function processApproval(LeaveRequest $leaveRequest, $comments = null)
    {
        try {
            $nextLevel      = $leaveRequest->getNextApprovalLevel();
            $requiredLevels = (int) (optional($leaveRequest->leaveType)->approval_levels ?? 1);

            $approverId = auth()->id();
            if (!$approverId) {
                return RequestResponse::badRequest('Invalid approver session.');
            }

            // NEW: resolve active role and guard per-role
            $activeRole = $this->resolveActiveRole(); // e.g. "business-hr"
            if (!$activeRole) {
                return RequestResponse::badRequest('Active role not found in session.');
            }

            $history = is_array($leaveRequest->approval_history ?? null) ? $leaveRequest->approval_history : [];

            // NEW: block only if SAME user + SAME role already approved before
            $alreadyApprovedSameRole = collect($history)->contains(function ($entry) use ($approverId, $activeRole) {
                $sameUser = (int)($entry['approver_id'] ?? 0) === (int)$approverId;
                $entryRole = strtolower((string)($entry['approver_role'] ?? ''));
                // If role not recorded in older entries, DO NOT block here
                return $sameUser && ($entryRole !== '' && $entryRole === $activeRole);
            });

            if ($alreadyApprovedSameRole) {
                return RequestResponse::badRequest('You have already approved this request with your current role.');
            }

            // Require docs before finalizing as you already do
            if ($nextLevel >= $requiredLevels) {
                if ($leaveRequest->requires_documentation && !$leaveRequest->attachment) {
                    return RequestResponse::badRequest('Cannot finalize approval: documentation is required.');
                }
            }

            // Record this approval step (persist the role used)
            $leaveRequest->current_approval_level = $nextLevel;
            $history[] = [
                'level'         => $nextLevel,
                'approver_id'   => $approverId,
                'approver_name' => auth()->user()->name,
                'approver_role' => $activeRole, // << store the active role from session
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

        // Notify employee safely
        try {
            $leaveRequest->employee->user->notify(new LeaveStatusNotification($leaveRequest));
        } catch (\Exception $e) {
            Log::error("Failed to send leave rejection notification for {$leaveRequest->reference_number}: " . $e->getMessage());
        }

        return RequestResponse::ok('Leave request rejected successfully.', [
            'new_status' => 'rejected',
        ]);
    }


    /**
     * Send application notifications with delays to prevent rate limiting
     * Modified to NOT auto-approve leaves that require approval
     */
    protected function sendApplicationNotifications(LeaveRequest $leaveRequest)
    {
        try {
            $business = $leaveRequest->business;
            $employee = $leaveRequest->employee;
            $leaveType = $leaveRequest->leaveType;

            // Always notify employee of submission (immediate)
            Mail::to($employee->user->email)->queue(new LeaveRequestSubmitted($leaveRequest));

            $approvalLevels = (int)($leaveType->approval_levels ?? 1);

            // If approval is required, notify approvers
            // The leave will remain in pending status until manually approved
            if ($leaveType->requires_approval && $approvalLevels > 0) {
                // Collect all recipients and send with delays
                $recipients = collect();
                $recipients = $recipients->merge($this->findHODApprovers($business)->pluck('email'));
                $recipients = $recipients->merge($this->findBusinessHR($business)->pluck('email'));

                // Send emails with 5-second delays
                foreach ($recipients->unique() as $index => $email) {
                    $delay = now()->addSeconds(($index + 1) * 5);
                    Mail::to($email)->later($delay, new LeaveRequestSubmitted($leaveRequest));
                }
            }
            // Leaves that require approval will stay pending until someone approves them

        } catch (\Exception $e) {
            Log::error("Error sending application notifications for {$leaveRequest->reference_number}: " . $e->getMessage());
        }
    }

    /**
     * Send next level notifications with delays
     */
    protected function sendNextLevelNotificationsWithDelay(LeaveRequest $leaveRequest)
    {
        try {
            $business = $leaveRequest->business;

            // Collect all recipients
            $recipients = collect();
            $recipients = $recipients->merge($this->findBusinessHR($business)->pluck('email'));
            $recipients = $recipients->merge($this->findHODApprovers($business)->pluck('email'));

            // Send with 5-second delays between each email
            foreach ($recipients->unique() as $index => $email) {
                $delay = now()->addSeconds(($index + 1) * 5);
                Mail::to($email)->later($delay, new LeaveRequestSubmitted($leaveRequest));
            }

            // Notify employee of progress (immediate)
            $leaveRequest->employee->user->notify(new LeaveStatusNotification($leaveRequest));
        } catch (\Exception $e) {
            Log::error("Error sending next level notifications for {$leaveRequest->reference_number}: " . $e->getMessage());
        }
    }

    /**
     * Send final approval notifications with delays to prevent rate limiting
     */
    protected function sendFinalApprovalNotificationsWithDelay(LeaveRequest $leaveRequest)
    {
        try {
            $business = $leaveRequest->business;

            // Employee notification (highest priority - immediate)
            $leaveRequest->employee->user->notify(new LeaveStatusNotification($leaveRequest));

            // Collect all other recipients
            $recipients = collect();
            $recipients = $recipients->merge($this->findBusinessAdmins($business)->pluck('email'));
            $recipients = $recipients->merge($this->findBusinessHeads($business)->pluck('email'));
            $recipients = $recipients->merge($this->findHODApprovers($business)->pluck('email'));
            $recipients = $recipients->merge($this->findBusinessHR($business)->pluck('email'));

            // Send emails with 10-second delays to avoid rate limiting
            foreach ($recipients->unique() as $index => $email) {
                $delay = now()->addSeconds(($index + 1) * 10);
                Mail::to($email)->later($delay, new LeaveRequestSubmitted($leaveRequest));
            }
        } catch (\Exception $e) {
            Log::error("Error sending final approval notifications for {$leaveRequest->reference_number}: " . $e->getMessage());
        }
    }

    /**
     * Enhanced finalizeApproval with better error handling
     */
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

            // Handle entitlement deduction with better error handling
            $this->deductLeaveEntitlementSafely($leaveRequest);
        } catch (\Exception $e) {
            Log::error("Error finalizing approval for leave {$leaveRequest->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Safe entitlement deduction with better error handling
     */
    protected function deductLeaveEntitlementSafely(LeaveRequest $leaveRequest): void
    {
        try {
            $entitlement = LeaveEntitlement::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->first();

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
                // Use the getRemainingDays method which recalculates and saves
                $entitlement->getRemainingDays();
                Log::info("Entitlement updated using getRemainingDays for entitlement #{$entitlement->id}.");
            }
        } catch (\Exception $e) {
            Log::error("Error deducting leave entitlement for leave {$leaveRequest->id}: " . $e->getMessage());
            // Don't throw here - entitlement issues shouldn't block approval
        }
    }

    /**
     * Replace the old finalizeApproval method with this call
     */
    protected function finalizeApproval(LeaveRequest $leaveRequest): void
    {
        $this->finalizeApprovalSafely($leaveRequest);
    }

    /**
     * Replace the old notification methods with delay versions
     */
    protected function sendNextLevelNotifications(LeaveRequest $leaveRequest)
    {
        $this->sendNextLevelNotificationsWithDelay($leaveRequest);
    }

    protected function sendFinalApprovalNotifications(LeaveRequest $leaveRequest)
    {
        $this->sendFinalApprovalNotificationsWithDelay($leaveRequest);
    }

    /* =========================
     * Other existing helpers
     * =========================
     */


    /**
     * Upload document (owner only).
     */
    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => 'required|exists:leave_requests,reference_number',
            'attachment' => 'required|file|mimes:pdf,jpg,png,doc,docx|max:2048',
        ]);

        $leaveRequest = LeaveRequest::where('reference_number', $validated['reference_number'])->firstOrFail();

        // Only owner can upload
        if (!$this->canUploadOnBehalf($leaveRequest)) {
            return RequestResponse::badRequest(
                'You are not allowed to upload documents for this leave.'
            );
        }


        // Upload file
        try {
            $path = $request->file('attachment')->store('attachments', 'public');
        } catch (\Exception $e) {
            Log::error("Failed to upload attachment for leave {$leaveRequest->id}: " . $e->getMessage());
            return RequestResponse::badRequest('Failed to upload attachment. Please try again.');
        }

        // Persist changes
        $leaveRequest->attachment = $path;
        $leaveRequest->requires_documentation = false;
        $leaveRequest->is_tentative = false;
        $leaveRequest->save();

        //  Notify approvers SAFELY
        if ($leaveRequest->status === 'pending' && $leaveRequest->needsMoreApprovals()) {
            try {
                foreach ($this->findHODApprovers($leaveRequest->business) as $hod) {
                    Mail::to($hod->email)->queue(new LeaveRequestSubmitted($leaveRequest));
                }

                foreach ($this->findBusinessHR($leaveRequest->business) as $hr) {
                    Mail::to($hr->email)->queue(new LeaveRequestSubmitted($leaveRequest));
                }
            } catch (\Exception $e) {
                Log::error(
                    "Failed to send approval notifications for leave {$leaveRequest->reference_number}: "
                    . $e->getMessage()
                );
                // DO NOT fail the request
            }
        }

        return RequestResponse::ok(
            'Document uploaded successfully. Your request will now proceed for approval.'
        );
    }

    protected function canUploadOnBehalf(LeaveRequest $leaveRequest): bool
    {
        $user = auth()->user();
        if (!$user || !$user->employee) {
            return false;
        }

        $employee = $user->employee;

        // Owner
        if ((int) $employee->id === (int) $leaveRequest->employee_id) {
            return true;
        }

        // Must belong to same business
        if ((int) $employee->business_id !== (int) $leaveRequest->business_id) {
            return false;
        }

        // Allowed roles
        return in_array($employee->role, [
            'hr',
            'head-of-department',
            'chief-of-staff',
            'business-admin',
            'business-head',
        ]);
    }
    /**
     * Revoke (shorten) an approved leave request.
     */
    public function revoke(Request $request)
    {
        $validated = $request->validate([
            'reference_number'     => 'required|exists:leave_requests,reference_number',
            'return_to_work_date'  => 'nullable|required_if:action,shorten|date',
            'reason'               => 'nullable|string|max:500',
            'action'               => 'required|in:full,shorten',
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
            if ($validated['action'] === 'full') {
                $refund = $leave->revokeFully(
                    $validated['reason'] ?? null,
                    auth()->user()
                );

                $message = "Leave fully revoked. Refunded {$refund} day(s).";

            } else {
                $refund = $leave->revokeToReturnDate(
                    Carbon::parse($validated['return_to_work_date']),
                    $validated['reason'] ?? null,
                    auth()->user()
                );

                $message = "Leave shortened successfully. Refunded {$refund} day(s).";
            }

            LeaveEntitlement::recomputeUsageFor(
                (int) $leave->employee_id,
                (int) $leave->leave_type_id,
                (int) $leave->business_id
            );

            try {
                $leave->employee->user->notify(new LeaveStatusNotification($leave));
            } catch (\Throwable $e) {
                Log::warning('Notification failed after revoke: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);

        } catch (\Throwable $e) {
            Log::error("Revoke failed for {$leave->reference_number}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
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

            // Only owner can delete, and only while pending
            if ($leaveRequest->status !== 'pending') {
                return RequestResponse::badRequest('Cannot delete approved or rejected requests.');
            }

            $authEmployeeId = auth()->user()->employee->id ?? null;
            if (!$authEmployeeId || $authEmployeeId !== (int)$leaveRequest->employee_id) {
                return RequestResponse::badRequest('You can only delete your own leave requests.');
            }

            $leaveRequest->delete();

            return RequestResponse::ok('Leave request deleted successfully.');
        });
    }

    /* =========================
     * Finders
     * =========================
     */

    /** All HODs for employee's department in this business. */
    protected function findHODApprovers(Business $business)
    {
        return User::role('head-of-department')
            ->whereHas('employee', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })->get();
    }

    /** All HR users in this business. */
    protected function findBusinessHR(Business $business)
    {
        return User::role('business-hr')
            ->whereHas('employee', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })->get();
    }

    /** All Business Heads in this business. */
    protected function findBusinessHeads(Business $business)
    {
        return User::role('business-head')
            ->whereHas('employee', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })->get();
    }

    /** All Business Admins in this business (for final-approval ping). */
    protected function findBusinessAdmins(Business $business)
    {
        return User::role('business-admin')
            ->where(function ($q) use ($business) {
                $q->whereHas('employee', function ($qq) use ($business) {
                    $qq->where('business_id', $business->id);
                })
                // if users table has a business_id column
                ->orWhere('business_id', $business->id)
                // if there's a belongsTo/hasOne relation named 'business'
                ->orWhereHas('business', function ($qb) use ($business) {
                    $qb->where('id', $business->id);
                })
                // if there's a many-to-many relation named 'businesses'
                ->orWhereHas('businesses', function ($qb) use ($business) {
                    $qb->where('businesses.id', $business->id);
                });
            })
            ->get();
    }


    public function downloadPdf(Business $business, $reference)
    {
        $leave = LeaveRequest::with(['employee.user','leaveType','approvedBy'])
            ->where('business_id', $business->id)
            ->where('reference_number', $reference)
            ->firstOrFail();

        // View permission (same as show)
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
    // Debug helper (unchanged)
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

        // Check existing leave requests for this employee
        $existingRequests = LeaveRequest::where('employee_id', $employeeId)
            ->where('business_id', $business->id)
            ->get(['id', 'reference_number', 'start_date', 'end_date', 'approved_by', 'rejection_reason', 'current_approval_level']);

        $debugInfo['existing_requests'] = $existingRequests->toArray();

        // Check for overlaps with detailed query
        $overlapQuery = LeaveRequest::where('employee_id', $employeeId)
            ->where('business_id', $business->id)
            ->whereNull('rejection_reason') // Only non-rejected
            ->where('start_date', '<=', Carbon::parse($endDate)->toDateString())
            ->where('end_date', '>=', Carbon::parse($startDate)->toDateString());

        $overlappingRequests = $overlapQuery->get(['id', 'reference_number', 'start_date', 'end_date', 'approved_by', 'rejection_reason']);

        $debugInfo['overlapping_requests'] = $overlappingRequests->toArray();
        $debugInfo['has_overlap'] = $overlappingRequests->count() > 0;

        // Check leave policies
        $empGender = $this->normalizeGender($employee->gender ?? $employee->user->gender ?? null);

        $allPolicies = LeavePolicy::where('leave_type_id', $leaveTypeId)->get();
        $debugInfo['all_policies'] = $allPolicies->toArray();

        // Check policy match step by step
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



    /**
     * Helper method to normalize gender values
     */
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
        $r = session('active_role'); // e.g. "business-hr" from your EnsureCorrectRole middleware
        if (is_string($r) && $r !== '') {
            return strtolower($r);
        }
        $u = auth()->user();
        if ($u && method_exists($u, 'getRoleNames')) {
            return strtolower((string)($u->getRoleNames()->first() ?? ''));
        }
        return null;
    }

// LeaveRequestController.php (inside class LeaveRequestController)

public function export(Request $request, Business $business)
{
    $format  = $request->input('format', 'excel');
    $status  = $request->input('status', 'all');
    $data    = $request->input('data', []);
    $filters = $request->input('filters', []);

    // Normalize "days" to numeric for totals
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

    return response()->json(['error' => 'Invalid format'], 400);
}

/**
 * NOTE: still using table data from the client (DataTables).
 * If in future you want server-side export, we’ll change this to query DB with $filters.
 */
protected function exportToExcel(
    Business $business,
    array $data,
    string $status,
    array $filters,
    float $totalDays
) {
    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator(config('app.name'))
        ->setTitle('Leave Requests Export')
        ->setSubject('Leave Requests')
        ->setDescription('Exported leave requests data');

    // Title
    $sheet->mergeCells('A1:H1');
    $sheet->setCellValue('A1', strtoupper($business->company_name ?? $business->name ?? 'LEAVE REQUESTS REPORT'));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Subtitle row
    $sheet->mergeCells('A2:H2');
    $sheet->setCellValue('A2', 'LEAVE REQUESTS REPORT — Status: ' . ucfirst($status));
    $sheet->getStyle('A2')->getFont()->setSize(11);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Metadata
    $row = 4;
    $sheet->setCellValue('A' . $row, 'Business: ' . ($business->company_name ?? $business->name ?? 'N/A'));
    $sheet->setCellValue('E' . $row, 'Generated: ' . now()->format('M d, Y H:i'));
    $row++;

    $sheet->setCellValue('A' . $row, 'Status: ' . ucfirst($status));
    $sheet->setCellValue('E' . $row, 'Generated By: ' . (auth()->user()->name ?? 'System'));

    // Filters
    if (!empty(array_filter($filters))) {
        $row += 2;
        $sheet->setCellValue('A' . $row, 'FILTERS APPLIED:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if (!empty($filters['leave_type'])) {
            $sheet->setCellValue('A' . $row, 'Leave Type ID: ' . $filters['leave_type']);
            $row++;
        }
        if (!empty($filters['employee'])) {
            $sheet->setCellValue('A' . $row, 'Employee ID: ' . $filters['employee']);
            $row++;
        }
        if (!empty($filters['department'])) {
            $sheet->setCellValue('A' . $row, 'Department ID: ' . $filters['department']);
            $row++;
        }
        if (!empty($filters['job_category'])) {
            $sheet->setCellValue('A' . $row, 'Job Category ID: ' . $filters['job_category']);
            $row++;
        }
        if (!empty($filters['start_date'])) {
            $sheet->setCellValue('A' . $row, 'From Date: ' . $filters['start_date']);
            $row++;
        }
        if (!empty($filters['end_date'])) {
            $sheet->setCellValue('A' . $row, 'To Date: ' . $filters['end_date']);
            $row++;
        }
        if (!empty($filters['days_range'])) {
            $sheet->setCellValue('A' . $row, 'Days Range: ' . $filters['days_range']);
            $row++;
        }
        if (!empty($filters['approval_status'])) {
            $sheet->setCellValue('A' . $row, 'Approval Status: ' . $filters['approval_status']);
            $row++;
        }
        if (!empty($filters['documentation'])) {
            $sheet->setCellValue('A' . $row, 'Documentation: ' . $filters['documentation']);
            $row++;
        }
        if (!empty($filters['tentative'])) {
            $sheet->setCellValue('A' . $row, 'Tentative: ' . $filters['tentative']);
            $row++;
        }
    }

    // Headers
    $row       += 2;
    $headerRow  = $row;
    $headers    = ['Ref. No.', 'Employee', 'Leave Type', 'Start Date', 'Days', 'End Date', 'Status', 'Notes'];

    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }

    // Style headers
    $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray([
        'font' => [
            'bold'  => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);

    // Data rows
    $row++;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $row, strip_tags($item['ref']        ?? ''));
        $sheet->setCellValue('B' . $row, strip_tags($item['employee']   ?? ''));
        $sheet->setCellValue('C' . $row, strip_tags($item['leave_type'] ?? ''));
        $sheet->setCellValue('D' . $row, strip_tags($item['start_date'] ?? ''));

        $daysVal = strip_tags($item['days'] ?? '');
        $daysVal = preg_replace('/[^0-9.\-]/', '', $daysVal);
        $sheet->setCellValue('E' . $row, (float) $daysVal);

        $sheet->setCellValue('F' . $row, strip_tags($item['end_date']   ?? ''));
        $sheet->setCellValue('G' . $row, strip_tags($item['status']     ?? ''));
        $sheet->setCellValue('H' . $row, '');

        $row++;
    }

    // Apply borders to data
    $lastRow = $row - 1;
    $sheet->getStyle('A' . $headerRow . ':H' . $lastRow)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => '000000'],
            ],
        ],
    ]);

    // Auto-size columns
    foreach (range('A', 'H') as $colLetter) {
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }

    // Summary row
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Total Records:');
    $sheet->setCellValue('B' . $row, count($data));
    $sheet->setCellValue('D' . $row, 'Total Days Requested:');
    $sheet->setCellValue('E' . $row, $totalDays);
    $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);

    // Stream Excel – avoids temp-file/header weirdness
    $writer   = new Xlsx($spreadsheet);
    $fileName = 'leave_requests_' . $status . '_' . time() . '.xlsx';

    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
    ]);

    $pdf->setPaper('A4', 'landscape');

    $fileName = 'leave_requests_' . $status . '_' . time() . '.pdf';

    return $pdf->download($fileName);
}

}

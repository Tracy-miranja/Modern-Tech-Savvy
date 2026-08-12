<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Employee;
use App\Models\Business;
use App\Models\LeaveType;
use App\Models\User;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'employee_id',
        'business_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'attachment',
        'requires_documentation',
        'is_tentative',
        'current_approval_level',
        'approval_history', // json
        'half_day',
        'half_day_type',
        'reason',
        'handover_notes',
        'handover_attachment',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'revocation_history',
    ];

    protected $casts = [
        'half_day'               => 'boolean',
        'start_date'             => 'date',
        'end_date'               => 'date',
        'total_days'             => 'float',
        'approved_by'            => 'integer',
        'approved_at'            => 'datetime',
        'requires_documentation' => 'boolean',
        'is_tentative'           => 'boolean',
        'current_approval_level' => 'integer',
        'approval_history'       => 'array',
        'revocation_history'     => 'array',
    ];

    /* ----------------
       Relationships
    -----------------*/
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function delegation()
    {
        return $this->hasOne(LeaveDelegation::class);
    }

    /* ----------------
       Computed status
    -----------------*/
    public function getStatusAttribute(): string
    {
        if (!is_null($this->rejection_reason)) return 'rejected';
        if (!is_null($this->approved_by))      return 'approved';
        return 'pending';
    }

    /* -----------------------------------
       Multi-level approval helper methods
    ------------------------------------*/
    public function needsMoreApprovals(): bool
    {
        $required = (int) (optional($this->leaveType)->approval_levels ?? 1);
        $current  = (int) ($this->current_approval_level ?? 0);
        return $current < $required;
    }

    public function getNextApprovalLevel(): int
    {
        return (int) ($this->current_approval_level ?? 0) + 1;
    }

    // Who can approve - driven by the leave type's configured approval
    // chain (LeaveType::approverTypeForLevel()), not whichever role a
    // user happens to be wearing.
    public function canUserApprove(User $user): bool
    {
        // Only pending can be approved
        if ($this->status !== 'pending') return false;

        $requesterEmployee = $this->employee;
        $approverEmployee = $user->activeEmployee();
        $history = is_array($this->approval_history ?? null) ? $this->approval_history : [];
        $alreadyApprovedByUser = collect($history)->contains(
            fn ($entry) => (int) ($entry['approver_id'] ?? 0) === (int) $user->id
        );

        $approverType = $this->leaveType?->approverTypeForLevel($this->getNextApprovalLevel()) ?? 'organogram';

        // 'organogram': anyone anywhere in the requester's actual reporting
        // chain (direct manager, their manager, and so on) can approve -
        // Employee.manager_id is the source of truth, not a role label.
        if ($approverType === 'organogram'
            && $requesterEmployee && $approverEmployee
            && $requesterEmployee->reportsTo($approverEmployee)
            && !$alreadyApprovedByUser) {
            return true;
        }

        // 'department_head': the approver must actually hold the
        // head-of-department role AND be in the requester's own
        // department - lets a business route a specific leave type
        // straight to the department's head without also opening it to
        // the requester's full reporting chain.
        if ($approverType === 'department_head'
            && $requesterEmployee && $approverEmployee
            && (int) $approverEmployee->department_id === (int) $requesterEmployee->department_id
            && $approverEmployee->department_id
            && $user->hasRole('head-of-department')
            && !$alreadyApprovedByUser) {
            return true;
        }

        // Beyond the configured routing, company-wide admin roles remain a
        // standing override (see below) - this is also the entire
        // enforcement mechanism for approverType === 'hr': nothing above
        // matches for a plain manager, so only an actual HR/admin/head
        // user can get past this point, which is exactly "route this
        // leave type straight to HR."

        // Resolve active role (prefer session)
        $activeRole = strtolower((string) (session('active_role') ?? ''));
        if ($activeRole === '' && method_exists($user, 'getRoleNames')) {
            $activeRole = strtolower((string) ($user->getRoleNames()->first() ?? ''));
        }
        if ($activeRole === '') return false;

        // Beyond the organogram chain, only two kinds of standing override
        // remain: company-wide admin roles (HR/admin), and chief-of-staff -
        // which unlike "head-of-department" is a genuine HR-curated
        // cross-departmental assignment (a pivot table, not just a role
        // label), so it isn't reducible to the single-parent manager_id
        // tree the way a normal reporting line is.
        $overrideRoles = ['chief-of-staff', 'business-hr', 'business-admin'];
        if (!in_array($activeRole, $overrideRoles, true)) return false;

        // Prevent duplicate approval by the SAME user under the SAME role
        $alreadyApprovedSameRole = collect($history)->contains(function ($entry) use ($user, $activeRole) {
            $sameUser  = (int) ($entry['approver_id'] ?? 0) === (int) $user->id;
            $entryRole = strtolower((string) ($entry['approver_role'] ?? ''));
            return $sameUser && ($entryRole !== '' && $entryRole === $activeRole);
        });
        if ($alreadyApprovedSameRole) return false;

        // SPECIAL CASE: business-admin can approve for the whole business
        if ($activeRole === 'business-admin') {
            $sameBusiness =
                (int) ($user->business_id ?? 0) === (int) $this->business_id
                || (method_exists($user, 'business') && (int) optional($user->business)->id === (int) $this->business_id)
                || (method_exists($user, 'businesses') && $user->businesses->pluck('id')->contains((int) $this->business_id));
            return $sameBusiness;
        }

        // For other roles, require an employee record in the SAME business
        $userEmployee = $user->activeEmployee();
        if (!$userEmployee || (int) $userEmployee->business_id !== (int) $this->business_id) {
            return false;
        }

        // Chief-of-staff must have the request's department in their assigned pivot departments
        if ($activeRole === 'chief-of-staff') {
            $reqDept = (int) optional($this->employee)->department_id;
            if ($reqDept <= 0) return false;
            $assigned = $userEmployee->assignedDepartmentIds(); // from Employee model
            return in_array($reqDept, $assigned, true);
        }

        // HR / Head pass once same-business employee is confirmed
        return true;
    }


    // Filter by ACTIVE role
public function scopeForRole($query, User $user, $businessId)
{
    $userEmployee = $user->activeEmployee();
    $activeRole   = session('active_role');

    switch ($activeRole) {
        case 'business-employee':
            if ($userEmployee) {
                // Own requests, plus (if this employee is a line manager)
                // their direct reports' requests - so a manager logged in
                // with the plain employee role can still see what's waiting
                // on them via the organogram, without needing an approver role.
                $reportIds = $userEmployee->directReports()->pluck('id');

                return $query->where('business_id', $businessId)
                             ->where(function ($q) use ($userEmployee, $reportIds) {
                                 $q->where('employee_id', $userEmployee->id);
                                 if ($reportIds->isNotEmpty()) {
                                     $q->orWhereIn('employee_id', $reportIds);
                                 }
                             });
            }
            return $query->whereRaw('1=0');

        case 'head-of-department':
            if (!$userEmployee || empty($userEmployee->department_id)) {
                return $query->whereRaw('1=0');
            }
            return $query->where('business_id', $businessId)
                        ->whereHas('employee', function ($q) use ($userEmployee) {
                            $q->where('department_id', (int)$userEmployee->department_id);
                        });

        case 'chief-of-staff':
            if (!$userEmployee) {
                return $query->whereRaw('1=0');
            }
            // Use assigned departments from pivot
            $deptIds = $userEmployee->assignedDepartmentIds();
            if (empty($deptIds)) {
                return $query->whereRaw('1=0');
            }
            return $query->where('business_id', $businessId)
                        ->whereHas('employee', function ($q) use ($deptIds) {
                            $q->whereIn('department_id', $deptIds);
                        });

        case 'business-hr':
        case 'business-admin':
            return $query->where('business_id', $businessId);

        default:
            return $query->whereRaw('1=0');
    }
}


    // Keep both for legacy code
    public function scopeStatus($query, $statusName)
    {
        switch (strtolower($statusName)) {
            case 'pending':
                return $query->whereNull('approved_by')->whereNull('rejection_reason');
            case 'approved':
                return $query->whereNotNull('approved_by')->whereNull('rejection_reason');
            case 'rejected':
            case 'declined':
                return $query->whereNotNull('rejection_reason');
            default:
                return $query;
        }
    }

    public function scopeCurrentStatus($query, $statusName)
    {
        return $this->scopeStatus($query, $statusName);
    }

    /* ----------------
       Utilities
    -----------------*/
    public static function generateUniqueReferenceNumber($businessId)
    {
        do {
            $referenceNumber = 'LR' . strtoupper(substr(uniqid('', true), -6));
        } while (
            self::where('business_id', $businessId)
                ->where('reference_number', $referenceNumber)
                ->exists()
        );

        return $referenceNumber;
    }

    public static function hasOverlap($employeeId, $startDate, $endDate)
    {
        return self::where('employee_id', $employeeId)
            // Only consider approved or pending (exclude rejected)
            ->where(function ($q) {
                $q->where(function ($q1) {
                    // Approved
                    $q1->whereNotNull('approved_by')
                    ->whereNull('rejection_reason');
                })
                ->orWhere(function ($q2) {
                    // Pending
                    $q2->whereNull('approved_by')
                    ->whereNull('rejection_reason');
                });
            })
            // Overlap condition
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();
    }

    /**
     * Inclusive days minus excluded weekdays, excluded specific dates,
     * public holidays (unless the leave type opts out), and half-day adjustment.
     */
    public static function calculateTotalDays($startDate, $endDate, $halfDay = false, $leaveType = null, ?int $businessId = null, ?int $locationId = null): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        $excluded = [];
        // ADDED: collect excluded specific dates (YYYY-MM-DD)
        $excludedDates = [];
        $nonWorkingDays = [];

        if ($leaveType instanceof LeaveType) {
            $excluded = array_map('strtolower', (array) ($leaveType->excluded_days ?? []));
            // ADDED: normalize to ISO date strings
            $excludedDates = collect((array)($leaveType->excluded_dates ?? []))
                ->filter()
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->unique()
                ->values()
                ->all();

            $excludeHolidays = $leaveType->exclude_public_holidays ?? true;
            if ($excludeHolidays && $businessId) {
                // Scoped to the requester's location when known, so a
                // Kenya-based employee's leave excludes Kenyan public
                // holidays and a Uganda-based employee's excludes Ugandan
                // ones, on top of whatever business-wide holidays apply to
                // everyone regardless of location.
                $holidayDates = Holiday::getHolidaysInRange($businessId, $start, $end, $locationId)
                    ->map(fn($h) => Carbon::parse($h->date)->toDateString())
                    ->all();
                $excludedDates = array_values(array_unique(array_merge($excludedDates, $holidayDates)));
            }

            // Business-defined non-working days (e.g. Saturday/Sunday) -
            // the day list itself is company-wide, but whether a given
            // leave type excludes them from its day count is a per-type
            // opt-in, same pattern as exclude_public_holidays above.
            $excludeNonWorkingDays = $leaveType->exclude_non_working_days ?? true;
            if ($excludeNonWorkingDays && $businessId) {
                $business = Business::find($businessId);
                $nonWorkingDays = array_map('intval', (array) ($business->non_working_days ?? []));
            }
        }

        $period = CarbonPeriod::create($start->toDateString(), $end->toDateString());

        $days = 0;
        foreach ($period as $date) {
            $weekday = strtolower($date->format('l'));
            $isoDate = $date->toDateString(); // ADDED
            $isNonWorkingDay = in_array((int) $date->dayOfWeek, $nonWorkingDays, true);

            // increment only if NOT an excluded weekday AND NOT an excluded specific date AND NOT a non-working day
            if (!in_array($weekday, $excluded, true) && !in_array($isoDate, $excludedDates, true) && !$isNonWorkingDay) {
                $days++;
            }
        }

        if ($halfDay) {
            $days -= 0.5;
        }

        return max(0, (float) $days);
    }

    public function canUserRevoke(User $user): bool
    {
        // Only approved leaves can be revoked/shortened
        if ($this->status !== 'approved') return false;

        // Active role
        $activeRole = strtolower((string)(session('active_role') ?? ''));
        if ($activeRole === '' && method_exists($user, 'getRoleNames')) {
            $activeRole = strtolower((string) ($user->getRoleNames()->first() ?? ''));
        }
        if ($activeRole === '') return false;

        $approverRoles = ['head-of-department','chief-of-staff','business-hr','business-admin'];
        if (!in_array($activeRole, $approverRoles, true)) return false;

        // Admins: anywhere within the business
        if ($activeRole === 'business-admin') {
            $sameBusiness =
                (int)($user->business_id ?? 0) === (int)$this->business_id
                || (method_exists($user, 'business')  && (int)optional($user->business)->id === (int)$this->business_id)
                || (method_exists($user, 'businesses') && $user->businesses->pluck('id')->contains((int)$this->business_id));
            return $sameBusiness;
        }

        // Others must be in same business
        $emp = $user->activeEmployee();
        if (!$emp || (int)$emp->business_id !== (int)$this->business_id) return false;

        // HoD: department must match the leave's employee department
        if ($activeRole === 'head-of-department') {
            $reqDept = (int) optional($this->employee)->department_id;
            return $reqDept > 0 && (int)$emp->department_id === $reqDept;
        }

        // Chief-of-staff: department must be in assigned pivot
        if ($activeRole === 'chief-of-staff') {
            $reqDept = (int) optional($this->employee)->department_id;
            if ($reqDept <= 0) return false;
            $assigned = $emp->assignedDepartmentIds();
            return in_array($reqDept, $assigned, true);
        }

        // HR/Head
        return true;
    }



    /**
     * Shorten an approved leave to the day before $returnToWorkDate.
     * Returns the number of days refunded (float).
     */
    public function revokeToReturnDate(Carbon $returnToWorkDate, ?string $reason, User $byUser): float
    {
        if ($this->status !== 'approved') {
            throw new \RuntimeException('Only approved leaves can be revoked.');
        }

        $oldEnd   = $this->end_date->copy()->startOfDay();
        $newEnd   = $returnToWorkDate->copy()->startOfDay()->subDay(); // last day off
        $start    = $this->start_date->copy()->startOfDay();

        if ($newEnd->lt($start)) {
            throw new \InvalidArgumentException('Return date is before the leave start.');
        }
        if ($newEnd->gte($oldEnd)) {
            throw new \InvalidArgumentException('Return date does not shorten the leave.');
        }

        // Calculate old/new totals with the same rules used everywhere -
        // business_id/location_id are required for calculateTotalDays() to
        // exclude holidays/non-working days at all, otherwise the refund
        // is computed against raw calendar days instead of actual leave days.
        $leaveType = $this->leaveType ?: LeaveType::find($this->leave_type_id);
        $employee  = $this->employee ?: Employee::find($this->employee_id);
        $oldTotal  = static::calculateTotalDays($start, $oldEnd, (bool)$this->half_day, $leaveType, $this->business_id, $employee?->location_id);
        $newTotal  = static::calculateTotalDays($start, $newEnd, (bool)$this->half_day, $leaveType, $this->business_id, $employee?->location_id);

        $refund = max(0.0, (float)$oldTotal - (float)$newTotal);

        // Refund entitlement (reverse a portion of the original deduction)
        $entitlement = \App\Models\LeaveEntitlement::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->first();

        if ($entitlement) {
            if (method_exists($entitlement, 'addBackDays')) {
                $entitlement->addBackDays($refund);
            } elseif (!is_null($entitlement->getAttribute('used_days'))) {
                $entitlement->used_days = max(0, (float)($entitlement->used_days ?? 0) - $refund);
                $entitlement->save();
            } else {
                // Fallback: call getRemainingDays() to ensure recompute path stays consistent
                $entitlement->getRemainingDays();
            }
        }

        // Persist new end date; total_days auto-recalculates in saving()
        $this->end_date = $newEnd;
        $history = is_array($this->revocation_history ?? null) ? $this->revocation_history : [];
        $history[] = [
            'revoked_at'           => now()->toDateTimeString(),
            'revoked_by'           => (int)$byUser->id,
            'revoked_by_name'      => $byUser->name,
            'return_to_work_date'  => $returnToWorkDate->toDateString(),
            'new_end_date'         => $newEnd->toDateString(),
            'refund_days'          => $refund,
            'reason'               => $reason,
        ];
        $this->revocation_history = $history;

        $this->save();

        return $refund;
    }

    /* ----------------
       Auto-calc total_days
    -----------------*/
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($leaveRequest) {
            $leaveType = $leaveRequest->leaveType
                ?: ($leaveRequest->leave_type_id ? LeaveType::find($leaveRequest->leave_type_id) : null);
            $employee = $leaveRequest->employee
                ?: ($leaveRequest->employee_id ? Employee::find($leaveRequest->employee_id) : null);

            $leaveRequest->total_days = self::calculateTotalDays(
                $leaveRequest->start_date,
                $leaveRequest->end_date,
                (bool) ($leaveRequest->half_day ?? false),
                $leaveType,
                $leaveRequest->business_id,
                $employee?->location_id
            );

            if ($leaveRequest->total_days < 0) {
                $leaveRequest->total_days = 0;
            }
        });
    }
}

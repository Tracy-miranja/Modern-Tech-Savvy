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
        'approval_history',
        'half_day',
        'half_day_type',
        'reason',
        'handover_notes',
        'handover_attachment',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'revocation_history',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
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
        'cancelled_at'           => 'datetime',
        'cancelled_by'           => 'integer',
    ];

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

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getStatusAttribute(): string
    {
        if (!is_null($this->cancelled_at))      return 'cancelled';
        if (!is_null($this->rejection_reason))  return 'rejected';
        if (!is_null($this->approved_by))       return 'approved';
        return 'pending';
    }

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

    // user happens to be wearing.
    public function canUserApprove(User $user): bool
    {

        if ($this->status !== 'pending') return false;

        $requesterEmployee = $this->employee;
        $approverEmployee = $user->activeEmployee();
        $history = is_array($this->approval_history ?? null) ? $this->approval_history : [];
        $alreadyApprovedByUser = collect($history)->contains(
            fn ($entry) => (int) ($entry['approver_id'] ?? 0) === (int) $user->id
        );

        $approverType = $this->leaveType?->approverTypeForLevel($this->getNextApprovalLevel()) ?? 'organogram';

        if ($approverType === 'organogram'
            && $requesterEmployee && $approverEmployee
            && $requesterEmployee->reportsTo($approverEmployee)
            && !$alreadyApprovedByUser) {
            return true;
        }

        if ($approverType === 'department_head'
            && $requesterEmployee && $approverEmployee
            && $requesterEmployee->department_id
            && !$alreadyApprovedByUser) {
            $ownership = app(\App\Services\OrganizationOwnershipService::class)
                ->computeDepartmentOwnership($this->business);
            $derivedHod = $ownership[$requesterEmployee->department_id]['hod'] ?? null;

            if ($derivedHod) {
                if ((int) $derivedHod['employee_id'] === (int) $approverEmployee->id) {
                    return true;
                }
            } elseif ((int) $approverEmployee->department_id === (int) $requesterEmployee->department_id
                && $user->hasRole('head-of-department')) {
                return true;
            }
        }

        if ($approverEmployee
            && (int) $approverEmployee->business_id === (int) $this->business_id
            && !$alreadyApprovedByUser
            && $user->hasPermissionTo('module.leave-management.approve')) {
            return true;
        }

        $activeRole = strtolower((string) (session('active_role') ?? ''));
        if ($activeRole === '' && method_exists($user, 'getRoleNames')) {
            $activeRole = strtolower((string) ($user->getRoleNames()->first() ?? ''));
        }
        if ($activeRole === '') return false;

        $overrideRoles = ['chief-of-staff', 'business-hr', 'business-admin'];
        if (!in_array($activeRole, $overrideRoles, true)) return false;

        $alreadyApprovedSameRole = collect($history)->contains(function ($entry) use ($user, $activeRole) {
            $sameUser  = (int) ($entry['approver_id'] ?? 0) === (int) $user->id;
            $entryRole = strtolower((string) ($entry['approver_role'] ?? ''));
            return $sameUser && ($entryRole !== '' && $entryRole === $activeRole);
        });
        if ($alreadyApprovedSameRole) return false;

        if ($activeRole === 'business-admin') {
            $sameBusiness =
                (int) ($user->business_id ?? 0) === (int) $this->business_id
                || (method_exists($user, 'business') && (int) optional($user->business)->id === (int) $this->business_id)
                || (method_exists($user, 'businesses') && $user->businesses->pluck('id')->contains((int) $this->business_id));
            return $sameBusiness;
        }

        $userEmployee = $user->activeEmployee();
        if (!$userEmployee || (int) $userEmployee->business_id !== (int) $this->business_id) {
            return false;
        }

        if ($activeRole === 'chief-of-staff') {
            $reqDept = (int) optional($this->employee)->department_id;
            if ($reqDept <= 0) return false;
            $assigned = $userEmployee->assignedDepartmentIds();
            return in_array($reqDept, $assigned, true);
        }

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
                return $query->whereNull('approved_by')->whereNull('rejection_reason')->whereNull('cancelled_at');
            case 'approved':
                return $query->whereNotNull('approved_by')->whereNull('rejection_reason')->whereNull('cancelled_at');
            case 'rejected':
            case 'declined':
                return $query->whereNotNull('rejection_reason')->whereNull('cancelled_at');
            case 'cancelled':
                return $query->whereNotNull('cancelled_at');
            default:
                return $query;
        }
    }

    public function scopeCurrentStatus($query, $statusName)
    {
        return $this->scopeStatus($query, $statusName);
    }

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

            ->where(function ($q) {
                $q->where(function ($q1) {

                    $q1->whereNotNull('approved_by')
                    ->whereNull('rejection_reason');
                })
                ->orWhere(function ($q2) {

                    $q2->whereNull('approved_by')
                    ->whereNull('rejection_reason');
                });
            })

            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();
    }

    public static function calculateTotalDays($startDate, $endDate, $halfDay = false, $leaveType = null, ?int $businessId = null, ?int $locationId = null): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        $excluded = [];

        $excludedDates = [];
        $nonWorkingDays = [];

        if ($leaveType instanceof LeaveType) {
            $excluded = array_map('strtolower', (array) ($leaveType->excluded_days ?? []));

            $excludedDates = collect((array)($leaveType->excluded_dates ?? []))
                ->filter()
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->unique()
                ->values()
                ->all();

            $excludeHolidays = $leaveType->exclude_public_holidays ?? true;
            if ($excludeHolidays && $businessId) {

                $holidayDates = Holiday::getHolidaysInRange($businessId, $start, $end, $locationId)
                    ->map(fn($h) => Carbon::parse($h->date)->toDateString())
                    ->all();
                $excludedDates = array_values(array_unique(array_merge($excludedDates, $holidayDates)));
            }

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
            $isoDate = $date->toDateString();
            $isNonWorkingDay = in_array((int) $date->dayOfWeek, $nonWorkingDays, true);

            if (!in_array($weekday, $excluded, true) && !in_array($isoDate, $excludedDates, true) && !$isNonWorkingDay) {
                $days++;
            }
        }

        if ($halfDay) {
            $days -= 0.5;
        }

        return max(0, (float) $days);
    }

    public function canUserCancel(User $user): bool
    {
        if (!in_array($this->status, ['pending', 'approved'], true)) return false;

        if ($this->start_date && $this->start_date->copy()->startOfDay()->lte(now()->startOfDay())) {
            return false;
        }

        $activeRole = strtolower((string)(session('active_role') ?? ''));
        if ($activeRole === '' && method_exists($user, 'getRoleNames')) {
            $activeRole = strtolower((string) ($user->getRoleNames()->first() ?? ''));
        }

        $emp = $user->activeEmployee();
        if ($emp && (int)$emp->id === (int)$this->employee_id) return true;

        if ($activeRole === '') return false;

        $approverRoles = ['head-of-department', 'chief-of-staff', 'business-hr', 'business-admin'];
        if (!in_array($activeRole, $approverRoles, true)) return false;

        if ($activeRole === 'business-admin') {
            $sameBusiness =
                (int)($user->business_id ?? 0) === (int)$this->business_id
                || (method_exists($user, 'business')  && (int)optional($user->business)->id === (int)$this->business_id)
                || (method_exists($user, 'businesses') && $user->businesses->pluck('id')->contains((int)$this->business_id));
            return $sameBusiness;
        }

        if (!$emp || (int)$emp->business_id !== (int)$this->business_id) return false;

        if ($activeRole === 'head-of-department') {
            $reqDept = (int) optional($this->employee)->department_id;
            return $reqDept > 0 && (int)$emp->department_id === $reqDept;
        }

        if ($activeRole === 'chief-of-staff') {
            $reqDept = (int) optional($this->employee)->department_id;
            if ($reqDept <= 0) return false;
            $assigned = $emp->assignedDepartmentIds();
            return in_array($reqDept, $assigned, true);
        }

        return true;
    }

    public function cancel(?string $reason, User $byUser): void
    {
        if (!is_null($this->cancelled_at)) {
            throw new \RuntimeException('This leave request is already cancelled.');
        }
        if (!in_array($this->status, ['pending', 'approved'], true)) {
            throw new \RuntimeException('Only pending or approved requests can be cancelled.');
        }

        $this->cancelled_at = now();
        $this->cancelled_by = $byUser->id;
        $this->cancellation_reason = $reason;
        $this->save();
    }

    public function canUserRevoke(User $user): bool
    {

        if ($this->status !== 'approved') return false;

        $activeRole = strtolower((string)(session('active_role') ?? ''));
        if ($activeRole === '' && method_exists($user, 'getRoleNames')) {
            $activeRole = strtolower((string) ($user->getRoleNames()->first() ?? ''));
        }
        if ($activeRole === '') return false;

        $approverRoles = ['head-of-department','chief-of-staff','business-hr','business-admin'];
        if (!in_array($activeRole, $approverRoles, true)) return false;

        if ($activeRole === 'business-admin') {
            $sameBusiness =
                (int)($user->business_id ?? 0) === (int)$this->business_id
                || (method_exists($user, 'business')  && (int)optional($user->business)->id === (int)$this->business_id)
                || (method_exists($user, 'businesses') && $user->businesses->pluck('id')->contains((int)$this->business_id));
            return $sameBusiness;
        }

        $emp = $user->activeEmployee();
        if (!$emp || (int)$emp->business_id !== (int)$this->business_id) return false;

        if ($activeRole === 'head-of-department') {
            $reqDept = (int) optional($this->employee)->department_id;
            return $reqDept > 0 && (int)$emp->department_id === $reqDept;
        }

        if ($activeRole === 'chief-of-staff') {
            $reqDept = (int) optional($this->employee)->department_id;
            if ($reqDept <= 0) return false;
            $assigned = $emp->assignedDepartmentIds();
            return in_array($reqDept, $assigned, true);
        }

        return true;
    }

    public function revokeToReturnDate(Carbon $returnToWorkDate, ?string $reason, User $byUser): float
    {
        if ($this->status !== 'approved') {
            throw new \RuntimeException('Only approved leaves can be revoked.');
        }

        $oldEnd   = $this->end_date->copy()->startOfDay();
        $newEnd   = $returnToWorkDate->copy()->startOfDay()->subDay();
        $start    = $this->start_date->copy()->startOfDay();

        if ($newEnd->lt($start)) {
            throw new \InvalidArgumentException('Return date is before the leave start.');
        }
        if ($newEnd->gte($oldEnd)) {
            throw new \InvalidArgumentException('Return date does not shorten the leave.');
        }

        $leaveType = $this->leaveType ?: LeaveType::find($this->leave_type_id);
        $employee  = $this->employee ?: Employee::find($this->employee_id);
        $oldTotal  = static::calculateTotalDays($start, $oldEnd, (bool)$this->half_day, $leaveType, $this->business_id, $employee?->location_id);
        $newTotal  = static::calculateTotalDays($start, $newEnd, (bool)$this->half_day, $leaveType, $this->business_id, $employee?->location_id);

        $refund = max(0.0, (float)$oldTotal - (float)$newTotal);

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

                $entitlement->getRemainingDays();
            }
        }

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

        static::saved(function (LeaveRequest $leaveRequest) {
            app(\App\Services\LeaveAttendanceSyncService::class)->handleLeaveSaved($leaveRequest);
        });
    }
}

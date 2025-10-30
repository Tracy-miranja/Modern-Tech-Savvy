<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Employee;
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
        'approved_by',
        'approved_at',
        'rejection_reason',
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

    // Who can approve (based on ACTIVE role, not just assigned roles)
// App\Models\LeaveRequest.php

public function canUserApprove(User $user): bool
{
    // Only pending can be approved
    if ($this->status !== 'pending') return false;

    // Resolve active role (prefer session)
    $activeRole = strtolower((string)(session('active_role') ?? ''));
    if ($activeRole === '' && method_exists($user, 'getRoleNames')) {
        $activeRole = strtolower((string) ($user->getRoleNames()->first() ?? ''));
    }
    if ($activeRole === '') return false;

    // Allowed approver roles
    $approverRoles = ['head-of-department', 'business-hr', 'business-admin', 'business-head'];
    if (!in_array($activeRole, $approverRoles, true)) return false;

    // Prevent duplicate approval by the SAME user under the SAME role
    $history = is_array($this->approval_history ?? null) ? $this->approval_history : [];
    $alreadyApprovedSameRole = collect($history)->contains(function ($entry) use ($user, $activeRole) {
        $sameUser  = (int)($entry['approver_id'] ?? 0) === (int)$user->id;
        $entryRole = strtolower((string)($entry['approver_role'] ?? ''));
        return $sameUser && ($entryRole !== '' && $entryRole === $activeRole);
    });
    if ($alreadyApprovedSameRole) return false;

    // SPECIAL CASE: business-admin can approve for the whole business, even without an employee row
    if ($activeRole === 'business-admin') {
        $sameBusiness =
            (int)($user->business_id ?? 0) === (int)$this->business_id // if users.business_id exists
            || (method_exists($user, 'business')  && (int)optional($user->business)->id === (int)$this->business_id) // hasOne/belongsTo
            || (method_exists($user, 'businesses') && $user->businesses->pluck('id')->contains((int)$this->business_id)); // many-to-many
        return $sameBusiness;
    }

    // For other roles, require an employee record in the SAME business
    $userEmployee = $user->employee;
    if (!$userEmployee || (int)$userEmployee->business_id !== (int)$this->business_id) {
        return false;
    }

    // Optional (tighten HOD to same department as the request’s employee)
    if ($activeRole === 'head-of-department') {
        $reqDept = (int) optional($this->employee)->department_id;
        return $reqDept > 0 && (int)$userEmployee->department_id === $reqDept;
    }

    // HR / Head pass once same-business employee is confirmed
    return true;
}


    // Filter by ACTIVE role
    public function scopeForRole($query, User $user, $businessId)
    {
        $userEmployee = $user->employee;
        $activeRole   = session('active_role');

        switch ($activeRole) {
            case 'business-employee':
                if ($userEmployee) {
                    return $query->where('business_id', $businessId)
                                ->where('employee_id', $userEmployee->id);
                }
                return $query->whereRaw('1=0');

            // HOD sees ALL requests in the business (not tied to a department)
            case 'head-of-department':
                if (!$userEmployee || empty($userEmployee->department_id)) {
                    return $query->whereRaw('1=0');
                }
                return $query->where('business_id', $businessId)
                            ->whereHas('employee', function ($q) use ($userEmployee) {
                                $q->where('department_id', (int)$userEmployee->department_id);
                            });

            case 'business-hr':
            case 'business-admin':
            case 'business-head':
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
     * Inclusive days minus excluded weekdays and half-day adjustment.
     */
    public static function calculateTotalDays($startDate, $endDate, $halfDay = false, $leaveType = null): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        $excluded = [];
        // ADDED: collect excluded specific dates (YYYY-MM-DD)
        $excludedDates = [];

        if ($leaveType instanceof LeaveType) {
            $excluded = array_map('strtolower', (array) ($leaveType->excluded_days ?? []));
            // ADDED: normalize to ISO date strings
            $excludedDates = collect((array)($leaveType->excluded_dates ?? []))
                ->filter()
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->unique()
                ->values()
                ->all();
        }

        $period = CarbonPeriod::create($start->toDateString(), $end->toDateString());

        $days = 0;
        foreach ($period as $date) {
            $weekday = strtolower($date->format('l'));
            $isoDate = $date->toDateString(); // ADDED

            // increment only if NOT an excluded weekday AND NOT an excluded specific date
            if (!in_array($weekday, $excluded, true) && !in_array($isoDate, $excludedDates, true)) {
                $days++;
            }
        }

        if ($halfDay) {
            $days -= 0.5;
        }

        return max(0, (float) $days);
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

            $leaveRequest->total_days = self::calculateTotalDays(
                $leaveRequest->start_date,
                $leaveRequest->end_date,
                (bool) ($leaveRequest->half_day ?? false),
                $leaveType
            );

            if ($leaveRequest->total_days < 0) {
                $leaveRequest->total_days = 0;
            }
        });
    }
}

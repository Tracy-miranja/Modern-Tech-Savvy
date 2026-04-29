<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\ModelStatus\HasStatuses;
use Spatie\ModelStatus\Status;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasStatuses, LogsActivity;

    protected $fillable = [
        'user_id',
        'employee_code',
        'department_id',
        'business_id',
        'location_id',
        'gender',
        'alternate_phone',
        'date_of_birth',
        'place_of_birth',
        'marital_status',
        'national_id',
        'place_of_issue',
        'tax_no',
        'nhif_no',
        'nssf_no',
        'passport_no',
        'passport_issue_date',
        'passport_expiry_date',
        'address',
        'permanent_address',
        'blood_group',
        'is_exempt_from_payroll',
        'resident_status',
        'kra_employee_status',
        'status',
         'is_overtime_eligible',
        'overtime_rate_regular',
        'overtime_rate_holiday',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_issue_date' => 'date',
        'passport_expiry_date' => 'date',
        'is_exempt_from_payroll' => 'boolean',
        'kra_employee_status' => 'string',
        'status' => Status::class,
         'is_overtime_eligible' => 'boolean',
        'overtime_rate_regular' => 'decimal:2',
        'overtime_rate_holiday' => 'decimal:2',
    ];

    // Relationships
    public function leaveEntitlements()
    {
        return $this->hasMany(LeaveEntitlement::class, 'employee_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function spouse()
    {
        return $this->hasOne(Spouse::class);
    }
    public function academicDetails()
    {
        return $this->hasMany(AcademicQualification::class);
    }
    public function previousEmployment()
    {
        return $this->hasOne(PreviousEmployment::class);
    }
    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }
    public function familyMembers()
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }
    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function employmentDetails()
    {
        return $this->hasOne(EmploymentDetail::class);
    }
    public function paymentDetails()
    {
        return $this->hasOne(EmployeePaymentDetail::class);
    }
    public function contactDetails()
    {
        return $this->hasOne(EmployeeContactDetail::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'employee_departments');
    }
    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function employeeAllowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function deductions()
    {
        return $this->belongsToMany(Deduction::class, 'employee_deductions')
            ->withPivot('amount', 'rate', 'is_active')
            ->withTimestamps();
    }

    public function employeeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function reliefs()
    {
        return $this->belongsToMany(Relief::class, 'employee_reliefs')->withPivot('amount', 'is_active', 'start_date', 'end_date')->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrolls()
    {
        return $this->hasMany(EmployeePayroll::class);
    }
    public function advances()
    {
        return $this->hasMany(Advance::class);
    }
    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'employee_task')->withTimestamps();
    }
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cv_attachments');
        $this->addMediaCollection('academic_files');
    }

    public function getImageUrl()
    {
        $media = $this->getFirstMedia('avatars');
        return $media && File::exists($media->getPath()) ? $media->getUrl() : asset('media/avatar.png');
    }

    public function scopeOnLeave($query)
    {
        return $query->whereHas('leaveRequests', function ($query) {
            $query->where('approved_at', '!=', null)
                ->whereDate('start_date', '<=', Carbon::today())
                ->whereDate('end_date', '>=', Carbon::today())
                ->currentStatus('active');
        });
    }

    public function getRemainingLeaveDaysAttribute()
    {
        $totalAllocated = $this->total_leave_days ?? 0;
        $usedLeave = $this->leaveRequests()->where('status', 'Approved')->sum('total_days');
        return max($totalAllocated - $usedLeave, 0);
    }

    public function getFullNameAttribute()
    {
        return $this->user->name ?? 'Unnamed Employee';
    }
    public function allowances()
    {
        return $this->belongsToMany(Allowance::class, 'employee_allowances')
            ->withTimestamps();
    }

    public function payrollDetail()
    {
        return $this->hasOne(EmployeePayrollDetail::class);
    }
    public function payrollSettings()
    {
        return $this->hasOne(PayrollSettings::class);
    }

    public function taskReviews()
    {
        return $this->hasMany(TaskReview::class, 'reviewer_id');

    }

public function assignedDepartmentIds(): array
{
    try {
        // Prefer cached relationship to avoid extra queries on loops
        return $this->relationLoaded('departments')
            ? $this->departments->pluck('id')->map(fn ($i) => (int)$i)->all()
            : $this->departments()->pluck('departments.id')->map(fn ($i) => (int)$i)->all();
    } catch (\Throwable $e) {
        return [];
    }
}

    public function getEmploymentDateAttribute($value)
    {
        return $value ?? $this->employmentDetail->employment_date ?? null;
    }
    public function getDepartmentIdAttribute($value)
    {
        return $value ?? $this->employmentDetail->department_id ?? null;
    }
    public function getJobCategoryIdAttribute($value)
    {
        return $value ?? $this->employmentDetail->job_category_id ?? null;
    }
   public function employmentDetail()
    {
        return $this->hasOne(\App\Models\EmploymentDetail::class);
    }


}

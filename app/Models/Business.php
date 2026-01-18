<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\SlugOptions;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\OrganogramPosition;


class Business extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasStatuses, InteractsWithMedia, LogsActivity;
    protected $table = 'businesses';

    protected $fillable = [
        'user_id',
        'company_name',
        'slug',
        'industry',
        'company_size',
        "email",
        'hr_email',
        'phone',
        'country',
        'code',
        'registration_no',
        'tax_pin_no',
        'business_license_no',
        'physical_address',
        'currency',
        'verified',
        'api_token',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('company_name')->saveSlugsTo('slug');
    }
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('businesses');
        $this->addMediaCollection('registration_certificates');
        $this->addMediaCollection('tax_pin_certificates');
        $this->addMediaCollection('business_license_certificates');
    }
  public function getImageUrl()
{
    // Option A – if you save the logo in collection 'logo' (most common)
    $media = $this->getFirstMedia('logo');

    // Option B – if you save it in collection 'businesses' (rare)
    // $media = $this->getFirstMedia('businesses');

    if ($media && File::exists($media->getPath())) {
        return $media->getUrl();
    }

    return asset('media/krstlogo.png');
}
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'business_modules')->withPivot('is_active', 'subscription_ends_at')->withTimestamps();
    }
    public function activeModules()
    {
        return $this->modules()->wherePivot('is_active', true);
    }
    public function coreModules()
    {
        return $this->modules()->where('is_core', true);
    }

    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->firstOrFail();
    }


    // business
    public function departments()
    {
        return $this->hasMany(Department::class);
    }
    public function job_categories()
    {
        return $this->hasMany(JobCategory::class);
    }
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
    public function payrollFormulas()
    {
        return $this->hasMany(PayrollFormula::class);
    }
    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
    public function reliefs()
    {
        return $this->hasMany(Relief::class);
    }

    // employees
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    public function employeesByStatus($status)
    {
        return Employee::where('business_id', $this->id)
            ->whereHas('statuses', function ($query) use ($status) {
                $query->where('name', $status)
                    ->orderByDesc('created_at')
                    ->limit(1);
            })
            ->get();
    }


    // leaves
    public function leaveTypes()
    {
        return $this->hasMany(LeaveType::class);
    }
    public function leavePeriods()
    {
        return $this->hasMany(LeavePeriod::class);
    }
    public function leaveEntitlements()
    {
        return $this->hasMany(LeaveEntitlement::class);
    }
    public function leaveRequestsByStatus($status)
    {
        return LeaveRequest::where('business_id', $this->id)
            ->currentStatus($status)
            ->get();
    }


    // Advances
    public function advancesByStatus($status)
    {
        return Advance::whereHas('employee', function ($query) {
            $query->where('business_id', $this->id);
        })
            ->currentStatus($status)
            ->get();
    }


    //managed businesses
    public function formulas()
    {
        return $this->hasMany(PayrollFormula::class, 'business_id');
    }
    public function clients()
    {
        return $this->hasMany(Client::class, 'business_id');
    }
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
    public function jobPosts()
    {
        return $this->hasMany(JobPost::class);
    }
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
    public function managedBusinesses()
    {
        return $this->belongsToMany(
            Business::class,
            'clients',
            'business_id',
            'client_business'
        );
    }
    public function managingBusinesses()
    {
        return $this->belongsToMany(
            Business::class,
            'clients',
            'client_business',
            'business_id'
        );
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }
    public function allowances()
    {
        return $this->hasMany(Allowance::class);
    }
    public function employeeDeductions()
    {
        return $this->hasManyThrough(EmployeeDeduction::class, Employee::class, 'business_id', 'employee_id', 'id', 'id');
    }

    //loans
    public function activeLoanCount()
    {
        return Loan::whereHas('employee', function ($query) {
            $query->where('business_id', $this->id);
        })->whereHas('statuses', function ($subQuery) {
            $subQuery->where('name', 'active');
        })->count();
    }

    public function totalActiveLoanAmount()
    {
        return Loan::whereHas('employee', function ($query) {
            $query->where('business_id', $this->id);
        })->whereHas('statuses', function ($subQuery) {
            $subQuery->where('name', 'active');
        })->sum('amount');
    }

    public function totalActiveRepaidAmount()
    {
        return LoanRepayment::whereHas('loan', function ($query) {
            $query->whereHas('employee', function ($subQuery) {
                $subQuery->where('business_id', $this->id);
            })->whereHas('statuses', function ($statusQuery) {
                $statusQuery->where('name', 'active');
            });
        })->sum('amount_paid');
    }

    public function remainingActiveLoanBalance()
    {
        return $this->totalActiveLoanAmount() - $this->totalActiveRepaidAmount();
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
   public function positions(): HasMany
{
    return $this->hasMany(OrganogramPosition::class, 'business_id');
}

}

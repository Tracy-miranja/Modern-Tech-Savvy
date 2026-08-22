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
        'non_working_days',
        'setup_guide_dismissed_at',
        'learning_certificate_validity_months',
        'learning_certificate_number_prefix',
        'learning_session_reminder_days',
        'learning_certificate_expiry_reminder_days',
        'project_task_due_reminder_days',
        'leave_planner_capacity_warning_percent',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'non_working_days' => 'array',
        'setup_guide_dismissed_at' => 'datetime',
        'learning_certificate_validity_months' => 'integer',
        'learning_session_reminder_days' => 'integer',
        'learning_certificate_expiry_reminder_days' => 'integer',
        'project_task_due_reminder_days' => 'integer',
        'leave_planner_capacity_warning_percent' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getSlugOptions(): SlugOptions
    {
        // Without this, Spatie's HasSlug regenerates the slug from
        // company_name on every single save() - including updates to
        // completely unrelated fields - silently changing the URL/session
        // identifier for a business that already has active links, browser
        // sessions, and route bindings pointing at its current slug.
        return SlugOptions::create()
            ->generateSlugsFrom('company_name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
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
        $media = $this->getFirstMedia('businesses');
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

    /**
     * True if this business has $slug active AND not expired, with a
     * grandfather clause: a business that has never gone through module
     * selection at all (no rows in business_modules - true for every
     * existing business today, since ModulesSeeder only recently started
     * being run and nothing auto-attaches modules retroactively) keeps
     * full access. Gating only actually restricts a business that HAS a
     * real selected set of modules that doesn't include this one, or whose
     * subscription_ends_at has passed - so turning this on can never
     * silently lock out a business that simply never touched the
     * subscription flow. subscription_ends_at is set/extended by recording
     * a ClientPayment (see ClientPaymentController::store()) - null means
     * no expiry has ever been set (e.g. the free/core module), never gated.
     */
    /**
     * Request-scoped memoization cache for findBySlug() - called dozens
     * of times per page (middleware, the wildcard view composer in
     * AppServiceProvider, the navbar partial, the controller, the page
     * view all independently re-look up the same business). A static
     * array is safely request-scoped here since this app has no
     * persistent-worker runtime (no Octane) - a fresh PHP process per
     * request means it always starts empty.
     *
     * hasModule() is deliberately NOT memoized the same way - module
     * pivots genuinely do get mutated and then re-checked within a
     * single request (ClientController::assignModules(),
     * ClientPaymentController::store() extending subscription_ends_at,
     * and several tests all attach/update a pivot then immediately call
     * hasModule() again expecting the fresh state) - caching it returned
     * stale answers and broke real behavior, not just tests.
     */
    protected static array $slugLookupCache = [];

    public function hasModule(string $slug): bool
    {
        if (!$this->modules()->exists()) {
            return true;
        }

        return $this->activeModules()
            ->where('slug', $slug)
            ->where(function ($query) {
                $query->whereNull('business_modules.subscription_ends_at')
                    ->orWhereDate('business_modules.subscription_ends_at', '>=', now()->toDateString());
            })
            ->exists();
    }

    public static function findBySlug($slug)
    {
        if (array_key_exists($slug, static::$slugLookupCache)) {
            return static::$slugLookupCache[$slug];
        }

        return static::$slugLookupCache[$slug] = static::where('slug', $slug)->firstOrFail();
    }

    /**
     * PHPUnit runs the whole suite in one PHP process (unlike a real
     * request, which always starts these caches empty) - without this,
     * a business object cached by one test's since-rolled-back
     * transaction would leak into the next test as stale data. Called
     * from Tests\TestCase::setUp(), before each test's own DB
     * transaction begins.
     */
    public static function clearRequestCaches(): void
    {
        static::$slugLookupCache = [];
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
}

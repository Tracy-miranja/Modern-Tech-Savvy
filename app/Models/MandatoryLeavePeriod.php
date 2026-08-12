<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class MandatoryLeavePeriod extends Model
{
    use HasFactory, HasSlug;

    public const SCOPE_ORGANIZATION = 'organization';
    public const SCOPE_DEPARTMENT = 'department';
    public const SCOPE_LOCATION = 'location';
    public const SCOPE_TYPES = [self::SCOPE_ORGANIZATION, self::SCOPE_DEPARTMENT, self::SCOPE_LOCATION];

    protected $fillable = [
        'business_id',
        'leave_type_id',
        'name',
        'slug',
        'start_date',
        'end_date',
        'scope_type',
        'scope_ids',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'scope_ids' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(MandatoryLeavePeriodDeduction::class);
    }

    /**
     * Employees this period applies to, based on scope_type/scope_ids.
     * No status filtering beyond business_id, matching how the rest of the
     * leave module (e.g. the calendar's department filter) resolves
     * affected employees.
     */
    public function resolveAffectedEmployees(): Builder
    {
        $query = Employee::where('business_id', $this->business_id);

        if ($this->scope_type === self::SCOPE_DEPARTMENT) {
            $query->whereIn('department_id', (array) ($this->scope_ids ?? []));
        } elseif ($this->scope_type === self::SCOPE_LOCATION) {
            $query->whereIn('location_id', (array) ($this->scope_ids ?? []));
        }

        return $query;
    }
}

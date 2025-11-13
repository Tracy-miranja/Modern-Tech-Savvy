<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'description',
        'requires_approval',
        'is_paid',
        'allowance_accruable',
        'allows_half_day',
        'requires_attachment',
        'max_continuous_days',
        'min_notice_days',
        'is_active',
        'allows_backdating',
        'approval_levels',
        'excluded_days',
        'is_stepwise',
        'excluded_dates',
        'stepwise_rules',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_paid' => 'boolean',
        'allowance_accruable' => 'boolean',
        'allows_half_day' => 'boolean',
        'requires_attachment' => 'boolean',
        'max_continuous_days' => 'integer',
        'min_notice_days' => 'integer',
        'is_active' => 'boolean',
        'allows_backdating' => 'boolean',
        'approval_levels' => 'integer',
        'excluded_days' => 'array',
        'excluded_dates' => 'array',
        'is_stepwise' => 'boolean',
        'stepwise_rules' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    /**
     * Relationship to the Business model.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relationship to the LeavePolicy model.
     */
    public function leavePolicies()
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(\App\Models\LeaveRequest::class, 'leave_type_id');
    }

}

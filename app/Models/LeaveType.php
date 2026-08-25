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

    public const APPROVER_TYPES = ['organogram', 'hr', 'department_head'];

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
        'approval_chain',
        'excluded_days',
        'is_stepwise',
        'excluded_dates',
        'stepwise_rules',
        'exclude_public_holidays',
        'exclude_non_working_days',
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
        'approval_chain' => 'array',
        'excluded_days' => 'array',
        'excluded_dates' => 'array',
        'is_stepwise' => 'boolean',
        'stepwise_rules' => 'array',
        'exclude_public_holidays' => 'boolean',
        'exclude_non_working_days' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate();
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function leavePolicies()
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(\App\Models\LeaveRequest::class, 'leave_type_id');
    }

    public function approverTypeForLevel(int $level): string
    {
        $chain = (array) ($this->approval_chain ?? []);
        $type = $chain[$level - 1] ?? 'organogram';

        return in_array($type, self::APPROVER_TYPES, true) ? $type : 'organogram';
    }
}

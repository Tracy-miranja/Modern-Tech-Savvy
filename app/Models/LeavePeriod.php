<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeavePeriod extends Model
{
    use HasFactory, HasStatuses, HasSlug, LogsActivity;
    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'start_date',
        'end_date',
        'is_active',
        'accept_applications',
        'can_accrue',
        'restrict_applications_within_dates',
        'archive',
        'autocreate',
        'period_status',
        'closed_at',
        'closed_by',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'accept_applications' => 'boolean',
        'can_accrue' => 'boolean',
        'restrict_applications_within_dates' => 'boolean',
        'archive' => 'boolean',
        'autocreate' => 'boolean',
        'closed_at' => 'datetime',
    ];
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate();
    }
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->period_status === 'closed';
    }

    public function nextPeriod(): ?self
    {
        return static::where('business_id', $this->business_id)
            ->where('start_date', '>=', $this->end_date)
            ->orderBy('start_date')
            ->first();
    }

    public static function currentlyOpenFor(int $businessId): ?self
    {
        $today = now()->toDateString();

        return static::where('business_id', $businessId)
            ->where('period_status', 'open')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first()
            ?? static::where('business_id', $businessId)
                ->where('period_status', 'open')
                ->where('is_active', true)
                ->orderByDesc('start_date')
                ->first();
    }

}

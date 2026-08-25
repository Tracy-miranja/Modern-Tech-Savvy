<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\ModelStatus\HasStatuses;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Task extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasStatuses, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'business_id',
        'title',
        'slug',
        'description',
        'status',
        'priority',
        'links',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'links' => 'array',
    ];

    // }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_task')->withTimestamps();
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function reviews()
    {
        return $this->hasMany(TaskReview::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs(false)
            ->slugsShouldBeNoLongerThan(255);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }
}

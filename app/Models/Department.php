<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory, HasSlug, HasStatuses, LogsActivity;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'description',
    ];
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate();
    }
    public function employees()
{
    return $this->belongsToMany(Employee::class, 'employee_departments');
}
}

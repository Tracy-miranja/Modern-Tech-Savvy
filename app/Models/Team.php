<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

/**
 * An optional grouping inside a department (e.g. a squad with its own
 * line manager) - purely additive; departments with no teams behave
 * exactly as they did before this existed.
 */
class Team extends Model
{
    use HasSlug;

    protected $fillable = [
        'business_id',
        'department_id',
        'name',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function positions()
    {
        return $this->belongsToMany(OrganogramPosition::class, 'organogram_position_team', 'team_id', 'organogram_position_id');
    }
}

<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Allowance extends Model
{
    use LogsActivity, HasStatuses, HasSlug;

    protected $fillable = [
        'business_id',
        'location_id',
        'calculation_basis',
        'applies_to',
        'type',
        'name',
        'slug',
        'is_taxable',
        'is_nssf_applicable',
        'is_sdl_applicable',
        'amount',
        'rate',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_nssf_applicable' => 'boolean',
            'is_sdl_applicable' => 'boolean',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_allowances')
            ->withTimestamps();
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate();
    }
}

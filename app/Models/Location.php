<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory, HasSlug, HasStatuses, LogsActivity;

    protected $fillable = [
        'business_id',
        'name',
        'country',
        'slug',
        'company_size',
        'registration_no',
        'tax_pin_no',
        'business_license_no',
        'physical_address',
    ];
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function resolvedCountry(): ?string
    {
        return $this->country ?: optional($this->business)->country;
    }

    public function resolvedJurisdiction(): ?string
    {
        return $this->jurisdiction ?: null;
    }
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
    public function employeeDeductions()
    {
        return $this->hasManyThrough(EmployeeDeduction::class, Employee::class, 'location_id', 'employee_id', 'id', 'id');
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->doNotGenerateSlugsOnUpdate();
    }
}

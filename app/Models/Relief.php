<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Relief extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'description',
        'computation_method',
        'amount',
        'percentage_of_amount',
        'percentage_of',
        'limit',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage_of_amount' => 'decimal:2',
        'limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_reliefs')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function calculate(float $baseAmount, ?float $employeeSpecificAmount = null): float
    {
        $relief = $employeeSpecificAmount ?? match ($this->computation_method) {
            'fixed' => $this->amount ?? 0,
            'percentage' => ($this->percentage_of_amount && $this->percentage_of)
                ? ($baseAmount * ($this->percentage_of_amount / 100))
                : 0,
            default => 0,
        };

        return $this->limit ? min($relief, $this->limit) : $relief;
    }

    public function getEmployeeRelief(Employee $employee, float $baseAmount): float
    {
        $pivot = $this->employees()->where('employee_id', $employee->id)->first();
        $employeeAmount = $pivot ? $pivot->pivot->amount : null;

        return $this->calculate($baseAmount, $employeeAmount);
    }
}
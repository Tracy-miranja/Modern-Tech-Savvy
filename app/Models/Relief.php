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
        'computation_method',    // Fixed or percentage
        'amount',                // Fixed amount
        'percentage_of_amount',  // Percentage value
        'percentage_of',         // Basis for percentage (e.g., total_salary)
        'limit',                 // Maximum relief cap
        'is_active',             // Active status
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

    /**
     * Calculate the relief amount based on the computation method.
     *
     * @param float $baseAmount The amount to compute relief against (e.g., total_salary)
     * @param float|null $employeeSpecificAmount Optional employee-specific amount
     * @return float The calculated relief
     */
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

    /**
     * Get relief amount for a specific employee.
     *
     * @param Employee $employee
     * @param float $baseAmount
     * @return float
     */
    public function getEmployeeRelief(Employee $employee, float $baseAmount): float
    {
        $pivot = $this->employees()->where('employee_id', $employee->id)->first();
        $employeeAmount = $pivot ? $pivot->pivot->amount : null;

        return $this->calculate($baseAmount, $employeeAmount);
    }
}
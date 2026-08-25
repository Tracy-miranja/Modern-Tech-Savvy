<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deduction extends Model
{
    use HasFactory, HasStatuses, HasSlug, LogsActivity;

    protected $fillable = [
        'business_id',
        'location_id',
        'name',
        'slug',
        'description',
        'calculation_basis',
        'computation_method',
        'amount',
        'rate',
         'employer_rate',
        'formula',
        'actual_amount',
        'fraction_to_consider',
        'limit',
          'employer_limit',
        'round_off',
        'decimal_places',
        'is_statutory',
        'is_optional',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'employer_rate' => 'decimal:4',
        'limit' => 'decimal:2',
        'employer_limit' => 'decimal:2',
        'decimal_places' => 'integer',
        'actual_amount' => 'boolean',
        'is_statutory' => 'boolean',
        'is_optional' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employeeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_deductions')
            ->withPivot('amount', 'is_active')
            ->withTimestamps();
    }

    public function resolvedEmployerRate(): float
    {
        if ($this->employer_rate !== null && floatval($this->employer_rate) > 0) {
            return floatval($this->employer_rate);
        }
        return floatval($this->rate ?? 0);
    }

    public function resolvedEmployerLimit(): float
    {
        if ($this->employer_limit !== null) {
            return floatval($this->employer_limit);
        }
        return $this->limit !== null ? floatval($this->limit) : PHP_FLOAT_MAX;
    }

    public function hasEmployerContribution(): bool
    {
        return in_array($this->fraction_to_consider, ['employee_and_employer', 'employer_only']);
    }

    public function calculate(float $baseAmount, ?float $employeeSpecificAmount = null): float
    {
        $deduction = 0;

        switch ($this->computation_method) {
            case 'fixed':
                $deduction = $this->amount ?? 0;
                break;

            case 'rate':
                $deduction = ($baseAmount * ($this->rate / 100)) ?? 0;
                break;

            case 'formula':
                if ($this->formula) {

                    $deduction = eval("return {$baseAmount} * " . str_replace('base', '$baseAmount', $this->formula) . ";");
                }
                break;
        }

        if ($this->actual_amount && $employeeSpecificAmount !== null) {
            $deduction = $employeeSpecificAmount;
        }

        if ($this->limit !== null) {
            $deduction = min($deduction, $this->limit);
        }

        if ($this->round_off === 'round_off_up') {
            $deduction = ceil($deduction * pow(10, $this->decimal_places)) / pow(10, $this->decimal_places);
        } elseif ($this->round_off === 'round_off_down') {
            $deduction = floor($deduction * pow(10, $this->decimal_places)) / pow(10, $this->decimal_places);
        }

        return $deduction;
    }

    public function calculateRawEmployerAmount(float $baseAmount): float
    {
        if (!$this->hasEmployerContribution()) {
            return 0.0;
        }

        $employerRate = $this->resolvedEmployerRate();

        if ($employerRate > 0) {
            return round($baseAmount * ($employerRate / 100), 2);
        }

        return floatval($this->amount ?? 0);
    }

    public function getEmployeeDeduction(Employee $employee, float $baseAmount): float
    {
        $pivot = $this->employees()->where('employee_id', $employee->id)->first();
        $employeeAmount = $pivot ? $pivot->pivot->amount : null;

        return $this->calculate($baseAmount, $employeeAmount);
    }
}

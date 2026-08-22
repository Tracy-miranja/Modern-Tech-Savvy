<?php

namespace App\Models;

use Exception;
use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollFormula extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = [
        'business_id',
        'country',
        'name',
        'slug',
        'description',
        'formula_type',
        'calculation_basis',
        'is_statutory',
        'is_progressive',
        'minimum_amount',
        'limit',
        'round_off',
        'applies_to',
        'expression'
    ];

    protected $casts = [
        'is_progressive' => 'boolean',
        'is_statutory' => 'boolean',
        'minimum_amount' => 'decimal:2',
        'limit' => 'decimal:2',
    ];

    public function brackets()
    {
        return $this->hasMany(PayrollFormulaBracket::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function calculations()
    {
        return $this->hasMany(PayrollFormulaCalculation::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public static function calculateForEmployee($employeeId, $payrollId, $basisAmount, $formulaSlug, $businessId = null)
    {
        $formula = self::where('slug', $formulaSlug)
            ->where(function ($query) use ($businessId) {
                $query->where('business_id', $businessId)->orWhereNull('business_id');
            })
            ->with('brackets')
            ->firstOrFail();

        $result = $formula->calculate($basisAmount);
        $calculation = $formula->recordCalculation($employeeId, $payrollId, $basisAmount, $result);

        return [
            'result' => $result,
            'calculation_id' => $calculation->id,
        ];
    }

    public function calculate($amount)
    {
        $steps = [];
        $result = 0;

        switch ($this->formula_type) {
            case 'rate':
                $rate = floatval($this->minimum_amount ?? 0) / 100;
                $result = $amount * $rate;
                $steps = ["{$amount} * {$this->minimum_amount}% = {$result}"];
                break;

            case 'fixed':
            case 'amount':
                $result = floatval($this->minimum_amount ?? 0);
                $steps = ["Fixed: {$result}"];
                break;

            case 'progressive':
                $brackets = $this->brackets->sortBy('min');
                $result = $this->calculateProgressive($amount, $brackets);
                $steps = $this->progressiveSteps($amount, $brackets);
                break;

            case 'expression':
                $result = $this->evaluateExpression($amount);
                $steps = ["Expression: {$this->expression} = {$result}"];
                break;

            default:
                $result = 0;
                $steps = ['No calculation defined'];
                Log::warning("Unknown formula type: {$this->formula_type}", ['slug' => $this->slug]);
        }

        if ($this->limit !== null) {
            $originalResult = $result;
            $result = min($result, floatval($this->limit));
            if ($result !== $originalResult) {
                $steps[] = "Capped at {$this->limit} (from {$originalResult})";
            }
        }

        if ($this->round_off) {
            $steps[] = "Rounded ({$this->round_off})";
            $result = match ($this->round_off) {
                'round_up' => ceil($result),
                'round_down' => floor($result),
                'nearest' => round($result),
                default => $result,
            };
        }

        return $result;
    }

    private function calculateProgressive($amount, $brackets)
    {
        $tax = 0;
        $previousMax = 0;

        foreach ($brackets as $bracket) {
            $min = floatval($bracket->min ?? 0);
            $max = floatval($bracket->max ?? PHP_FLOAT_MAX);
            $rate = floatval($bracket->rate ?? 0) / 100;
            $fixedAmount = floatval($bracket->amount ?? 0);

            if ($amount <= $previousMax) {
                break;
            }

            if ($amount > $min) {
                if ($fixedAmount > 0) {
                    $tax += $fixedAmount;
                } elseif ($rate > 0) {
                    $taxableInBracket = min($amount, $max) - max($min, $previousMax);
                    if ($taxableInBracket > 0) {
                        $tax += $taxableInBracket * $rate;
                    }
                }
            }
            $previousMax = $max;
        }

        return $tax;
    }

    private function progressiveSteps($amount, $brackets)
    {
        $steps = [];
        $runningTotal = 0;
        $previousMax = 0;

        foreach ($brackets as $bracket) {
            $min = floatval($bracket->min ?? 0);
            $max = floatval($bracket->max ?? PHP_FLOAT_MAX);
            $rate = floatval($bracket->rate ?? 0) / 100;
            $fixedAmount = floatval($bracket->amount ?? 0);

            if ($amount <= $previousMax) {
                break;
            }

            if ($amount > $min) {
                if ($fixedAmount > 0) {
                    $runningTotal += $fixedAmount;
                    $steps[] = "Bracket {$min}-{$max}: Fixed amount = {$fixedAmount} (Running total: {$runningTotal})";
                } elseif ($rate > 0) {
                    $taxableInBracket = min($amount, $max) - max($min, $previousMax);
                    if ($taxableInBracket > 0) {
                        $stepAmount = $taxableInBracket * $rate;
                        $runningTotal += $stepAmount;
                        $steps[] = "Bracket {$min}-{$max}: {$taxableInBracket} * {$bracket->rate}% = {$stepAmount} (Running total: {$runningTotal})";
                    }
                }
            }
            $previousMax = $max;
        }

        return $steps;
    }

    private function evaluateExpression($amount)
    {
        if (empty($this->expression)) {
            Log::warning("No expression defined for formula", ['slug' => $this->slug]);
            return 0;
        }

        $expression = str_replace(['gross_pay', 'basic_pay'], [$amount, $amount], $this->expression);
        Log::warning("Expression evaluation is unsafe and limited. Consider using a math parser.", [
            'slug' => $this->slug,
            'expression' => $expression,
        ]);

        try {
            return eval("return {$expression};");
        } catch (\Exception $e) {
            Log::error("Failed to evaluate expression: {$this->expression}", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    public function recordCalculation($employeeId, $payrollId, $inputAmount, $result)
    {
        $steps = $this->formula_type === 'progressive'
            ? $this->progressiveSteps($inputAmount, $this->brackets)
            : [$this->formula_type . ": {$result}"];
        $affectedFields = $this->determineAffectedFields($result);

        return PayrollFormulaCalculation::create([
            'payroll_id' => $payrollId,
            'employee_id' => $employeeId,
            'payroll_formula_id' => $this->id,
            'input_amount' => $inputAmount,
            'result' => $result,
            'calculation_steps' => json_encode($steps),
            'affected_fields' => json_encode($affectedFields),
        ]);
    }

    private function determineAffectedFields($result)
    {
        return match ($this->calculation_basis) {
            'taxable_pay' => ['taxable_pay' => -$result],
            'gross_pay' => ['gross_pay' => -$result],
            'net_pay' => ['net_pay' => -$result],
            default => [],
        };
    }

    public static function getFixedAmount(string $slug)
    {
        $formula = self::where('slug', $slug)->firstOrFail();
        if (!in_array($formula->formula_type, ['fixed', 'amount'])) {
            throw new Exception("Formula '{$slug}' is not fixed or amount-based.");
        }
        return floatval($formula->minimum_amount);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;
use App\Models\Application;
use App\Models\EmployeePayroll;
use App\Models\Overtime;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\Advance;
use App\Models\Loan;
use App\Models\JobPost;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\JobCategory;
use Illuminate\Support\Facades\DB;

class Kpi extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'business_id',
        'location_id',
        'employee_id',
        'department_id',
        'job_category_id',
        'model_type',
        'description',
        'calculation_method',
        'target_value',
        'comparison_operator',
    ];

    public function results()
    {
        return $this->hasMany(KpiResult::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function calculate($modelInstance)
    {
        if ($this->model_type === 'manual') {
            return null;
        }

        $method = ucfirst($this->calculation_method);
        if (!method_exists($this, "calculate{$method}")) {
            return null;
        }

        $value = $this->{"calculate{$method}"}($modelInstance);

        $result = new KpiResult([
            'kpi_id' => $this->id,
            'model_type' => $this->model_type,
            'model_id' => $modelInstance->id,
            'result_value' => $value,
            'meets_target' => $this->evaluateTarget($value),
            'measured_at' => now()->toDateString(),
        ]);
        $result->save();

        return $result;
    }

    protected function evaluateTarget($value)
    {
        $target = (float) $this->target_value;
        switch ($this->comparison_operator) {
            case '>=':
                return $value >= $target;
            case '<=':
                return $value <= $target;
            case '=':
                return abs($value - $target) < 0.01;
            default:
                return false;
        }
    }

    protected function calculatePercentage($modelInstance)
    {
        if ($this->model_type === 'App\Models\Attendance') {
            $month = $modelInstance->date->startOfMonth();
            $totalDays = $month->daysInMonth;

            $query = Attendance::whereBetween('date', [$month, $month->endOfMonth()])
                ->where('is_absent', 0);

            if ($this->employee_id) {
                $query->where('employee_id', $this->employee_id);
            } elseif ($this->department_id) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $this->department_id));
            } elseif ($this->job_category_id) {
                $query->whereHas('employee', fn($q) => $q->whereHas('employmentDetails', fn($eq) => $eq->where('job_category_id', $this->job_category_id)));
            } elseif ($this->location_id) {
                $query->where('location_id', $this->location_id);
            } elseif ($this->business_id) {
                $query->where('business_id', $this->business_id);
            }

            $presentDays = $query->count();
            return $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
        }
        return 0;
    }

    protected function calculateCount($modelInstance)
    {
        if ($this->model_type === 'App\Models\Application') {
            $query = Application::where('job_post_id', $modelInstance->job_post_id);
            if ($this->business_id) {
                $query->where('business_id', $this->business_id);
            } elseif ($this->location_id) {
                $query->where('location_id', $this->location_id);
            }
            return $query->count();
        } elseif ($this->model_type === 'App\Models\Task') {
            $query = Task::where('due_date', '<=', now())
                ->whereHas('statuses', fn($q) => $q->where('name', 'completed'));
            if ($this->employee_id) {
                $query->where('employee_id', $this->employee_id);
            } elseif ($this->department_id) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $this->department_id));
            } elseif ($this->job_category_id) {
                $query->whereHas('employee', fn($q) => $q->whereHas('employmentDetails', fn($eq) => $eq->where('job_category_id', $this->job_category_id)));
            } elseif ($this->business_id) {
                $query->where('business_id', $this->business_id);
            }
            return $query->count();
        }
        return 0;
    }

    protected function calculateAverage($modelInstance)
    {
        if ($this->model_type === 'App\Models\EmployeePayroll') {
            $query = EmployeePayroll::where('payroll_id', $modelInstance->payroll_id);
            if ($this->employee_id) {
                $query->where('employee_id', $this->employee_id);
            } elseif ($this->department_id) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $this->department_id));
            } elseif ($this->job_category_id) {
                $query->whereHas('employee', fn($q) => $q->whereHas('employmentDetails', fn($eq) => $eq->where('job_category_id', $this->job_category_id)));
            } elseif ($this->business_id) {
                $query->whereHas('payroll', fn($q) => $q->where('business_id', $this->business_id));
            }
            return $query->avg('net_pay') ?? 0;
        } elseif ($this->model_type === 'App\Models\Advance') {
            $query = Advance::query();
            if ($this->employee_id) {
                $query->where('employee_id', $this->employee_id);
            } elseif ($this->department_id) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $this->department_id));
            } elseif ($this->job_category_id) {
                $query->whereHas('employee', fn($q) => $q->whereHas('employmentDetails', fn($eq) => $eq->where('job_category_id', $this->job_category_id)));
            } elseif ($this->business_id) {
                $query->where('business_id', $this->business_id);
            }
            return $query->avg('amount') ?? 0;
        } elseif ($this->model_type === 'App\Models\Loan') {
            $query = Loan::query();
            if ($this->employee_id) {
                $query->where('employee_id', $this->employee_id);
            } elseif ($this->department_id) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $this->department_id));
            } elseif ($this->job_category_id) {
                $query->whereHas('employee', fn($q) => $q->whereHas('employmentDetails', fn($eq) => $eq->where('job_category_id', $this->job_category_id)));
            } elseif ($this->business_id) {
                $query->where('business_id', $this->business_id);
            }
            return $query->avg('amount') ?? 0;
        }
        return 0;
    }

    protected function calculateSum($modelInstance)
    {
        if ($this->model_type === 'App\Models\Overtime') {
            $month = $modelInstance->date->startOfMonth();
            $query = Overtime::whereBetween('date', [$month, $month->endOfMonth()]);
            if ($this->employee_id) {
                $query->where('employee_id', $this->employee_id);
            } elseif ($this->department_id) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $this->department_id));
            } elseif ($this->job_category_id) {
                $query->whereHas('employee', fn($q) => $q->whereHas('employmentDetails', fn($eq) => $eq->where('job_category_id', $this->job_category_id)));
            } elseif ($this->location_id) {
                $query->where('location_id', $this->location_id);
            } elseif ($this->business_id) {
                $query->where('business_id', $this->business_id);
            }
            return $query->sum('overtime_hours') ?? 0;
        } elseif ($this->model_type === 'App\Models\LeaveRequest') {
            $query = LeaveRequest::where('start_date', '>=', now()->startOfYear());
            if ($this->employee_id) {
                $query->where('employee_id', $this->employee_id);
            } elseif ($this->department_id) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $this->department_id));
            } elseif ($this->job_category_id) {
                $query->whereHas('employee', fn($q) => $q->whereHas('employmentDetails', fn($eq) => $eq->where('job_category_id', $this->job_category_id)));
            } elseif ($this->business_id) {
                $query->where('business_id', $this->business_id);
            }
            return $query->sum('total_days') ?? 0;
        }
        return 0;
    }

    protected function calculateRatio($modelInstance)
    {
        if ($this->model_type === 'App\Models\JobPost') {
            $query = Application::where('job_post_id', $modelInstance->id);
            if ($this->business_id) {
                $query->where('business_id', $this->business_id);
            } elseif ($this->location_id) {
                $query->where('location_id', $this->location_id);
            }
            $applications = $query->count();
            $targetHires = $modelInstance->vacancies ?? 1;
            return $targetHires > 0 ? $applications / $targetHires : 0;
        }
        return 0;
    }

    public function getProgressPercentage()
    {
        if ($this->results->isEmpty()) {
            return 0;
        }

        $result = $this->results->last()->result_value;
        $target = (float) $this->target_value;

        if (!$target || !$this->comparison_operator) {
            return 0;
        }

        if ($this->comparison_operator === '>=') {
            return min(100, ($result / $target) * 100);
        } elseif ($this->comparison_operator === '<=') {

            if ($result <= $target) {
                return 100.0;
            }
            return max(0.0, 100 - (($result - $target) / $target) * 100);
        } elseif ($this->comparison_operator === '=') {
            return abs($result - $target) < 0.01 ? 100 : min(100, ($result / $target) * 100);
        }
        return 0;
    }
}
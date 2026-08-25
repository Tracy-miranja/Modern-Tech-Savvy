<?php

namespace Database\Seeders;

use App\Models\Kpi;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    public function run()
    {

        Kpi::create([
            'name' => 'Employee Attendance Rate',
            'slug' => 'employee-attendance-rate',
            'model_type' => 'App\Models\Attendance',
            'description' => 'Percentage of days an employee was present in a month.',
            'calculation_method' => 'percentage',
            'target_value' => '90',
            'comparison_operator' => '>=',
        ]);

        Kpi::create([
            'name' => 'Job Application Count',
            'slug' => 'job-application-count',
            'model_type' => 'App\Models\Application',
            'description' => 'Number of applications per job post.',
            'calculation_method' => 'count',
            'target_value' => '5',
            'comparison_operator' => '>=',
        ]);

        Kpi::create([
            'name' => 'Average Net Pay',
            'slug' => 'average-net-pay',
            'model_type' => 'App\Models\EmployeePayroll',
            'description' => 'Average net pay per payroll run.',
            'calculation_method' => 'average',
            'target_value' => '50000',
            'comparison_operator' => '>=',
        ]);

        Kpi::create([
            'name' => 'Overtime Utilization',
            'slug' => 'overtime-utilization',
            'model_type' => 'App\Models\Overtime',
            'description' => 'Total overtime hours per employee per month.',
            'calculation_method' => 'sum',
            'target_value' => '20',
            'comparison_operator' => '<=',
        ]);

        Kpi::create([
            'name' => 'Leave Days Taken',
            'slug' => 'leave-days-taken',
            'model_type' => 'App\Models\LeaveRequest',
            'description' => 'Total leave days taken by an employee in a year.',
            'calculation_method' => 'sum',
            'target_value' => '21',
            'comparison_operator' => '<=',
        ]);

        Kpi::create([
            'name' => 'Task Completion Rate',
            'slug' => 'task-completion-rate',
            'model_type' => 'App\Models\Task',
            'description' => 'Number of completed tasks per business.',
            'calculation_method' => 'count',
            'target_value' => '3',
            'comparison_operator' => '>=',
        ]);

        Kpi::create([
            'name' => 'Average Advance Amount',
            'slug' => 'average-advance-amount',
            'model_type' => 'App\Models\Advance',
            'description' => 'Average advance amount per employee.',
            'calculation_method' => 'average',
            'target_value' => '15000',
            'comparison_operator' => '<=',
        ]);

        Kpi::create([
            'name' => 'Average Loan Amount',
            'slug' => 'average-loan-amount',
            'model_type' => 'App\Models\Loan',
            'description' => 'Average loan amount per employee.',
            'calculation_method' => 'average',
            'target_value' => '50000',
            'comparison_operator' => '<=',
        ]);

        Kpi::create([
            'name' => 'Application-to-Hire Ratio',
            'slug' => 'application-to-hire-ratio',
            'model_type' => 'App\Models\JobPost',
            'description' => 'Ratio of applications to target hires per job post.',
            'calculation_method' => 'ratio',
            'target_value' => '10',
            'comparison_operator' => '>=',
        ]);
    }
}
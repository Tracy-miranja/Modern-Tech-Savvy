<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ModulesSeeder extends Seeder
{
    public function run()
    {
        $modules = [
            [
                'name' => 'Core HR Management',
                'description' => 'Essential HR features including employee management, attendance, and basic reporting',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'is_core' => true,
                'features' => [
                    'Employee Database',
                    'Attendance Management',
                    'Leave Management',
                    'Basic Reports',
                    'Document Management'
                ],
                'icon' => 'people-fill'
            ],
            [
                'name' => 'Payroll Management',
                'description' => 'Complete payroll processing system with tax calculations and compliance',
                'price_monthly' => 49.99,
                'price_yearly' => 499.99,
                'is_core' => false,
                'features' => [
                    'Salary Processing',
                    'Tax Calculations',
                    'Payslip Generation',
                    'Statutory Compliance',
                    'Multiple Payment Methods',
                    'Payroll Reports'
                ],
                'icon' => 'wallet2'
            ],
            [
                'name' => 'Recruitment & Onboarding',
                'description' => 'End-to-end recruitment solution from job posting to onboarding',
                'price_monthly' => 39.99,
                'price_yearly' => 399.99,
                'is_core' => false,
                'features' => [
                    'Job Posting Management',
                    'Applicant Tracking',
                    'Interview Scheduling',
                    'Candidate Assessment',
                    'Onboarding Workflow',
                    'Document Collection'
                ],
                'icon' => 'person-plus-fill'
            ],
            [
                'name' => 'Performance Management',
                'description' => 'Complete performance evaluation and goal tracking system',
                'price_monthly' => 29.99,
                'price_yearly' => 299.99,
                'is_core' => false,
                'features' => [
                    'Goal Setting & Tracking',
                    'Performance Reviews',
                    '360° Feedback',
                    'Skills Assessment',
                    'Development Plans',
                    'Performance Analytics'
                ],
                'icon' => 'graph-up-arrow'
            ],
            [
                'name' => 'Learning Management',
                'description' => 'Employee training and development platform',
                'price_monthly' => 34.99,
                'price_yearly' => 349.99,
                'is_core' => false,
                'features' => [
                    'Course Management',
                    'Training Schedules',
                    'Learning Paths',
                    'Assessment Tools',
                    'Certification Tracking',
                    'Training Reports'
                ],
                'icon' => 'journal-bookmark'
            ],
            [
                'name' => 'Time & Attendance',
                'description' => 'Advanced time tracking and attendance management',
                'price_monthly' => 24.99,
                'price_yearly' => 249.99,
                'is_core' => false,
                'features' => [
                    'Time Tracking',
                    'Shift Management',
                    'Overtime Calculation',
                    'Leave Planning',
                    'Attendance Reports',
                    'Mobile Check-in'
                ],
                'icon' => 'clock-fill'
            ],
            [
                'name' => 'Asset Management',
                'description' => 'Track and manage company assets and resources',
                'price_monthly' => 19.99,
                'price_yearly' => 199.99,
                'is_core' => false,
                'features' => [
                    'Asset Tracking',
                    'Maintenance Scheduling',
                    'Asset Assignment',
                    'Inventory Management',
                    'Asset Reports',
                    'Depreciation Tracking'
                ],
                'icon' => 'box-seam'
            ],
            [
                'name' => 'Employee Self-Service',
                'description' => 'Portal for employees to manage their information and requests',
                'price_monthly' => 14.99,
                'price_yearly' => 149.99,
                'is_core' => false,
                'features' => [
                    'Profile Management',
                    'Leave Requests',
                    'Expense Claims',
                    'Document Access',
                    'Payslip Download',
                    'Benefits Enrollment'
                ],
                'icon' => 'person-workspace'
            ],
            [
                'name' => 'CRM Integration',
                'description' => 'Customer relationship management integration with HR',
                'price_monthly' => 44.99,
                'price_yearly' => 449.99,
                'is_core' => false,
                'features' => [
                    'Contact Management',
                    'Lead Tracking',
                    'Sales Pipeline',
                    'Customer Support',
                    'Email Integration',
                    'Analytics & Reports'
                ],
                'icon' => 'people'
            ],
            [
                'name' => 'Project Management',
                'description' => 'Project planning and resource management tools',
                'price_monthly' => 39.99,
                'price_yearly' => 399.99,
                'is_core' => false,
                'features' => [
                    'Project Planning',
                    'Task Management',
                    'Resource Allocation',
                    'Time Tracking',
                    'Project Reports',
                    'Team Collaboration'
                ],
                'icon' => 'clipboard-data'
            ]
        ];

        foreach ($modules as $module) {
            Module::firstOrCreate(['name' => $module['name']], $module);
        }
    }
}

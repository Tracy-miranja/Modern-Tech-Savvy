<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        // Resolve the platform business dynamically - hardcoding an id
        // here silently seeds against the wrong business (or a
        // nonexistent one) if that id doesn't exist in this database.
        $businessId = \App\Models\Business::where('slug', config('business.main_slug'))->value('id')
            ?? \App\Models\Business::query()->value('id');

        $departments = [
            ['business_id' => $businessId, 'name' => 'IT Department', 'slug' => 'it-department', 'description' => 'Responsible for all IT-related tasks'],
            ['business_id' => $businessId, 'name' => 'Human Resources', 'slug' => 'human-resources', 'description' => 'Handles recruitment, employee benefits, and other HR tasks'],
            ['business_id' => $businessId, 'name' => 'Sales Department', 'slug' => 'sales-department', 'description' => 'Responsible for sales and customer interactions'],
            ['business_id' => $businessId, 'name' => 'Marketing Department', 'slug' => 'marketing-department', 'description' => 'Oversees marketing and branding strategies'],
            ['business_id' => $businessId, 'name' => 'Operations', 'slug' => 'operations', 'description' => 'Manages day-to-day operations of the business'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(['slug' => $department['slug']], $department);
        }
    }
}

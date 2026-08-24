<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JobCategorySeeder extends Seeder
{
    public function run()
    {
        // Resolve the platform business dynamically - hardcoding an id
        // here silently seeds against the wrong business (or a
        // nonexistent one) if that id doesn't exist in this database.
        $businessId = \App\Models\Business::where('slug', config('business.main_slug'))->value('id')
            ?? \App\Models\Business::query()->value('id');

        $jobCategories = [
            ['business_id' => $businessId, 'name' => 'Software Developer', 'slug' => 'software-developer', 'description' => 'Develops and maintains software applications'],
            ['business_id' => $businessId, 'name' => 'Human Resources', 'slug' => 'human-resources', 'description' => 'Manages employee relations and recruitment'],
            ['business_id' => $businessId, 'name' => 'Marketing Manager', 'slug' => 'marketing-manager', 'description' => 'Oversees marketing strategies and campaigns'],
            ['business_id' => $businessId, 'name' => 'Project Manager', 'slug' => 'project-manager', 'description' => 'Manages and coordinates project teams'],
            ['business_id' => $businessId, 'name' => 'Sales Executive', 'slug' => 'sales-executive', 'description' => 'Responsible for sales and client acquisition'],
        ];

        foreach ($jobCategories as $category) {
            JobCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}

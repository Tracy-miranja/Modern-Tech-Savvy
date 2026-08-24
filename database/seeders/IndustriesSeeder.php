<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IndustriesSeeder extends Seeder
{
    public function run()
    {
        $industries = [
            ['name' => 'Information Technology'],
            ['name' => 'Healthcare'],
            ['name' => 'Finance'],
            ['name' => 'Education'],
            ['name' => 'Retail'],
            ['name' => 'Manufacturing'],
            ['name' => 'Construction'],
            ['name' => 'Real Estate'],
            ['name' => 'Hospitality'],
            ['name' => 'Entertainment'],
            ['name' => 'Automotive'],
            ['name' => 'Telecommunications'],
            ['name' => 'Energy'],
            ['name' => 'Agriculture'],
            ['name' => 'Aerospace'],
            ['name' => 'Logistics and Supply Chain'],
            ['name' => 'Food and Beverage'],
            ['name' => 'Fashion'],
            ['name' => 'Media and Publishing'],
            ['name' => 'Pharmaceuticals'],
            ['name' => 'Sports and Recreation'],
            ['name' => 'Legal Services'],
            ['name' => 'Consulting'],
            ['name' => 'Environmental Services'],
            ['name' => 'Transportation'],
            ['name' => 'Government and Public Administration'],
            ['name' => 'Non-Profit and Social Services'],
            ['name' => 'Marketing and Advertising'],
            ['name' => 'E-Commerce'],
            ['name' => 'Beauty and Personal Care'],
            ['name' => 'Insurance'],
            ['name' => 'Cybersecurity'],
            ['name' => 'Event Management'],
            ['name' => 'Research and Development'],
            ['name' => 'Art and Design'],
            ['name' => 'Pet Care'],
            ['name' => 'Fitness and Wellness'],
            ['name' => 'Waste Management'],
        ];

        foreach ($industries as $industry) {
            Industry::firstOrCreate(['name' => $industry['name']], $industry);
        }
    }
}

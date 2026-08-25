<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ShiftSeeder extends Seeder
{
    public function run()
    {

        $businessId = \App\Models\Business::where('slug', config('business.main_slug'))->value('id')
            ?? \App\Models\Business::query()->value('id');

        $shifts = [
            ['business_id' => $businessId, 'name' => 'Morning Shift', 'slug' => 'morning-shift', 'start_time' => '08:00', 'end_time' => '16:00', 'description' => 'Standard morning shift', 'is_active' => true],
            ['business_id' => $businessId, 'name' => 'Afternoon Shift', 'slug' => 'afternoon-shift', 'start_time' => '16:00', 'end_time' => '00:00', 'description' => 'Standard afternoon shift', 'is_active' => true],
            ['business_id' => $businessId, 'name' => 'Night Shift', 'slug' => 'night-shift', 'start_time' => '00:00', 'end_time' => '08:00', 'description' => 'Standard night shift', 'is_active' => true],
            ['business_id' => $businessId, 'name' => 'Flexible Shift', 'slug' => 'flexible-shift', 'start_time' => '10:00', 'end_time' => '18:00', 'description' => 'Flexible shift for employees', 'is_active' => true],
            ['business_id' => $businessId, 'name' => 'Weekend Shift', 'slug' => 'weekend-shift', 'start_time' => '09:00', 'end_time' => '17:00', 'description' => 'Shift during weekends', 'is_active' => true],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(['slug' => $shift['slug']], $shift);
        }
    }
}

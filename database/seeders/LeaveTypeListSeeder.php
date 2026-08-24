<?php

namespace Database\Seeders;

use App\Models\LeaveTypeList;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LeaveTypeListSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            ['name' => 'Annual Leave'],
            ['name' => 'Sick Leave'],
            ['name' => 'Maternity Leave'],
            ['name' => 'Paternity Leave'],
            ['name' => 'Compassionate Leave'],
            ['name' => 'Study Leave'],
            ['name' => 'Unpaid Leave'],
            ['name' => 'Public Holidays'],
            ['name' => 'Sabbatical Leave'],
            ['name' => 'Marriage Leave'],
            ['name' => 'Bereavement Leave'],
            ['name' => 'Adoption Leave'],
            ['name' => 'Relocation Leave'],
            ['name' => 'Childcare Leave'],
            ['name' => 'Voting Leave'],
            ['name' => 'Jury Duty Leave'],
            ['name' => 'Military Leave'],
            ['name' => 'Emergency Leave'],
            ['name' => 'Volunteer Leave'],
            ['name' => 'Wellness Leave'],
        ];

        foreach ($leaveTypes as $leaveTypeData) {
            LeaveTypeList::firstOrCreate(['name' => $leaveTypeData['name']], $leaveTypeData);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganogramPosition extends Model
{
    protected $fillable = [
        'business_id',
        'organogram_role_id',
        'employee_id',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function role()
    {
        return $this->belongsTo(OrganogramRole::class, 'organogram_role_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'organogram_position_department', 'organogram_position_id', 'department_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'organogram_position_team', 'organogram_position_id', 'team_id');
    }

    public function coversEmployee(Employee $employee): bool
    {
        return $this->coversEmployeeViaTeam($employee)
            || $this->coveredDepartmentIds()->intersect($employee->allDepartmentIds())->isNotEmpty();
    }

    public function coversEmployeeViaTeam(Employee $employee): bool
    {
        return (bool) $employee->team_id && $this->coveredTeamIds()->contains($employee->team_id);
    }

    private function coveredDepartmentIds()
    {
        return $this->relationLoaded('departments') ? $this->departments->pluck('id') : $this->departments()->pluck('departments.id');
    }

    private function coveredTeamIds()
    {
        return $this->relationLoaded('teams') ? $this->teams->pluck('id') : $this->teams()->pluck('teams.id');
    }
}

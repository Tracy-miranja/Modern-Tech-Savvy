<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * "This employee holds this role" - the actual assignment. Multiple
 * positions can share the same role (e.g. two different "Manager"
 * positions, each covering a different set of departments) - it's the
 * position's department/team coverage that makes an assignment specific,
 * not the role alone.
 */
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

    /**
     * True if this position's coverage includes the given employee -
     * either through their team directly, or through their department.
     * When more than one position of the same role matches an employee
     * (e.g. one scoped to their team, one scoped to their whole
     * department), the caller is responsible for preferring the more
     * specific (team) match - see Employee::computeTemplateManagerId().
     *
     * Reads from already-loaded `departments`/`teams` relations when
     * available (Employee::computeTemplateManagerId() eager-loads them
     * for every candidate position up front); falls back to a query
     * otherwise so this remains correct when called standalone.
     */
    public function coversEmployee(Employee $employee): bool
    {
        return $this->coversEmployeeViaTeam($employee)
            || $this->coveredDepartmentIds()->intersect($employee->allDepartmentIds())->isNotEmpty();
    }

    /**
     * True if this position covers the employee specifically through
     * their team (a more specific match than a department-wide position).
     */
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

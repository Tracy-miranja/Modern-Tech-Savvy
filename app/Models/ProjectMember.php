<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
    protected $fillable = [
        'project_id',
        'business_id',
        'employee_id',
        'role_on_project',
        'allocation_percentage',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'allocation_percentage' => 'integer',
        'joined_at' => 'date',
        'left_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

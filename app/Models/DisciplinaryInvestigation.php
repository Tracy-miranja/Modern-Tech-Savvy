<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplinaryInvestigation extends Model
{
    protected $fillable = [
        'warning_id',
        'business_id',
        'investigator_id',
        'started_at',
        'concluded_at',
        'findings',
        'outcome',
        'attachment',
    ];

    protected $casts = [
        'started_at' => 'date',
        'concluded_at' => 'date',
    ];

    public function warning()
    {
        return $this->belongsTo(Warning::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function investigator()
    {
        return $this->belongsTo(Employee::class, 'investigator_id');
    }
}

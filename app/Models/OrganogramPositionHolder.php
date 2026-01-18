<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganogramPositionHolder extends Model
{
    protected $fillable = [
    'organogram_position_id',
    'employee_id',
    'start_date',
    'end_date',
    'is_primary'
];


    public function position(): BelongsTo
    {
        return $this->belongsTo(OrganogramPosition::class, 'organogram_position_id');
    }

    public function employee()
{
    return $this->belongsTo(Employee::class, 'employee_id');
}

}

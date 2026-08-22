<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplinaryMinutes extends Model
{
    protected $table = 'disciplinary_minutes';

    protected $fillable = [
        'warning_id',
        'business_id',
        'meeting_date',
        'attendees',
        'notes',
        'attachment',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function warning()
    {
        return $this->belongsTo(Warning::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}

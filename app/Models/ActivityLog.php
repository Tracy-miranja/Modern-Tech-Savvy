<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action', 'title', 'description', 'loggable_id', 'loggable_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loggable()
    {
        return $this->morphTo();
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('loggable_type', Business::class)
            ->where('loggable_id', $businessId);
    }
}

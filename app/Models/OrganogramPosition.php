<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganogramPosition extends Model
{
    protected $fillable = [
        'business_id', 'title', 'code', 'parent_id', 'personnel_position_id',
        'level', 'sort_order', 'description', 'is_active'
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function holders()
    {
        return $this->hasMany(OrganogramPositionHolder::class);
    }

    public function currentHolder()
    {
        return $this->holders()
            ->where('is_primary', 1)
            ->where(function($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->latest('start_date')
            ->first();
    }
    public function positions(): HasMany
    {
        return $this->hasMany(OrganogramPosition::class, 'business_id');
    }
}

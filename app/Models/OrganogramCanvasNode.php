<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganogramCanvasNode extends Model
{
    protected $fillable = [
        'business_id',
        'node_type',
        'ref_id',
        'pos_x',
        'pos_y',
    ];

    protected $casts = [
        'ref_id' => 'integer',
        'pos_x' => 'integer',
        'pos_y' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function edgesFrom()
    {
        return $this->hasMany(OrganogramCanvasEdge::class, 'from_node_id');
    }

    public function edgesTo()
    {
        return $this->hasMany(OrganogramCanvasEdge::class, 'to_node_id');
    }

    public function referencedModel(): ?Model
    {
        return match ($this->node_type) {
            'department' => Department::find($this->ref_id),
            'role' => OrganogramRole::find($this->ref_id),
            'job_category' => JobCategory::find($this->ref_id),
            default => null,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One node on the structure canvas (see 2026_08_07_..._create_organogram_canvas_nodes_and_edges_tables
 * migration for the design rationale). node_type + ref_id point at the
 * real Department/OrganogramRole/JobCategory row this node represents.
 */
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

    /**
     * The real entity (Department, OrganogramRole, or JobCategory) this
     * node represents.
     */
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

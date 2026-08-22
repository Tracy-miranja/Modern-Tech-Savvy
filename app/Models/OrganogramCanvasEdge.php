<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganogramCanvasEdge extends Model
{
    protected $fillable = [
        'business_id',
        'from_node_id',
        'to_node_id',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function fromNode()
    {
        return $this->belongsTo(OrganogramCanvasNode::class, 'from_node_id');
    }

    public function toNode()
    {
        return $this->belongsTo(OrganogramCanvasNode::class, 'to_node_id');
    }
}

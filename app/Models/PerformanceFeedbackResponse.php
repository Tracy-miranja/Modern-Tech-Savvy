<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceFeedbackResponse extends Model
{

    public const QUESTIONS = [
        'strengths' => 'What does this person do well that others should learn from?',
        'growth_areas' => "What's one thing they could improve or do differently?",
        'collaboration_example' => 'Describe a specific example of them collaborating effectively.',
        'additional_comments' => 'Any other feedback you would like to share?',
    ];

    protected $fillable = [
        'performance_feedback_request_id',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(PerformanceFeedbackRequest::class, 'performance_feedback_request_id');
    }
}

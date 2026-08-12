<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Warning extends Model
{
    use HasFactory;

    public const CATEGORIES = ['misconduct', 'gross_misconduct', 'performance', 'attendance', 'other'];

    public const STAGES = [
        'informal_action',
        'investigation',
        'suspension',
        'notification_to_hearing',
        'disciplinary_hearing',
        'decision_outcome',
        'appeal',
        'closed',
    ];

    public const DECISION_OUTCOMES = [
        'pending', 'no_action', 'verbal_warning', 'first_written_warning',
        'final_written_warning', 'dismissal', 'summary_dismissal',
    ];

    public const APPEAL_STATUSES = ['not_filed', 'filed', 'under_review', 'upheld', 'overturned'];

    protected $fillable = [
        'employee_id', 'business_id', 'category', 'offence', 'reported_by_name',
        'issue_date', 'stage', 'hearing_date', 'decision_outcome', 'appeal_status',
        'reason', 'description', 'attachment', 'status', 'issued_by',
        'acknowledged_at', 'acknowledged_by', 'resolution_notes',
        'previous_case_id', 'severity', 'case_type',
    ];

    protected $casts = [
        'issue_date'      => 'date',
        'hearing_date'    => 'date',
        'acknowledged_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Warning $warning) {
            if (empty($warning->case_id)) {
                do {
                    $candidate = 'c' . Str::lower(Str::random(8));
                } while (static::where('case_id', $candidate)->exists());
                $warning->case_id = $candidate;
            }
        });
    }

    public function employee()      { return $this->belongsTo(Employee::class); }
    public function business()      { return $this->belongsTo(Business::class); }
    public function issuedBy()      { return $this->belongsTo(User::class, 'issued_by'); }
    public function acknowledgedBy(){ return $this->belongsTo(Employee::class, 'acknowledged_by'); }
    public function previousCase()  { return $this->belongsTo(Warning::class, 'previous_case_id'); }
    public function nextCases()     { return $this->hasMany(Warning::class, 'previous_case_id'); }

    public static function label(string $value): string
    {
        return str($value)->replace('_', ' ')->title();
    }
}

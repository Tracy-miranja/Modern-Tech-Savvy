<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LeaveDelegation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'employee_id',
        'delegate_id',
        'leave_request_id',
        'duties_delegated',
        'delegate_accepted',
        'accepted_at',
        'declined_at',
    ];

    protected $casts = [
        'delegate_accepted' => 'boolean',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function delegate()
    {
        return $this->belongsTo(Employee::class, 'delegate_id');
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function getStatusAttribute(): string
    {
        if ($this->declined_at) return 'declined';
        if ($this->delegate_accepted) return 'accepted';
        return 'pending';
    }
}

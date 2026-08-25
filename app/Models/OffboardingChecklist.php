<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OffboardingChecklist extends Model
{
    protected $fillable = [
        'business_id',
        'employee_id',
        'contract_action_id',
        'status',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function contractAction()
    {
        return $this->belongsTo(EmployeeContractAction::class, 'contract_action_id');
    }

    public function tasks()
    {
        return $this->hasMany(OffboardingTask::class, 'checklist_id')->orderBy('sequence_order');
    }

    public function progressPercent(): int
    {
        $total = $this->tasks->count();
        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->tasks->where('is_done', true)->count() / $total) * 100);
    }

    public function refreshStatus(): void
    {
        $tasks = $this->tasks()->get();
        $allDone = $tasks->isNotEmpty() && $tasks->every(fn (OffboardingTask $t) => $t->is_done);

        if ($allDone && $this->status !== 'completed') {
            $this->update(['status' => 'completed', 'completed_at' => now()]);
        } elseif (!$allDone && $this->status !== 'in_progress') {
            $this->update(['status' => 'in_progress', 'completed_at' => null]);
        }
    }
}

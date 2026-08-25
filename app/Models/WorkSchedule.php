<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkSchedule extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'business_id',
        'working_days',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'working_days' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function isWorkingDay(Carbon $date): bool
    {
        $dayOfWeek = $date->dayOfWeek;
        return in_array($dayOfWeek, $this->working_days ?? []);
    }

    public static function getActiveSchedule(int $employeeId, Carbon $date, ?int $businessId = null): ?self
    {
        $q = self::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });

        if ($businessId) {
            $q->where('business_id', $businessId);
        }

        return $q->with('shift')->first();
    }

    public function getWorkingDaysNamesAttribute(): array
    {
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return collect($this->working_days ?? [])
            ->map(fn($day) => $days[$day] ?? '')
            ->filter()
            ->values()
            ->all();
    }
}
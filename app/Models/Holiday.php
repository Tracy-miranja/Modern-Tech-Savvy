<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Holiday extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'date',
        'is_recurring',
        'is_working_day',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
        'is_working_day' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Check if a given date is a holiday for this business
     */
    public static function isHoliday(int $businessId, Carbon $date): ?self
    {
        return self::where('business_id', $businessId)
            ->where(function ($query) use ($date) {
                // Exact date match
                $query->where('date', $date->format('Y-m-d'))
                    // Or recurring holiday (same month and day)
                    ->orWhere(function ($q) use ($date) {
                        $q->where('is_recurring', true)
                            ->whereRaw('MONTH(date) = ?', [$date->month])
                            ->whereRaw('DAY(date) = ?', [$date->day]);
                    });
            })
            ->first();
    }

    /**
     * Get all holidays for a business in a date range
     */
    public static function getHolidaysInRange(int $businessId, Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        // One-time holidays inside the range
        $holidays = self::where('business_id', $businessId)
            ->where('is_recurring', false)
            ->whereBetween('date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get();

        // Recurring holidays (generate dates for each year in range)
        $recurring = self::where('business_id', $businessId)
            ->where('is_recurring', true)
            ->get();

        for ($year = $start->year; $year <= $end->year; $year++) {
            foreach ($recurring as $holiday) {
                $base = Carbon::parse($holiday->date);
                $dateThisYear = Carbon::createFromDate($year, $base->month, $base->day);

                if ($dateThisYear->betweenIncluded($start, $end)) {
                    $clone = $holiday->replicate();
                    $clone->date = $dateThisYear;
                    $holidays->push($clone);
                }
            }
        }

        // prevent duplicates (same name+date)
        return $holidays->unique(fn($h) => ($h->name . '|' . Carbon::parse($h->date)->format('Y-m-d')))
                        ->values();
    }

}
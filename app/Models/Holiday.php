<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Holiday extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = [
        'business_id',
        'location_id',
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

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    private static function scopeToLocation(Builder $query, ?int $locationId): Builder
    {
        return $query->where(function (Builder $q) use ($locationId) {
            $q->whereNull('location_id');
            if ($locationId) {
                $q->orWhere('location_id', $locationId);
            }
        });
    }

    public static function isHoliday(int $businessId, Carbon $date, ?int $locationId = null): ?self
    {
        $query = self::where('business_id', $businessId)
            ->where(function ($query) use ($date) {

                $query->where('date', $date->format('Y-m-d'))

                    ->orWhere(function ($q) use ($date) {
                        $q->where('is_recurring', true)
                            ->whereRaw('MONTH(date) = ?', [$date->month])
                            ->whereRaw('DAY(date) = ?', [$date->day]);
                    });
            });

        return self::scopeToLocation($query, $locationId)->first();
    }

    public static function getHolidaysInRange(int $businessId, Carbon $start, Carbon $end, ?int $locationId = null): \Illuminate\Support\Collection
    {

        $holidays = self::scopeToLocation(
            self::where('business_id', $businessId)->where('is_recurring', false),
            $locationId
        )
            ->whereBetween('date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get();

        $recurring = self::scopeToLocation(
            self::where('business_id', $businessId)->where('is_recurring', true),
            $locationId
        )->get();

        for ($year = $start->year; $year <= $end->year; $year++) {
            foreach ($recurring as $holiday) {
                $base = Carbon::parse($holiday->date);

                $dateThisYear = Carbon::createFromDate($year, $base->month, $base->day)->startOfDay();

                if ($dateThisYear->betweenIncluded($start, $end)) {
                    $clone = $holiday->replicate();
                    $clone->date = $dateThisYear;
                    $holidays->push($clone);
                }
            }
        }

        return $holidays->unique(fn($h) => (($h->slug ?? $h->id) . '|' . Carbon::parse($h->date)->format('Y-m-d')))
                        ->values();
    }

}

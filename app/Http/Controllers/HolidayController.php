<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;

class HolidayController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        
        $year = $request->input('year', now()->year);
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        $holidays = Holiday::getHolidaysInRange($business->id, $start, $end);
        
        $holidayTable = view('attendances.holidays_table', compact('holidays', 'year'))->render();
        return RequestResponse::ok('Holidays fetched successfully.', $holidayTable);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'sometimes|boolean',
            'is_working_day' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));

            Holiday::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'date' => $validated['date'],
                'is_recurring' => $validated['is_recurring'] ?? false,
                'is_working_day' => $validated['is_working_day'] ?? false,
                'description' => $validated['description'] ?? null,
            ]);

            return RequestResponse::created('Holiday created successfully.');
        });
    }

    public function edit(Request $request)
    {
        $validated = $request->validate([
            'holiday' => 'required|string|exists:holidays,slug',
        ]);

        $holiday = Holiday::findBySlug($validated['holiday']);
        $holidayForm = view('attendances.holidays_form', compact('holiday'))->render();

        return RequestResponse::ok('Holiday found', $holidayForm);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'holiday_slug' => 'required|exists:holidays,slug',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'sometimes|boolean',
            'is_working_day' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $holiday = Holiday::findBySlug($validated['holiday_slug']);

            $holiday->update([
                'name' => $validated['name'],
                'date' => $validated['date'],
                'is_recurring' => $validated['is_recurring'] ?? false,
                'is_working_day' => $validated['is_working_day'] ?? false,
                'description' => $validated['description'] ?? null,
            ]);

            return RequestResponse::ok('Holiday updated successfully.');
        });
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'holiday' => 'required|exists:holidays,slug',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $holiday = Holiday::findBySlug($validated['holiday']);
            $holiday->delete();

            return RequestResponse::ok('Holiday deleted successfully.');
        });
    }

    /**
     * Check if a specific date is a holiday
     */
    public function checkHoliday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        $date = Carbon::parse($validated['date']);
        $holiday = Holiday::isHoliday($business->id, $date);

        if ($holiday) {
            return RequestResponse::ok('This is a holiday', [
                'is_holiday' => true,
                'holiday' => $holiday,
            ]);
        }

        return RequestResponse::ok('This is not a holiday', [
            'is_holiday' => false,
        ]);
    }
}
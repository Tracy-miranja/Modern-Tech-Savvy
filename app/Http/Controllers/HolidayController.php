<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Business;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $year = $request->input('year', now()->year);
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);
        $locationId = $request->filled('location_id') ? (int) $request->input('location_id') : null;

        
        
        $holidays = $locationId
            ? Holiday::getHolidaysInRange($business->id, $start, $end, $locationId)
            : Holiday::where('business_id', $business->id)
                ->whereYear('date', $year)
                ->with('location:id,name')
                ->orderBy('date')
                ->get();

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
            'location_id' => 'nullable|integer|exists:locations,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));

            $locationId = null;
            if (!empty($validated['location_id'])) {
                $location = Location::where('business_id', $business->id)->find($validated['location_id']);
                if (!$location) {
                    return RequestResponse::badRequest('Location not found in this business.', 404);
                }
                $locationId = $location->id;
            }

            Holiday::create([
                'business_id' => $business->id,
                'location_id' => $locationId,
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
            'location_id' => 'nullable|integer|exists:locations,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $holiday = Holiday::findBySlug($validated['holiday_slug']);

            if (array_key_exists('location_id', $validated)) {
                $holiday->location_id = $validated['location_id']
                    ? Location::where('business_id', $holiday->business_id)->find($validated['location_id'])?->id
                    : null;
            }

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

    
    public function availableCountries(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $countryToGuess = $business?->country;

        if ($request->filled('location_id')) {
            $location = Location::where('business_id', $business?->id)->find($request->input('location_id'));
            if ($location) {
                $countryToGuess = $location->resolvedCountry();
            }
        }

        try {
            $response = Http::timeout(10)->get('https://date.nager.at/api/v3/AvailableCountries');
        } catch (\Throwable $e) {
            Log::error('Nager.Date AvailableCountries request failed: ' . $e->getMessage());
            return RequestResponse::badRequest('Could not reach the public holiday calendar service. Please try again later.', 502);
        }

        if (!$response->successful()) {
            return RequestResponse::badRequest('Could not reach the public holiday calendar service. Please try again later.', 502);
        }

        $countries = $response->json() ?? [];

        $guess = collect($countries)->first(function ($country) use ($countryToGuess) {
            return $countryToGuess
                && strtolower(trim($country['name'] ?? '')) === strtolower(trim($countryToGuess));
        });

        return RequestResponse::ok('Countries fetched successfully.', [
            'countries' => $countries,
            'guessed_country_code' => $guess['countryCode'] ?? null,
        ]);
    }

    
    public function importFromApi(Request $request)
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
            'year' => 'required|integer|min:2000|max:2100',
            'location_id' => 'nullable|integer|exists:locations,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Active business not found in session.');
        }

        $locationId = null;
        if (!empty($validated['location_id'])) {
            $location = Location::where('business_id', $business->id)->find($validated['location_id']);
            if (!$location) {
                return RequestResponse::badRequest('Location not found in this business.', 404);
            }
            $locationId = $location->id;
        }

        $countryCode = strtoupper($validated['country_code']);

        try {
            $response = Http::timeout(10)->get("https://date.nager.at/api/v3/PublicHolidays/{$validated['year']}/{$countryCode}");
        } catch (\Throwable $e) {
            Log::error('Nager.Date PublicHolidays request failed: ' . $e->getMessage());
            return RequestResponse::badRequest('Could not reach the public holiday calendar service. Please try again later.', 502);
        }

        if (!$response->successful()) {
            return RequestResponse::badRequest('Could not fetch holidays for that country/year - check the country code.', 502);
        }

        $items = $response->json();
        if (!is_array($items)) {
            return RequestResponse::badRequest('Unexpected response from the holiday calendar service.', 502);
        }

        return $this->handleTransaction(function () use ($items, $business, $locationId) {
            $imported = 0;
            $skipped = 0;

            foreach ($items as $item) {
                $name = $item['name'] ?? $item['localName'] ?? null;
                $date = $item['date'] ?? null;
                if (!$name || !$date) {
                    continue;
                }

                $exists = Holiday::where('business_id', $business->id)
                    ->where('location_id', $locationId)
                    ->where('name', $name)
                    ->whereDate('date', $date)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Holiday::create([
                    'business_id' => $business->id,
                    'location_id' => $locationId,
                    'name' => $name,
                    'date' => $date,
                    'is_recurring' => (bool) ($item['fixed'] ?? false),
                    'is_working_day' => false,
                ]);
                $imported++;
            }

            return RequestResponse::ok("Imported {$imported} holiday(s), skipped {$skipped} already on file.");
        });
    }

    
    public function checkHoliday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'location_id' => 'nullable|integer|exists:locations,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        $date = Carbon::parse($validated['date']);
        $holiday = Holiday::isHoliday($business->id, $date, $validated['location_id'] ?? null);

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
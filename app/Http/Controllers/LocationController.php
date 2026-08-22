<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Models\Business;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $locations = Location::where('business_id', $business->id)->get();
        $location_table = view('locations._table', compact('locations'))->render();

        return RequestResponse::ok('Ok', $location_table);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'company_size' => 'nullable|string',
            'registration_no' => 'nullable|string',
            'tax_pin_no' => 'nullable|string',
            'business_license_no' => 'nullable|string',
            'address' => 'required|string',
            'country' => 'nullable|string|max:255',
        ]);

        return $this->handleTransaction(function () use ($request, $validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));

            $location = $business->locations()->create([
                'name' => $validatedData['name'],
                'company_size' => $validatedData['company_size'] ?? null,
                'registration_no' => $validatedData['registration_no'] ?? null,
                'tax_pin_no' => $validatedData['tax_pin_no'] ?? null,
                'business_license_no' => $validatedData['business_license_no'] ?? null,
                'physical_address' => $validatedData['address'] ?? null,
                'country' => $validatedData['country'] ?? null,
            ]);

            $location->setStatus(Status::ACTIVE);

            return RequestResponse::created('Location added successfully.');
        });
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'location' => 'required|string|exists:locations,slug',
        ]);

        $location = Location::where('slug', $validatedData['location'])->firstOrFail();
        $location_form = view('locations._form', compact('location'))->render();

        return RequestResponse::ok('Ok', $location_form);
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'location_slug' => 'required|exists:locations,slug',
            'name' => 'required|string|max:255',
            'company_size' => 'nullable|string',
            'registration_no' => 'nullable|string',
            'tax_pin_no' => 'nullable|string',
            'business_license_no' => 'nullable|string',
            'address' => 'required|string',
            'country' => 'nullable|string|max:255',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $location = Location::where('slug', $validatedData['location_slug'])->firstOrFail();

            $location->update([
                'name' => $validatedData['name'],
                'company_size' => $validatedData['company_size'] ?? null,
                'registration_no' => $validatedData['registration_no'] ?? null,
                'tax_pin_no' => $validatedData['tax_pin_no'] ?? null,
                'business_license_no' => $validatedData['business_license_no'] ?? null,
                'physical_address' => $validatedData['address'] ?? null,
                'country' => $validatedData['country'] ?? null,
            ]);

            $location->setStatus(Status::ACTIVE);

            return RequestResponse::ok('Location updated successfully.');
        });
    }

    public function bulkDelete(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:locations,id'
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $deleted = Location::whereIn('id', $validatedData['ids'])->delete();

            if ($deleted) {
                return RequestResponse::ok('Selected locations deleted successfully.');
            }

            return RequestResponse::badRequest('Failed to delete locations.', 500);
        });
    }

    public function destroy(Request $request)
    {
        $validatedData = $request->validate([
            'location' => 'required|exists:locations,slug',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $location = Location::where('slug', $validatedData['location'])->first();

            if ($location) {
                $location->delete();
                return RequestResponse::ok('Location deleted successfully.');
            }

            return RequestResponse::badRequest('Failed to delete location.', 404);
        });
    }
}
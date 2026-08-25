<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ContactSubmission;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Lead;

class ContactSubmissionController extends Controller
{
    use HandleTransactions;

    public function externalStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'api_token' => 'required|string',
                'business_slug' => 'required|string',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'inquiry_type' => 'required|string|max:255',
                'message' => 'required|string',
                'utm_source' => 'nullable|string|max:255',
                'utm_medium' => 'nullable|string|max:255',
                'utm_campaign' => 'nullable|string|max:255',
            ], [
                'phone.regex' => 'The phone number must be a valid international format (e.g., +1234567890).',
            ]);

            $business = Business::where('slug', $validated['business_slug'])->first();

            if (!$business) {
                return RequestResponse::unauthorized('Invalid business or unauthorized API token.');
            }

            if (!$business->api_token) {
                return RequestResponse::unauthorized('Invalid or unauthorized API token.');
            }

            try {
                if (!Hash::check($validated['api_token'], $business->api_token)) {
                    return RequestResponse::unauthorized('Invalid or unauthorized API token.');
                }
            } catch (\Exception $e) {
                return RequestResponse::unauthorized('Invalid or unauthorized API token.');
            }

            return $this->handleTransaction(function () use ($validated, $business) {
                $submission = ContactSubmission::create([
                    'business_id' => $business->id,
                    'first_name' => $validated['first_name'] ?? '',
                    'last_name' => $validated['last_name'] ?? '',
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'company_name' => $validated['company_name'] ?? null,
                    'country' => $validated['country'] ?? null,
                    'inquiry_type' => $validated['inquiry_type'],
                    'message' => $validated['message'],
                    'source' => 'api',
                    'utm_source' => $validated['utm_source'] ?? null,
                    'utm_medium' => $validated['utm_medium'] ?? null,
                    'utm_campaign' => $validated['utm_campaign'] ?? null,
                    'status' => 'new',
                ]);

                Lead::create([
                    'business_id' => $business->id,
                    'contact_submission_id' => $submission->id,
                    'name' => trim($submission->first_name . ' ' . $submission->last_name) ?: 'Unknown',
                    'email' => $submission->email,
                    'phone' => $submission->phone,
                    'source' => 'api',
                    'status' => $submission->status,
                    'user_id' => null,
                ]);

                return RequestResponse::ok('Contact submission received successfully', [
                    'submission_id' => $submission->id,
                    'status' => $submission->status,
                ]);
            }, function ($exception) {
                return RequestResponse::badRequest('An error occurred while processing your submission: ' . $exception->getMessage());
            });
        } catch (ValidationException $e) {
            return RequestResponse::badRequest('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('Unexpected error in externalStore: ' . $e->getMessage(), [
                'request' => $request->except('api_token'),
            ]);
            return RequestResponse::badRequest('An unexpected error occurred.');
        }
    }
}
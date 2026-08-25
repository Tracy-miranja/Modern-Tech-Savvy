<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ClientPayment;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    use HandleTransactions;

    private function authorizedClient(string $businessSlug, string $clientBusinessSlug): ?Business
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business || $business->slug !== config('business.main_slug')) {
            return null;
        }

        return Business::findBySlug($clientBusinessSlug);
    }

    public function fetch(Request $request, $business_slug, $client_business_slug)
    {
        $clientBusiness = $this->authorizedClient($business_slug, $client_business_slug);
        if (!$clientBusiness) {
            return RequestResponse::forbidden('Only the platform business can view client payments.');
        }

        $payments = ClientPayment::where('client_business_id', $clientBusiness->id)
            ->with(['module:id,name', 'recordedBy:id,name'])
            ->orderByDesc('period_start')
            ->get();

        return RequestResponse::ok('Payments fetched.', $payments);
    }

    public function store(Request $request, $business_slug, $client_business_slug)
    {
        $clientBusiness = $this->authorizedClient($business_slug, $client_business_slug);
        if (!$clientBusiness) {
            return RequestResponse::forbidden('Only the platform business can record client payments.');
        }

        $validated = $request->validate([
            'module_id' => 'nullable|integer|exists:modules,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'required|in:bank,mpesa,cheque,cash,other',
            'reference' => 'nullable|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated, $clientBusiness, $request) {
            $payment = ClientPayment::create($validated + [
                'client_business_id' => $clientBusiness->id,
                'currency' => $validated['currency'] ?? 'KES',
                'recorded_by_user_id' => auth()->id(),
            ]);

            $moduleIds = !empty($validated['module_id'])
                ? [$validated['module_id']]
                : $clientBusiness->modules()->pluck('modules.id')->all();

            if (!empty($moduleIds)) {
                $clientBusiness->modules()->syncWithoutDetaching(
                    collect($moduleIds)->mapWithKeys(fn ($id) => [$id => [
                        'is_active' => true,
                        'subscription_ends_at' => $validated['period_end'],
                    ]])->all()
                );
            }

            activity()
                ->causedBy($request->user())
                ->performedOn($clientBusiness)
                ->withProperties(['amount' => $validated['amount'], 'period_end' => $validated['period_end']])
                ->log('Payment recorded');

            return RequestResponse::created('Payment recorded.', $payment->load('module', 'recordedBy'));
        });
    }
}

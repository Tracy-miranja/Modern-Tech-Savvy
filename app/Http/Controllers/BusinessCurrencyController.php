<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessCurrency;
use App\Services\CurrencyService;
use App\Http\RequestResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BusinessCurrencyController extends Controller
{
    // ── Resolve business from route param OR session (route param wins) ──────

    private function resolveBusiness(Request $request): ?Business
    {
        $param = $request->route('business');

        // Route model binding returns the model directly — handle both cases
        if ($param instanceof Business) {
            return $param;
        }

        $slug = $param ?? session('active_business_slug');
        return $slug ? Business::findBySlug($slug) : null;
    }

    // ── Page ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return redirect()->back()->with('error', 'Business not found.');

        $currencies      = BusinessCurrency::where('business_id', $business->id)
            ->orderByDesc('is_primary')->orderBy('currency_code')->get();
        $knownCurrencies = BusinessCurrency::knownCurrencies();

        return view('settings.currencies', [
            'page'            => 'Currency Management',
            'business'        => $business,
            'currencies'      => $currencies,
            'knownCurrencies' => $knownCurrencies,
        ]);
    }

    // ── List (JSON) ───────────────────────────────────────────────────────────

    public function list(Request $request)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $currencies = BusinessCurrency::where('business_id', $business->id)
            ->orderByDesc('is_primary')->orderBy('currency_code')
            ->get()->map(fn($c) => $this->formatCurrency($c));

        return RequestResponse::ok('success', ['currencies' => $currencies]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $validator = Validator::make($request->all(), [
            'currency_code'  => 'required|string|max:10',
            'currency_name'  => 'required|string|max:100',
            'symbol'         => 'nullable|string|max:10',
            'decimal_places' => 'required|integer|min:0|max:4',
            'is_primary'     => 'boolean',
            'rate_mode'      => 'required|in:auto,manual',
            'manual_rate'    => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) return RequestResponse::badRequest($validator->errors()->first());

        $code = strtoupper(trim($request->currency_code));
        if (BusinessCurrency::where('business_id', $business->id)->where('currency_code', $code)->exists()) {
            return RequestResponse::badRequest("Currency {$code} already exists for this business.");
        }

        try {
            DB::beginTransaction();

            if ($request->boolean('is_primary')) {
                BusinessCurrency::where('business_id', $business->id)
                    ->where('is_primary', true)->update(['is_primary' => false]);
            }

            $currency = BusinessCurrency::create([
                'business_id'    => $business->id,
                'currency_code'  => $code,
                'currency_name'  => $request->currency_name,
                'symbol'         => $request->symbol,
                'decimal_places' => $request->decimal_places ?? 2,
                'is_primary'     => $request->boolean('is_primary'),
                'rate_mode'      => $request->rate_mode,
                'manual_rate'    => $request->rate_mode === 'manual' ? $request->manual_rate : null,
            ]);

            if ($request->rate_mode === 'auto') {
                $this->refreshAutoRate($currency, $business);
            }

            DB::commit();
            return RequestResponse::ok('Currency added successfully.', [
                'currency' => $this->formatCurrency($currency->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add business currency: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to add currency: ' . $e->getMessage());
        }
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    // $businessSlug absorbs the {business} route param so $id correctly receives the currency ID
    public function show(Request $request, $businessSlug, $id)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $currency = BusinessCurrency::where('business_id', $business->id)
            ->where('id', $id)->firstOrFail();

        return RequestResponse::ok('success', ['currency' => $this->formatCurrency($currency)]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    // $businessSlug absorbs the {business} route param so $id correctly receives the currency ID
    public function update(Request $request, $businessSlug, $id)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $currency = BusinessCurrency::where('business_id', $business->id)
            ->where('id', $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'currency_name'  => 'sometimes|string|max:100',
            'symbol'         => 'nullable|string|max:10',
            'decimal_places' => 'sometimes|integer|min:0|max:4',
            'is_primary'     => 'boolean',
            'rate_mode'      => 'sometimes|in:auto,manual',
            'manual_rate'    => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) return RequestResponse::badRequest($validator->errors()->first());

        try {
            DB::beginTransaction();

            if ($request->boolean('is_primary') && !$currency->is_primary) {
                BusinessCurrency::where('business_id', $business->id)
                    ->where('is_primary', true)->update(['is_primary' => false]);
            }

            $updateData = array_filter([
                'currency_name'  => $request->currency_name,
                'symbol'         => $request->symbol,
                'decimal_places' => $request->decimal_places,
                'is_primary'     => $request->has('is_primary') ? $request->boolean('is_primary') : $currency->is_primary,
                'rate_mode'      => $request->rate_mode,
                'manual_rate'    => $request->rate_mode === 'manual' ? $request->manual_rate : $currency->manual_rate,
            ], fn($v) => !is_null($v));

            $currency->update($updateData);

            if ($request->rate_mode === 'auto') {
                $this->refreshAutoRate($currency, $business);
            }

            DB::commit();
            return RequestResponse::ok('Currency updated successfully.', [
                'currency' => $this->formatCurrency($currency->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update business currency: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to update currency: ' . $e->getMessage());
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    // $businessSlug absorbs the {business} route param so $id correctly receives the currency ID
    public function destroy(Request $request, $businessSlug, $id)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $currency = BusinessCurrency::where('business_id', $business->id)
            ->where('id', $id)->firstOrFail();

        if ($currency->is_primary) {
            return RequestResponse::badRequest('Cannot delete the primary currency. Set another currency as primary first.');
        }

        $currency->delete();
        return RequestResponse::ok('Currency deleted successfully.');
    }

    // ── Bulk Delete ───────────────────────────────────────────────────────────

    public function bulkDestroy(Request $request)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $ids = $request->input('ids', []);
        if (empty($ids)) return RequestResponse::badRequest('No currencies selected.');

        $primaryId = BusinessCurrency::where('business_id', $business->id)
            ->where('is_primary', true)->whereIn('id', $ids)->value('id');

        if ($primaryId) {
            return RequestResponse::badRequest('Cannot delete the primary currency. Deselect it and try again.');
        }

        BusinessCurrency::where('business_id', $business->id)->whereIn('id', $ids)->delete();
        return RequestResponse::ok('Selected currencies deleted successfully.');
    }

    // ── Refresh Auto Rate (single) ────────────────────────────────────────────

    // $businessSlug absorbs the {business} route param so $id correctly receives the currency ID
    public function refreshRate(Request $request, $businessSlug, $id)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $currency = BusinessCurrency::where('business_id', $business->id)
            ->where('id', $id)->firstOrFail();

        if ($currency->is_primary) {
            return RequestResponse::badRequest('Primary currency always has a rate of 1.00.');
        }

        $success = $this->refreshAutoRate($currency, $business);

        if (!$success) {
            return RequestResponse::badRequest('Failed to fetch exchange rate. Please try again or use manual mode.');
        }

        return RequestResponse::ok('Rate refreshed successfully.', [
            'currency' => $this->formatCurrency($currency->fresh()),
        ]);
    }

    // ── Refresh All Auto Rates ────────────────────────────────────────────────

    public function refreshAllRates(Request $request)
    {
        $business = $this->resolveBusiness($request);
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $currencies = BusinessCurrency::where('business_id', $business->id)
            ->where('is_primary', false)->where('rate_mode', 'auto')->get();

        $refreshed = 0;
        foreach ($currencies as $currency) {
            if ($this->refreshAutoRate($currency, $business)) $refreshed++;
        }

        $allCurrencies = BusinessCurrency::where('business_id', $business->id)
            ->orderByDesc('is_primary')->orderBy('currency_code')
            ->get()->map(fn($c) => $this->formatCurrency($c));

        return RequestResponse::ok("Refreshed {$refreshed} exchange rate(s).", [
            'currencies' => $allCurrencies,
        ]);
    }

    // ── Known Currencies List ─────────────────────────────────────────────────

    public function knownCurrencies()
    {
        return RequestResponse::ok('success', [
            'currencies' => BusinessCurrency::knownCurrencies(),
        ]);
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function refreshAutoRate(BusinessCurrency $currency, $business): bool
    {
        try {
            $primary     = BusinessCurrency::where('business_id', $business->id)->where('is_primary', true)->first();
            $primaryCode = $primary?->currency_code ?? $business->currency ?? 'KES';

            if ($currency->currency_code === $primaryCode) {
                $currency->update(['auto_rate' => 1.0, 'rate_fetched_at' => now()]);
                return true;
            }

            $rate = app(CurrencyService::class)->getRate($currency->currency_code, $primaryCode);
            $currency->update(['auto_rate' => $rate, 'rate_fetched_at' => now()]);

            Log::info("Auto rate refreshed for {$currency->currency_code}", [
                'business_id' => $business->id,
                'primary'     => $primaryCode,
                'currency'    => $currency->currency_code,
                'rate'        => $rate,
                'meaning'     => "1 {$currency->currency_code} = {$rate} {$primaryCode}",
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to refresh auto rate for {$currency->currency_code}: " . $e->getMessage());
            return false;
        }
    }

    private function formatCurrency(BusinessCurrency $c): array
    {
        return [
            'id'              => $c->id,
            'currency_code'   => $c->currency_code,
            'currency_name'   => $c->currency_name,
            'symbol'          => $c->symbol,
            'decimal_places'  => $c->decimal_places,
            'is_primary'      => $c->is_primary,
            'rate_mode'       => $c->rate_mode,
            'rate_mode_label' => $c->getRateModeLabel(),
            'manual_rate'     => $c->manual_rate,
            'auto_rate'       => $c->auto_rate,
            'effective_rate'  => $c->effective_rate,
            'rate_fetched_at' => $c->rate_fetched_at?->diffForHumans(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessCurrency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    protected int $cacheTtlSeconds = 21600;

    //   usage:   usdAmount = kesAmount * 0.01  (= kesAmount / 100)

    public function getBusinessRate($business, string $from, string $to): float
    {
        $from = strtoupper(trim($from));
        $to   = strtoupper(trim($to));

        if ($from === $to) return 1.0;

        if (is_int($business) || is_numeric($business)) {
            $business = Business::find($business);
        }
        if (!$business) {
            Log::warning("CurrencyService::getBusinessRate — business not found, using global rate");
            return $this->getRate($from, $to);
        }

        $currencies = BusinessCurrency::where('business_id', $business->id)
            ->get()
            ->keyBy('currency_code');

        $primary     = $currencies->firstWhere('is_primary', true);
        $primaryCode = $primary?->currency_code ?? $business->currency ?? 'KES';

        $fromRow = $currencies->get($from);
        $toRow   = $currencies->get($to);

        $fromRate = $this->resolveRate($fromRow, $from, $primaryCode, $business);
        $toRate   = $this->resolveRate($toRow,   $to,   $primaryCode, $business);

        if ($toRate > 0) {
            $rate = $fromRate / $toRate;
            Log::debug("BusinessRate [{$business->id}] {$from}→{$to}", [
                'from_rate' => $fromRate,
                'to_rate'   => $toRate,
                'result'    => round($rate, 6),
            ]);
            return round($rate, 6);
        }

        Log::warning("CurrencyService: toRate=0 for {$to}, falling back to live API");
        return $this->getRate($from, $to);
    }

    private function resolveRate(
        ?BusinessCurrency $currency,
        string $currencyCode,
        string $primaryCode,
        $business
    ): float {

        if ($currencyCode === $primaryCode) return 1.0;

        if (!$currency) {

            return $this->getRate($currencyCode, $primaryCode);
        }

        if ($currency->rate_mode === 'manual') {
            return floatval($currency->manual_rate ?? 1.0);
        }

        $isStale = !$currency->rate_fetched_at
            || $currency->rate_fetched_at->diffInSeconds(now()) > $this->cacheTtlSeconds;

        if ($isStale || !$currency->auto_rate) {
            try {

                $liveRate = $this->getRate($currencyCode, $primaryCode);
                $currency->update([
                    'auto_rate'       => $liveRate,
                    'rate_fetched_at' => now(),
                ]);
                return $liveRate;
            } catch (\Exception $e) {
                Log::error("Failed to refresh auto rate for {$currencyCode}: " . $e->getMessage());
                if ($currency->auto_rate) return floatval($currency->auto_rate);
            }
        }

        return floatval($currency->auto_rate ?? 1.0);
    }

    // Uses USD as API base and cross-multiplies.

    public function getAllRates(): array
    {
        return Cache::remember('fx_rates_usd_base', $this->cacheTtlSeconds, function () {
            return $this->fetchAllRatesFromApi();
        });
    }

    public function getRate(string $fromCurrency, string $toCurrency): float
    {
        $from = strtoupper(trim($fromCurrency));
        $to   = strtoupper(trim($toCurrency));

        if ($from === $to) return 1.0;

        $rates = $this->getAllRates();

        if (!isset($rates[$from]) || !isset($rates[$to])) {
            Log::warning("CurrencyService: unknown currency pair {$from}→{$to}, using 1.0");
            return 1.0;
        }

        return round($rates[$to] / $rates[$from], 6);
    }

    public function convert(float $amount, string $from, string $to, ?float $rate = null): float
    {
        $from = strtoupper(trim($from));
        $to   = strtoupper(trim($to));
        if ($from === $to) return $amount;
        $rate = $rate ?? $this->getRate($from, $to);
        return round($amount * $rate, 2);
    }

    public function isSupported(string $currency): bool
    {
        $rates = $this->getAllRates();
        return isset($rates[strtoupper(trim($currency))]);
    }

    private function fetchAllRatesFromApi(): array
    {
        try {
            $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD');
            if ($response->successful()) {
                $rates = $response->json('rates') ?? [];
                if (!empty($rates) && isset($rates['KES'])) {
                    Log::info('FX rates fetched from open.er-api.com', ['KES' => $rates['KES'], 'UGX' => $rates['UGX'] ?? 'N/A']);
                    return $rates;
                }
            }
            Log::warning('open.er-api.com failed, trying fallback');
        } catch (\Exception $e) {
            Log::error('open.er-api.com exception: ' . $e->getMessage());
        }

        try {
            $response = Http::timeout(5)->get('https://api.exchangerate.host/latest', ['base' => 'USD']);
            if ($response->successful()) {
                $rates = $response->json('rates') ?? [];
                if (!empty($rates)) {
                    Log::info('FX rates fetched from fallback exchangerate.host');
                    return $rates;
                }
            }
        } catch (\Exception $e) {
            Log::error('Fallback FX API exception: ' . $e->getMessage());
        }

        Log::critical('ALL FX APIs failed — using hardcoded fallback rates!');
        return [
            'USD' => 1.0,
            'KES' => 129.50,
            'UGX' => 3750.00,
            'EUR' => 0.92,
            'GBP' => 0.79,
            'ZAR' => 18.50,
            'ETB' => 56.50,
            'TZS' => 2600.00,
            'NGN' => 1580.00,
            'GHS' => 15.50,
        ];
    }
}

<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\LeaveEntitlement;
use App\Observers\LeaveEntitlementObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(\App\Services\HourlyPayCalculator::class);
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $user = auth()->user();

            if ($user) {
                $businessSlug = session('active_business_slug');
                $business = $businessSlug ? Business::findBySlug($businessSlug) : $user->business;

                $managedBusinesses = $user->switchableBusinesses()
                    ->reject(fn ($b) => $business && $b->id === $business->id)
                    ->values();

                $view->with([
                    'currentBusiness' => $business,
                    'managedBusinesses' => $managedBusinesses
                ]);
            } else {
                $view->with([
                    'currentBusiness' => null,
                    'managedBusinesses' => collect()
                ]);
            }
        });
    }

}

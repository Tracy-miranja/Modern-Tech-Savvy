<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\LeaveEntitlement;
use App\Observers\LeaveEntitlementObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\HourlyPayCalculator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $user = auth()->user();

            if ($user) {
                $businessSlug = session('active_business_slug');
                $business = $businessSlug ? Business::findBySlug($businessSlug) : $user->business;

                // "Switch Business" (app-header.blade.php): every OTHER
                // business this account is legitimately attached to, as
                // owner or via an Employee record - NOT
                // $business->managedBusinesses() (the `clients` pivot),
                // which is unrelated/empty and was for a different,
                // defunct mechanism. Distinct from krest-admin's Clients
                // impersonation, which uses its own separate data source.
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

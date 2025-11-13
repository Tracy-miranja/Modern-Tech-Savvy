<?php


namespace App\Providers;

use App\Models\Business;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\LeaveEntitlement;
use App\Observers\LeaveEntitlementObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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

                $managedBusinesses = $business ? $business->managedBusinesses : collect();

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

        LeaveEntitlement::observe(LeaveEntitlementObserver::class);
    }

}

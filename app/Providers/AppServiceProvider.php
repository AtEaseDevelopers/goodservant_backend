<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\EInvoiceService;
use App\Models\Customer;
use App\Models\Driver;
use App\Observers\CustomerObserver;
use App\Observers\DriverObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Blade::if('einvoice', function () {
            return app(EInvoiceService::class)->isEnabled();
        });

        Blade::if('noeinvoice', function () {
            return !app(EInvoiceService::class)->isEnabled();
        });

        // Keep the `assigns` pivot in sync with debtor-type coverage in both
        // directions (driver's debtor_types edited / customer's debtor_type synced).
        Driver::observe(DriverObserver::class);
        Customer::observe(CustomerObserver::class);
    }
}

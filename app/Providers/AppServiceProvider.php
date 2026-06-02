<?php

namespace App\Providers;

use App\Contracts\Billing\CreditLedger;
use App\Services\Billing\DatabaseCreditLedger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CreditLedger::class, DatabaseCreditLedger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

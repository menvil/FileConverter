<?php

namespace App\Providers;

use App\Contracts\Billing\ConversionCostEstimator;
use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use App\Observers\UserObserver;
use App\Services\Billing\ConfigDrivenConversionCostEstimator;
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
        $this->app->bind(ConversionCostEstimator::class, ConfigDrivenConversionCostEstimator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}

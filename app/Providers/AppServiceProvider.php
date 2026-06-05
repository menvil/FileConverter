<?php

namespace App\Providers;

use App\Billing\Gateway\CashierCreditPackCheckoutGateway;
use App\Billing\Gateway\CashierSubscriptionCheckoutGateway;
use App\Billing\Gateway\CreditPackCheckoutGateway;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Contracts\Billing\ConversionCostEstimator;
use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use App\Observers\UserObserver;
use App\Services\Billing\ConfigDrivenConversionCostEstimator;
use App\Services\Billing\DatabaseCreditLedger;
use App\Services\FeatureAccess\FeatureAccessService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Cashier::ignoreRoutes();

        $this->app->bind(CreditLedger::class, DatabaseCreditLedger::class);
        $this->app->bind(ConversionCostEstimator::class, ConfigDrivenConversionCostEstimator::class);
        $this->app->bind(SubscriptionCheckoutGateway::class, CashierSubscriptionCheckoutGateway::class);
        $this->app->bind(CreditPackCheckoutGateway::class, CashierCreditPackCheckoutGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        RateLimiter::for('web-upload', function (Request $request) {
            return Limit::perMinute(20)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        RateLimiter::for('web-conversion-create', function (Request $request) {
            return Limit::perMinute(30)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        RateLimiter::for('api-v1', function (Request $request) {
            $user = $request->user();

            $limit = $user
                ? app(FeatureAccessService::class)->limit($user, 'api_rate_limit_per_minute') ?? 60
                : 30;

            return Limit::perMinute((int) $limit)->by(
                $user?->id ?: $request->ip(),
            );
        });
    }
}

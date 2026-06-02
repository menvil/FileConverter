<?php

namespace App\Http\Controllers\Billing;

use App\Billing\BillingPaymentService;
use App\Billing\BillingPlanRepository;
use App\Billing\Exceptions\CannotCheckoutFreePlanException;
use App\Billing\Exceptions\UnknownBillingPlanException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StartSubscriptionCheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        string $plan,
        BillingPlanRepository $plans,
        BillingPaymentService $service,
    ): RedirectResponse {
        try {
            $billingPlan = $plans->findOrFail($plan);
        } catch (UnknownBillingPlanException) {
            abort(404);
        }

        try {
            $checkout = $service->createSubscriptionCheckout(
                user: $request->user(),
                plan: $billingPlan,
                successUrl: route('billing.success'),
                cancelUrl: route('billing.cancel'),
            );
        } catch (CannotCheckoutFreePlanException) {
            abort(422);
        }

        return redirect($checkout->url);
    }
}

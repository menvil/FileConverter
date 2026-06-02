<?php

namespace App\Http\Controllers\Billing;

use App\Billing\BillingPaymentService;
use App\Http\Controllers\Controller;
use App\Services\Billing\CreditPackRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreditPackCheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        string $pack,
        CreditPackRepository $repository,
        BillingPaymentService $service,
    ): RedirectResponse {
        $creditPack = $repository->find($pack);

        if ($creditPack === null) {
            abort(404);
        }

        $checkout = $service->createCreditPackCheckout(
            user: $request->user(),
            pack: $creditPack,
            successUrl: route('billing.credits.success'),
            cancelUrl: route('billing.credits.cancel'),
        );

        return redirect($checkout->url);
    }
}

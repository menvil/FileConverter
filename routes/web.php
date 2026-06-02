<?php

use App\Http\Controllers\Billing\CashierWebhookController;
use App\Http\Controllers\Billing\CreditPackCheckoutController;
use App\Http\Controllers\Billing\StartSubscriptionCheckoutController;
use App\Http\Controllers\DownloadConversionResultController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', [CashierWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

Route::get('/', function () {
    return view('welcome');
});

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::view('/ui-kit', 'ui-kit')->name('ui-kit');

Route::middleware('auth')->group(function () {
    Route::post('/billing/checkout/{plan}', StartSubscriptionCheckoutController::class)
        ->name('billing.checkout');

    Route::post('/billing/credits/{pack}', CreditPackCheckoutController::class)
        ->name('billing.credits.checkout');

    Route::view('/billing/success', 'billing.success')->name('billing.success');
    Route::view('/billing/cancel', 'billing.cancel')->name('billing.cancel');

    Route::view('/billing/credits/success', 'billing.credits-success')->name('billing.credits.success');
    Route::view('/billing/credits/cancel', 'billing.credits-cancel')->name('billing.credits.cancel');

    Route::get('/conversions/{conversion}/download', DownloadConversionResultController::class)
        ->name('conversions.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

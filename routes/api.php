<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    // Admin and Merchant APIs disabled - using web routes with session auth instead
    // require base_path('routes/v1/admins.php');
    require base_path('routes/v1/app.php');
    // require base_path('routes/v1/merchants.php');
});

// NymCard Webhook (public endpoint, no auth required)
Route::post('/webhooks/nymcard', [App\Http\Controllers\Api\NymCardWebhookController::class, 'handle'])
    ->name('webhooks.nymcard');

// -----------------------------------------------------------------------
// Merchant REST API — authenticated with API keys (Authorization: Bearer epk_live_…)
// -----------------------------------------------------------------------
Route::prefix('v1')->name('merchant.api.')->middleware(['merchant.api.auth', 'throttle:60,1'])->group(function () {
    // Payment links
    Route::post('/payment-links',        [App\Http\Controllers\Api\MerchantApiController::class, 'createPaymentLink'])->name('payment-links.create');
    Route::get('/payment-links/{uuid}',  [App\Http\Controllers\Api\MerchantApiController::class, 'getPaymentLink'])->name('payment-links.show');

    // Payments
    Route::get('/payments',              [App\Http\Controllers\Api\MerchantApiController::class, 'listPayments'])->name('payments.index');
});

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

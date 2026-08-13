<?php
use \Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('app')->name('app.')->group(function () {
    Route::post('login', [App\Http\Controllers\Api\App\AuthController::class, 'loginWithDeviceId'])->name('login');
    Route::post('signup', [App\Http\Controllers\Api\App\AuthController::class, 'signup'])->name('signup');
    Route::post('verify-otp', [App\Http\Controllers\Api\App\AuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('confirm-details', [App\Http\Controllers\Api\App\AuthController::class, 'confirmDetails'])->name('confirm-details')->middleware('auth:app_user');
});

// Protected routes (using Sanctum for app users)
Route::prefix('app')->name('app.')->middleware('auth:app_user')->group(function () {
    // Invoice routes
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('{uuid}', [App\Http\Controllers\Api\App\InvoiceController::class, 'getInvoice'])->name('get');
        Route::post('initiate', [App\Http\Controllers\Api\App\InvoiceController::class, 'initiatePayment'])->name('initiate');
    });
    
    // Payment routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\App\PaymentController::class, 'getPayments'])->name('index');
        Route::get('{id}/receipt', [App\Http\Controllers\Api\App\PaymentController::class, 'getPaymentReceipt'])->name('receipt');
        Route::post('flow/success', [App\Http\Controllers\Api\App\PaymentController::class, 'handleFlowSuccess'])->name('flow.success');
        Route::post('flow/failure', [App\Http\Controllers\Api\App\PaymentController::class, 'handleFlowFailure'])->name('flow.failure');
        Route::post('flow/done', [App\Http\Controllers\Api\App\PaymentController::class, 'handleFlowDone'])->name('flow.done');
    });
});

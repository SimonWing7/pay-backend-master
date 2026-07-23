<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/admin.php';
require __DIR__.'/merchant.php';

// Public invoice payment pages
Route::get('/invoice/{uuid}', [App\Http\Controllers\PublicInvoiceController::class, 'show'])->name('public.invoice.show');
Route::post('/invoice/{uuid}/pay', [App\Http\Controllers\PublicInvoiceController::class, 'pay'])->name('public.invoice.pay');

// Lean return URL (customer lands here after Lean SDK callback redirects)
Route::get('/payment/return', [App\Http\Controllers\PublicInvoiceController::class, 'paymentReturn'])->name('public.payment.return');

// Lean webhook (CSRF excluded in bootstrap/app.php — Lean signs requests with HMAC-SHA256)
Route::post('/webhook/lean', [App\Http\Controllers\LeanWebhookController::class, 'handle'])->name('webhook.lean');

// Developer documentation
Route::get('/developers', function () {
    return view('public.developers');
})->name('public.developers');

// Public product purchase page
Route::get('/product/{uuid}', [App\Http\Controllers\PublicProductController::class, 'show'])->name('public.product');
Route::post('/product/{uuid}', [App\Http\Controllers\PublicProductController::class, 'store'])->name('public.product.store');

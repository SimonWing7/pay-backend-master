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

// NymCard redirect flow return URL (customer lands here after completing payment on NymCard's page)
Route::get('/payment/return', [App\Http\Controllers\PublicInvoiceController::class, 'paymentReturn'])->name('public.payment.return');

// Developer documentation
Route::get('/developers', function () {
    return view('public.developers');
})->name('public.developers');

// Public product purchase page
Route::get('/product/{uuid}', [App\Http\Controllers\PublicProductController::class, 'show'])->name('public.product');
Route::post('/product/{uuid}', [App\Http\Controllers\PublicProductController::class, 'store'])->name('public.product.store');

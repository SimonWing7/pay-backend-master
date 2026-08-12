<?php

use Illuminate\Support\Facades\Route;

// Admin login routes (public)
Route::get('/admin/login', [App\Http\Controllers\Admin\AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [App\Http\Controllers\Admin\AdminController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [App\Http\Controllers\Admin\AdminController::class, 'logout'])->name('admin.logout');

// Admin protected routes
Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('dashboard');
    
    // Merchants management
    Route::prefix('merchants')->name('merchants.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\MerchantController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\MerchantController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Admin\MerchantController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Admin\MerchantController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\MerchantController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\MerchantController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-active', [App\Http\Controllers\Admin\MerchantController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/{id}', [App\Http\Controllers\Admin\MerchantController::class, 'delete'])->name('delete');
    });
    
    // Payments management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('index');
        Route::get('/{id}', [App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('show');
    });
    
    // App Users management
    Route::prefix('app-users')->name('app_users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AppUserController::class, 'index'])->name('index');
        Route::get('/{id}', [App\Http\Controllers\Admin\AppUserController::class, 'show'])->name('show');
    });

    // Referrals management
    Route::prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ReferralController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\Admin\ReferralController::class, 'export'])->name('export');
        Route::post('/{id}/settle', [App\Http\Controllers\Admin\ReferralController::class, 'settle'])->name('settle');
    });
});


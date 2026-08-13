<?php

use Illuminate\Support\Facades\Route;

// Merchant login routes (public)
Route::get('/merchant/login', [App\Http\Controllers\Merchant\MerchantController::class, 'showLoginForm'])->name('merchant.login');
Route::post('/merchant/login', [App\Http\Controllers\Merchant\MerchantController::class, 'login'])->middleware('throttle:5,1')->name('merchant.login.post');
Route::get('/merchant/forgot-password',      [App\Http\Controllers\Merchant\MerchantController::class, 'showForgotPasswordForm'])->name('merchant.password.forgot');
Route::post('/merchant/forgot-password',     [App\Http\Controllers\Merchant\MerchantController::class, 'sendResetLink'])->name('merchant.password.forgot.post');
Route::get('/merchant/reset-password/{token}', [App\Http\Controllers\Merchant\MerchantController::class, 'showResetPasswordForm'])->name('merchant.password.reset');
Route::post('/merchant/reset-password',      [App\Http\Controllers\Merchant\MerchantController::class, 'resetPassword'])->name('merchant.password.reset.post');
Route::post('/merchant/logout', [App\Http\Controllers\Merchant\MerchantController::class, 'logout'])->name('merchant.logout');

// Merchant protected routes
Route::middleware(['auth:merchants', 'merchant.password.change'])->prefix('merchant')->name('merchant.')->group(function () {
    // Password change routes (must be accessible even when password change is required)
    Route::get('/password/change', [App\Http\Controllers\Merchant\MerchantController::class, 'showChangePasswordForm'])->name('password.change')->withoutMiddleware('merchant.password.change');
    Route::post('/password/change', [App\Http\Controllers\Merchant\MerchantController::class, 'changePassword'])->name('password.change.post')->withoutMiddleware('merchant.password.change');
    
    Route::get('/', [App\Http\Controllers\Merchant\MerchantController::class, 'dashboard'])->name('dashboard');
    
    // Products management
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\ProductController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Merchant\ProductController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Merchant\ProductController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Merchant\ProductController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Merchant\ProductController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Merchant\ProductController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-state', [App\Http\Controllers\Merchant\ProductController::class, 'toggleState'])->name('toggle-state');
        Route::delete('/{id}', [App\Http\Controllers\Merchant\ProductController::class, 'delete'])->name('delete');
        Route::get('/{id}/payments/export', [App\Http\Controllers\Merchant\ProductController::class, 'exportPayments'])->name('payments.export');
    });
    
    // Groups management
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\GroupController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Merchant\GroupController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Merchant\GroupController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Merchant\GroupController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Merchant\GroupController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Merchant\GroupController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Merchant\GroupController::class, 'destroy'])->name('delete');
    });
    
    // Consumers management
    Route::prefix('consumers')->name('consumers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\ConsumerController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Merchant\ConsumerController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Merchant\ConsumerController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Merchant\ConsumerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Merchant\ConsumerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Merchant\ConsumerController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Merchant\ConsumerController::class, 'delete'])->name('delete');
    });
    
    // Invoices management
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Merchant\InvoiceController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Merchant\InvoiceController::class, 'store'])->name('store');
        Route::get('/create-bulk', [App\Http\Controllers\Merchant\InvoiceController::class, 'createBulk'])->name('create-bulk');
        Route::post('/store-bulk', [App\Http\Controllers\Merchant\InvoiceController::class, 'storeBulk'])->name('store-bulk');
        Route::get('/{id}', [App\Http\Controllers\Merchant\InvoiceController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Merchant\InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Merchant\InvoiceController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Merchant\InvoiceController::class, 'delete'])->name('delete');
        Route::post('/{id}/archive', [App\Http\Controllers\Merchant\InvoiceController::class, 'archive'])->name('archive');
        Route::post('/{id}/unarchive', [App\Http\Controllers\Merchant\InvoiceController::class, 'unarchive'])->name('unarchive');
    });
    
    // Payments management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\PaymentController::class, 'index'])->name('index');
        Route::get('/export/csv', [App\Http\Controllers\Merchant\PaymentController::class, 'exportCsv'])->name('export.csv');
        Route::get('/{id}', [App\Http\Controllers\Merchant\PaymentController::class, 'show'])->name('show');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [App\Http\Controllers\Merchant\MerchantController::class, 'showProfile'])->name('profile');
        Route::post('/profile', [App\Http\Controllers\Merchant\MerchantController::class, 'updateProfile'])->name('profile.post');
        Route::post('/profile/regenerate-webhook-secret', [App\Http\Controllers\Merchant\MerchantController::class, 'regenerateWebhookSecret'])->name('profile.regenerate-webhook-secret');
        Route::get('/password', [App\Http\Controllers\Merchant\MerchantController::class, 'showSettingsChangePasswordForm'])->name('password');
        Route::post('/password', [App\Http\Controllers\Merchant\MerchantController::class, 'settingsChangePassword'])->name('password.post');
        Route::get('/api-keys', [App\Http\Controllers\Merchant\ApiKeyController::class, 'index'])->name('api-keys');
        Route::post('/api-keys', [App\Http\Controllers\Merchant\ApiKeyController::class, 'store'])->name('api-keys.store');
        Route::delete('/api-keys/{id}', [App\Http\Controllers\Merchant\ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    });
});


// ── Referral tracking ─────────────────────────────────────────────────────────
Route::middleware(['auth:merchants', 'merchant.password.change'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/referrals',        [App\Http\Controllers\Merchant\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/export', [App\Http\Controllers\Merchant\ReferralController::class, 'export'])->name('referrals.export');
});

<?php

use Illuminate\Support\Facades\Route;

// Admin login routes (public)
Route::get('/admin/login', [App\Http\Controllers\Admin\AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [App\Http\Controllers\Admin\AdminController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.post');
Route::post('/admin/logout', [App\Http\Controllers\Admin\AdminController::class, 'logout'])->name('admin.logout');

// 2FA login challenge (public route — reached mid-login, before the admin
// session exists; gated on the pending-id session key set by
// AdminController::login(), not by auth:admin middleware).
Route::get('/admin/two-factor/challenge', [App\Http\Controllers\Admin\TwoFactorController::class, 'showChallenge'])->name('admin.two-factor.challenge');
Route::post('/admin/two-factor/challenge', [App\Http\Controllers\Admin\TwoFactorController::class, 'verifyChallenge'])->middleware('throttle:5,1')->name('admin.two-factor.challenge.post');

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

        // Legal entities (companies/trade licenses) under a merchant — see
        // App\Models\MerchantEntity.
        Route::post('/{merchantId}/entities', [App\Http\Controllers\Admin\MerchantEntityController::class, 'store'])->name('entities.store');
        Route::delete('/{merchantId}/entities/{entityId}', [App\Http\Controllers\Admin\MerchantEntityController::class, 'destroy'])->name('entities.destroy');
    });

    // Create a Lean payment destination directly from the dashboard —
    // replaces the manual Tinker script previously used for this.
    Route::prefix('lean-destinations')->name('lean-destinations.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\LeanDestinationController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\LeanDestinationController::class, 'store'])->name('store');
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
        Route::get('/import', [App\Http\Controllers\Admin\ReferralImportController::class, 'show'])->name('import');
        Route::post('/import', [App\Http\Controllers\Admin\ReferralImportController::class, 'import'])->name('import.store');
        Route::get('/create', [App\Http\Controllers\Admin\ReferralController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Admin\ReferralController::class, 'store'])->name('store');
    });

    // Two-factor authentication settings (managing your own 2FA)
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TwoFactorController::class, 'index'])->name('index');
        Route::get('/setup', [App\Http\Controllers\Admin\TwoFactorController::class, 'showSetup'])->name('setup');
        Route::post('/confirm', [App\Http\Controllers\Admin\TwoFactorController::class, 'confirmSetup'])->name('confirm');
        Route::post('/disable', [App\Http\Controllers\Admin\TwoFactorController::class, 'disable'])->name('disable');
        Route::post('/recovery-codes', [App\Http\Controllers\Admin\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
    });
});

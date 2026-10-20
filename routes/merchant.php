<?php

use Illuminate\Support\Facades\Route;

// Merchant login routes (public)
Route::get('/merchant/login', [App\Http\Controllers\Merchant\MerchantController::class, 'showLoginForm'])->name('merchant.login');
Route::post('/merchant/login', [App\Http\Controllers\Merchant\MerchantController::class, 'login'])->middleware('throttle:5,1')->name('merchant.login.post');
Route::get('/merchant/forgot-password',      [App\Http\Controllers\Merchant\MerchantController::class, 'showForgotPasswordForm'])->name('merchant.password.forgot');
Route::post('/merchant/forgot-password',     [App\Http\Controllers\Merchant\MerchantController::class, 'sendResetLink'])->name('merchant.password.forgot.post');
Route::get('/merchant/reset-password/{token}', [App\Http\Controllers\Merchant\MerchantController::class, 'showResetPasswordForm'])->name('merchant.password.reset');
Route::post('/merchant/reset-password',      [App\Http\Controllers\Merchant\MerchantController::class, 'resetPassword'])->name('merchant.password.reset.post');
Route::get('/merchant/team/accept/{token}',  [App\Http\Controllers\Merchant\TeamAcceptController::class, 'show'])->name('merchant.team.accept');
Route::post('/merchant/team/accept/{token}', [App\Http\Controllers\Merchant\TeamAcceptController::class, 'store'])->middleware('throttle:10,1')->name('merchant.team.accept.post');
Route::post('/merchant/logout', [App\Http\Controllers\Merchant\MerchantController::class, 'logout'])->name('merchant.logout');

// Merchant protected routes
Route::middleware(['auth:merchants', 'merchant.member', 'merchant.password.change'])->prefix('merchant')->name('merchant.')->group(function () {
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

        // Scheduled payment-chase reminders for this product's invoices.
        // Payers: the people who need to pay for this product
        Route::post('/{id}/payers/import', [App\Http\Controllers\Merchant\ProductPayerController::class, 'import'])->name('payers.import');
        Route::post('/{id}/payers', [App\Http\Controllers\Merchant\ProductPayerController::class, 'store'])->name('payers.store');
        Route::post('/{id}/payers/send', [App\Http\Controllers\Merchant\ProductPayerController::class, 'send'])->name('payers.send');
        Route::post('/{id}/payers/retry', [App\Http\Controllers\Merchant\ProductPayerController::class, 'retry'])->name('payers.retry');
        Route::get('/{id}/payers/export', [App\Http\Controllers\Merchant\ProductPayerController::class, 'export'])->name('payers.export');
        Route::get('/{id}/payers/template', [App\Http\Controllers\Merchant\ProductPayerController::class, 'template'])->name('payers.template');
        Route::post('/{id}/payers/{invoiceId}/remove', [App\Http\Controllers\Merchant\ProductPayerController::class, 'remove'])->name('payers.remove');

        Route::post('/{productId}/reminders', [App\Http\Controllers\Merchant\ProductReminderController::class, 'store'])->name('reminders.store');
        Route::delete('/{productId}/reminders/{reminderId}', [App\Http\Controllers\Merchant\ProductReminderController::class, 'destroy'])->name('reminders.destroy');
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
    Route::prefix('invoices')->name('invoices.')->where(['id' => '[0-9]+'])->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Merchant\InvoiceController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Merchant\InvoiceController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Merchant\InvoiceController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Merchant\InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Merchant\InvoiceController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Merchant\InvoiceController::class, 'delete'])->name('delete');
        Route::post('/{id}/archive', [App\Http\Controllers\Merchant\InvoiceController::class, 'archive'])->name('archive');
        Route::post('/{id}/unarchive', [App\Http\Controllers\Merchant\InvoiceController::class, 'unarchive'])->name('unarchive');
        Route::post('/{id}/resend-link', [App\Http\Controllers\Merchant\InvoiceController::class, 'resendLink'])->name('resend-link');
    });
    
    // Billing — Edfundo's monthly fee statements and the card they're charged to
    Route::middleware('merchant.owner')->prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\BillingController::class, 'index'])->name('index');
        Route::post('/card', [App\Http\Controllers\Merchant\BillingController::class, 'startCard'])->name('card.start');
        Route::get('/card/return/{cart}', [App\Http\Controllers\Merchant\BillingController::class, 'cardReturn'])->name('card.return');
        Route::post('/card/{card}/check', [App\Http\Controllers\Merchant\BillingController::class, 'cardCheck'])->name('card.check');
        Route::get('/statements/{number}', [App\Http\Controllers\Merchant\BillingController::class, 'statement'])->name('statement');
    });

    // Payments management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\PaymentController::class, 'index'])->name('index');
        Route::get('/export/csv', [App\Http\Controllers\Merchant\PaymentController::class, 'exportCsv'])->name('export.csv');
        Route::get('/{id}', [App\Http\Controllers\Merchant\PaymentController::class, 'show'])->name('show');
        Route::post('/{id}/confirm', [App\Http\Controllers\Merchant\PaymentController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/reject', [App\Http\Controllers\Merchant\PaymentController::class, 'reject'])->name('reject');
    });
    
    // Settings. The password page is personal to whoever is signed in; the rest
    // belongs to the account owner.
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/password', [App\Http\Controllers\Merchant\MerchantController::class, 'showSettingsChangePasswordForm'])->name('password');
        Route::post('/password', [App\Http\Controllers\Merchant\MerchantController::class, 'settingsChangePassword'])->name('password.post');

        Route::middleware('merchant.owner')->group(function () {
            Route::get('/profile', [App\Http\Controllers\Merchant\MerchantController::class, 'showProfile'])->name('profile');
            Route::post('/profile', [App\Http\Controllers\Merchant\MerchantController::class, 'updateProfile'])->name('profile.post');
            Route::post('/profile/regenerate-webhook-secret', [App\Http\Controllers\Merchant\MerchantController::class, 'regenerateWebhookSecret'])->name('profile.regenerate-webhook-secret');
            Route::get('/api-keys', [App\Http\Controllers\Merchant\ApiKeyController::class, 'index'])->name('api-keys');
            Route::post('/api-keys', [App\Http\Controllers\Merchant\ApiKeyController::class, 'store'])->name('api-keys.store');
            Route::delete('/api-keys/{id}', [App\Http\Controllers\Merchant\ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
        });
    });

    // New-merchant onboarding journey (owners only)
    Route::middleware('merchant.owner')->prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\OnboardingController::class, 'index'])->name('index');
        Route::post('/submit', [App\Http\Controllers\Merchant\OnboardingController::class, 'submit'])->name('submit');
        Route::get('/{step}', [App\Http\Controllers\Merchant\OnboardingController::class, 'show'])->where('step', 'welcome|business|registration|bank|contact|fees|review')->name('show');
        Route::post('/{step}', [App\Http\Controllers\Merchant\OnboardingController::class, 'save'])->where('step', 'welcome|business|registration|bank|contact|fees|review')->name('save');
    });

    // Team: the people who work in this account (owners only)
    Route::middleware('merchant.owner')->prefix('team')->name('team.')->group(function () {
        Route::get('/', [App\Http\Controllers\Merchant\TeamController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Merchant\TeamController::class, 'invite'])->name('invite');
        Route::put('/{id}/role', [App\Http\Controllers\Merchant\TeamController::class, 'updateRole'])->name('role');
        Route::post('/{id}/resend', [App\Http\Controllers\Merchant\TeamController::class, 'resend'])->name('resend');
        Route::delete('/{id}', [App\Http\Controllers\Merchant\TeamController::class, 'destroy'])->name('destroy');
    });
});


// ── Referral tracking ─────────────────────────────────────────────────────────
Route::middleware(['auth:merchants', 'merchant.member', 'merchant.owner', 'merchant.password.change'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/referrals',        [App\Http\Controllers\Merchant\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/export', [App\Http\Controllers\Merchant\ReferralController::class, 'export'])->name('referrals.export');
});

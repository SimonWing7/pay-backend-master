<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\MerchantService;
use App\Services\PaymentService;
use App\Services\WebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService,
        protected PaymentService $paymentService
    ) {
    }

    public function showLoginForm(): View
    {
        return view('merchant.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $merchant = \App\Models\Merchant::where('email', $request->input('email'))->first();
        
        if ($merchant && !$merchant->is_active) {
            return redirect()->back()
                ->withErrors(['email' => 'Your account is not active. Please contact administrator.'])
                ->withInput($request->except('password'));
        }

        $success = $this->merchantService->loginSession(
            $request->input('email'),
            $request->input('password')
        );

        if (!$success) {
            return redirect()->back()
                ->withErrors(['email' => 'Invalid credentials'])
                ->withInput($request->except('password'));
        }

        // Check if merchant must change password
        $merchant = auth('merchants')->user();
        if ($merchant && $merchant->must_change_password) {
            return redirect()->route('merchant.password.change');
        }

        return redirect()->route('merchant.dashboard');
    }

    public function logout(): RedirectResponse
    {
        $this->merchantService->logout();
        return redirect()->route('merchant.login');
    }

    public function dashboard(): View
    {
        $merchant = auth('merchants')->user();
        
        $stats = [
            'products' => $merchant->products()->count(),
            'consumers' => $merchant->consumers()->count(),
            'invoices' => $merchant->invoices()->count(),
            'payments' => \App\Models\AppUserPayment::whereHas('invoice', function ($q) use ($merchant) {
                $q->where('merchant_id', $merchant->id);
            })->count(),
        ];

        $paymentStats = $this->paymentService->getPaymentStatsForMerchant($merchant->id, 30);
        $incomeStats = $this->paymentService->getIncomeStatsForMerchant($merchant->id, 30);
        $totalIncome = $this->paymentService->getTotalIncomeForMerchant($merchant->id);
        $totalIncomeCurrentMonth = $this->paymentService->getTotalIncomeCurrentMonthForMerchant($merchant->id);

        return view('merchant.dashboard', compact('stats', 'paymentStats', 'incomeStats', 'totalIncome', 'totalIncomeCurrentMonth'));
    }

    public function showChangePasswordForm(): View
    {
        return view('merchant.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']));
        }

        $merchant = auth('merchants')->user();

        // Verify current password
        if (!\Illuminate\Support\Facades\Hash::check($request->input('current_password'), $merchant->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Current password is incorrect'])
                ->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']));
        }

        // Update password and set must_change_password to false
        $this->merchantService->updatePassword($merchant, $request->input('new_password'), false);

        return redirect()->route('merchant.dashboard')
            ->with('success', 'Password changed successfully');
    }

    public function showProfile(): View
    {
        $merchant = auth('merchants')->user();
        return view('merchant.settings.profile', compact('merchant'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_trading_name'  => 'required|string|max:255',
            'support_email'          => 'nullable|email|max:255',
            'support_phone'          => 'nullable|string|max:50',
            'website'                => 'nullable|url|max:255',
            'webhook_url'            => 'nullable|url|max:2048',
            'logo'                   => 'nullable|image|mimes:jpeg,jpg,png,svg,webp|max:2048',
            // Fallback payment method
            'fallback_type'          => 'nullable|in:,payment_gateway,bank_transfer',
            'fallback_payment_url'   => 'nullable|url|max:2048',
            'fallback_bank_name'     => 'nullable|string|max:255',
            'fallback_account_name'  => 'nullable|string|max:255',
            'fallback_reference_note'=> 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $merchant = auth('merchants')->user();
        $data     = $validator->validated();

        // Normalise empty string to null for fallback_type
        if (isset($data['fallback_type']) && $data['fallback_type'] === '') {
            $data['fallback_type'] = null;
        }

        // Clear gateway URL when not using payment_gateway
        if (($data['fallback_type'] ?? null) !== 'payment_gateway') {
            $data['fallback_payment_url'] = null;
        }

        // Clear transfer fields when not using bank_transfer
        if (($data['fallback_type'] ?? null) !== 'bank_transfer') {
            $data['fallback_bank_name']      = null;
            $data['fallback_account_name']   = null;
            $data['fallback_reference_note'] = null;
        }

        // Auto-generate a webhook secret the first time a webhook URL is set
        if (!empty($data['webhook_url']) && empty($merchant->webhook_secret)) {
            $data['webhook_secret'] = WebhookService::generateSecret();
        }

        // If webhook URL is cleared, remove the secret too
        if (empty($data['webhook_url'])) {
            $data['webhook_secret'] = null;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($merchant->logo_path) {
                Storage::disk('public')->delete($merchant->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($merchant->logo_path) {
                Storage::disk('public')->delete($merchant->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);

        $merchant->update($data);

        return redirect()->route('merchant.settings.profile')
            ->with('success', 'Settings updated successfully.');
    }

    public function regenerateWebhookSecret(Request $request): RedirectResponse
    {
        $merchant = auth('merchants')->user();

        if (empty($merchant->webhook_url)) {
            return redirect()->route('merchant.settings.profile')
                ->with('error', 'Set a webhook URL before generating a secret.');
        }

        $merchant->update(['webhook_secret' => WebhookService::generateSecret()]);

        return redirect()->route('merchant.settings.profile')
            ->with('success', 'Webhook secret regenerated. Update your server with the new value.');
    }

    public function showSettingsChangePasswordForm(): View
    {
        return view('merchant.settings.password');
    }

    public function settingsChangePassword(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']));
        }

        $merchant = auth('merchants')->user();

        // Verify current password
        if (!\Illuminate\Support\Facades\Hash::check($request->input('current_password'), $merchant->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Current password is incorrect'])
                ->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']));
        }

        // Update password (keep must_change_password as is, since this is voluntary change)
        $this->merchantService->updatePassword($merchant, $request->input('new_password'), $merchant->must_change_password);

        return redirect()->route('merchant.settings.password')
            ->with('success', 'Password changed successfully');
    }

    public function showForgotPasswordForm(): View
    {
        return view('merchant.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('merchants')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'If that email is registered, a reset link is on its way.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('merchant.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::broker('merchants')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($merchant, $password) {
                $merchant->forceFill([
                    'password'             => Hash::make($password),
                    'remember_token'       => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                event(new PasswordReset($merchant));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('merchant.login')->with('status', 'Password reset successfully. Please sign in.')
            : back()->withErrors(['email' => [__($status)]]);
    }

}
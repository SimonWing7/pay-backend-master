<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\MerchantService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

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
}


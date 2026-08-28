<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
        protected PaymentService $paymentService
    ) {
    }

    public function showLoginForm(): View
    {
        return view('admin.login');
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

        $admin = $this->adminService->attemptCredentials(
            $request->input('email'),
            $request->input('password')
        );

        if (!$admin) {
            return redirect()->back()
                ->withErrors(['email' => 'Invalid credentials'])
                ->withInput($request->except('password'));
        }

        if ($admin->hasTwoFactorEnabled()) {
            // Not logged in yet — stash the pending admin id and route
            // through the 2FA challenge instead of establishing a session.
            $request->session()->put('admin_2fa_pending_id', $admin->id);
            return redirect()->route('admin.two-factor.challenge');
        }

        $this->adminService->completeLogin($admin);

        return redirect()->route('admin.dashboard');
    }

    public function logout(): RedirectResponse
    {
        $this->adminService->logout();
        return redirect()->route('admin.login');
    }

    public function dashboard(): View
    {
        $paymentStats = $this->paymentService->getPaymentStats(30);
        $totalAmountAllTime = $this->paymentService->getTotalAmountAllTime();
        $totalAmountCurrentMonth = $this->paymentService->getTotalAmountCurrentMonth();
        $statusBreakdown = $this->paymentService->getStatusBreakdown();
        $merchantBreakdown = $this->paymentService->getMerchantBreakdown(10);

        return view('admin.dashboard', compact('paymentStats', 'totalAmountAllTime', 'totalAmountCurrentMonth', 'statusBreakdown', 'merchantBreakdown'));
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Services\AppUserService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
        protected AppUserService $appUserService,
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

        $success = $this->adminService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (!$success) {
            return redirect()->back()
                ->withErrors(['email' => 'Invalid credentials'])
                ->withInput($request->except('password'));
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout(): RedirectResponse
    {
        $this->adminService->logout();
        return redirect()->route('admin.login');
    }

    public function dashboard(): View
    {
        $installationStats = $this->appUserService->getInstallationStats(30);
        $paymentStats = $this->paymentService->getPaymentStats(30);
        $totalAmountAllTime = $this->paymentService->getTotalAmountAllTime();
        $totalAmountCurrentMonth = $this->paymentService->getTotalAmountCurrentMonth();
        
        return view('admin.dashboard', compact('installationStats', 'paymentStats', 'totalAmountAllTime', 'totalAmountCurrentMonth'));
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(
        protected AdminService $adminService
    ) {
    }

    // -------------------------------------------------------------------
    // Login challenge — reached after correct email/password when the
    // account has 2FA enabled. Not behind the auth:admin middleware,
    // since the admin isn't logged in yet; gated on the pending-id
    // session key set by AdminController::login() instead.
    // -------------------------------------------------------------------

    public function showChallenge(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('admin_2fa_pending_id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.two-factor.challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $pendingId = $request->session()->get('admin_2fa_pending_id');

        if (!$pendingId) {
            return redirect()->route('admin.login');
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $admin = Admin::find($pendingId);

        if (!$admin || !$this->adminService->verifyTwoFactorCode($admin, $request->input('code'))) {
            return redirect()->back()->withErrors(['code' => 'Invalid or expired code']);
        }

        $request->session()->forget('admin_2fa_pending_id');
        // Regenerate the session id — this request ran without a fully
        // authenticated session, so we don't want to carry that
        // pre-auth session forward into the logged-in one.
        $request->session()->regenerate();

        $this->adminService->completeLogin($admin);

        return redirect()->route('admin.dashboard');
    }

    // -------------------------------------------------------------------
    // Settings — behind auth:admin, for an already-logged-in admin
    // managing their own 2FA.
    // -------------------------------------------------------------------

    public function index(): View
    {
        return view('admin.two-factor.index', [
            'admin' => auth('admin')->user(),
        ]);
    }

    public function showSetup(Request $request): View|RedirectResponse
    {
        $admin = auth('admin')->user();

        if ($admin->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.index');
        }

        $setup = $this->adminService->generateTwoFactorSetup($admin);

        // Stashed only long enough to confirm — never written to the
        // admin record until the code is verified.
        $request->session()->put('admin_2fa_setup_secret', $setup['secret']);

        // getQRCodeInline() returns the data-URI value itself, but
        // whether it already includes the "data:" scheme prefix isn't
        // guaranteed across library versions — normalize here rather
        // than assume. What we saw in testing was already base64 text
        // with the prefix missing, not raw unencoded SVG — so this only
        // ever prepends the prefix, never re-encodes.
        $qr = $setup['qr'];
        if (!str_starts_with($qr, 'data:')) {
            $qr = 'data:image/svg+xml;base64,' . $qr;
        }

        return view('admin.two-factor.setup', [
            'qr' => $qr,
            'secret' => $setup['secret'],
        ]);
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        $secret = $request->session()->get('admin_2fa_setup_secret');

        if (!$secret) {
            return redirect()->route('admin.two-factor.index')
                ->with('error', 'Setup session expired — please start again.');
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $recoveryCodes = $this->adminService->confirmTwoFactor(
            auth('admin')->user(),
            $secret,
            $request->input('code')
        );

        if (!$recoveryCodes) {
            return redirect()->back()->withErrors(['code' => 'Invalid code — check your authenticator app and try again']);
        }

        $request->session()->forget('admin_2fa_setup_secret');

        return redirect()->route('admin.two-factor.index')
            ->with('recovery_codes', $recoveryCodes)
            ->with('success', 'Two-factor authentication is now enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $admin = auth('admin')->user();

        if (!Hash::check($request->input('password'), $admin->password)) {
            return redirect()->back()->withErrors(['password' => 'Incorrect password']);
        }

        $this->adminService->disableTwoFactor($admin);

        return redirect()->route('admin.two-factor.index')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(): RedirectResponse
    {
        $recoveryCodes = $this->adminService->regenerateRecoveryCodes(auth('admin')->user());

        return redirect()->route('admin.two-factor.index')
            ->with('recovery_codes', $recoveryCodes)
            ->with('success', 'New recovery codes generated — your old codes no longer work.');
    }
}

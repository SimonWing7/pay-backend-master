<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(private AdminService $adminService)
    {
    }

    // -------------------------------------------------------------------
    // Authenticated — managing admin users (auth:admin)
    // -------------------------------------------------------------------

    public function index(): View
    {
        $admins = Admin::orderBy('created_at', 'desc')->get();
        return view('admin.users.index', compact('admins'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $token = Str::random(48);

        $admin = Admin::create([
            'name'         => $request->input('name'),
            'email'        => $request->input('email'),
            // Nobody knows this — it's replaced the moment the invite is
            // accepted. Admin::password has no default/nullable option at
            // the DB level, so a real (if unusable) hash is needed here
            // rather than leaving it blank.
            'password'     => Hash::make(Str::random(40)),
            'role'         => 'admin',
            'invited_by'   => $request->user()->id,
            'invite_token' => $token,
            'invited_at'   => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Invite created for {$admin->name}.")
            ->with('invite_link', route('admin.invite.show', $token));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if ($request->user()->id === $id) {
            return redirect()->route('admin.users.index')
                ->with('error', "You can't remove your own account.");
        }

        $admin = Admin::findOrFail($id);
        $admin->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "{$admin->name} removed.");
    }

    // -------------------------------------------------------------------
    // Public — accepting an invite (no auth yet, that's the point)
    // -------------------------------------------------------------------

    public function showInvite(string $token): View
    {
        $admin = Admin::where('invite_token', $token)->firstOrFail();

        if (!$admin->isPendingInvite()) {
            abort(404);
        }

        return view('admin.invite-accept', compact('admin', 'token'));
    }

    public function acceptInvite(Request $request, string $token): RedirectResponse
    {
        $admin = Admin::where('invite_token', $token)->firstOrFail();

        if (!$admin->isPendingInvite()) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $admin->update([
            'password'           => $request->input('password'),
            'invite_accepted_at' => now(),
        ]);

        // This request ran on the pre-auth invite-token session, so
        // regenerate before authenticating (same reasoning as the 2FA
        // challenge path in TwoFactorController::verifyChallenge()).
        $request->session()->regenerate();
        $this->adminService->completeLogin($admin);

        // RequireTwoFactorForInvitedAdmins enforces this too, but send them
        // straight there so they never see the dashboard first.
        return redirect()->route('admin.two-factor.setup')
            ->with('status', 'Your password is set. Please set up two-factor authentication to continue.');
    }
}

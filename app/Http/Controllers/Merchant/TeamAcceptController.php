<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public: the page an invited person (or someone resetting) lands on from their email. */
class TeamAcceptController extends Controller
{
    public function show(string $token): View
    {
        $member = MerchantUser::findByToken($token);

        return view('merchant.team-accept', [
            'member' => $member,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse|View
    {
        $member = MerchantUser::findByToken($token);

        if (!$member || !$member->merchant || !$member->merchant->is_active) {
            return view('merchant.team-accept', ['member' => null, 'token' => $token]);
        }

        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        // The token is single-use: clear it as the password is set.
        $member->forceFill([
            'password' => $data['password'],
            'setup_token_hash' => null,
            'setup_token_expires_at' => null,
        ])->save();

        return redirect()->route('merchant.login')
            ->with('status', 'Password set. Sign in with ' . $member->email . ' to get started.');
    }
}

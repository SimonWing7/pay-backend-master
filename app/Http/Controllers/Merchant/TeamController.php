<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Mail\TeamInviteMail;
use App\Models\Merchant;
use App\Models\MerchantUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Account owners inviting and managing the people who work in their account. */
class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $merchant = $request->user();
        $members = $merchant->members()->orderBy('created_at')->get();

        return view('merchant.settings.team', [
            'merchant' => $merchant,
            'members' => $members,
            'roles' => MerchantUser::ROLES,
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $merchant = $request->user();

        // Emails are matched case-insensitively everywhere, so store and compare lowercase.
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc', 'max:255', 'unique:merchant_users,email'],
            'role' => ['required', Rule::in(array_keys(MerchantUser::ROLES))],
        ], [
            'email.unique' => 'That email address already has access to an Edfundo Pay account.',
        ]);

        // The owner logins live in `merchants`; sign-in checks that table first,
        // so the same address as a member would be unreachable.
        $validator->after(function ($v) use ($request) {
            if (Merchant::withTrashed()->whereRaw('LOWER(email) = ?', [$request->input('email')])->exists()) {
                $v->errors()->add('email', 'That email address already has access to an Edfundo Pay account.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'invite')->withInput();
        }

        $data = $validator->validated();
        $actor = $request->attributes->get('merchant_member')?->name ?? $merchant->name;

        $member = $merchant->members()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'invited_at' => now(),
            'invited_by' => $actor,
        ]);

        if (!$this->sendSetupEmail($member, 'invite')) {
            return redirect()->route('merchant.team.index')
                ->with('error', "{$member->name} was added, but the invitation email couldn't be sent. Use Resend invite to try again.");
        }

        return redirect()->route('merchant.team.index')
            ->with('success', "Invitation sent to {$member->email}. The link works for 7 days.");
    }

    public function updateRole(Request $request, int $id): RedirectResponse
    {
        $member = $this->findMember($request, $id);

        $data = $request->validate(['role' => ['required', Rule::in(array_keys(MerchantUser::ROLES))]]);

        $member->update(['role' => $data['role']]);

        return redirect()->route('merchant.team.index')
            ->with('success', "{$member->name} is now {$member->roleLabel()}.");
    }

    public function resend(Request $request, int $id): RedirectResponse
    {
        $member = $this->findMember($request, $id);

        if (!$member->isPending()) {
            return redirect()->route('merchant.team.index')
                ->with('error', "{$member->name} has already accepted. They can use Forgot password on the sign-in page if they need a new one.");
        }

        $member->forceFill(['invited_at' => now()])->save();

        return $this->sendSetupEmail($member, 'invite')
            ? redirect()->route('merchant.team.index')->with('success', "A new invitation was sent to {$member->email}. Any earlier link no longer works.")
            : redirect()->route('merchant.team.index')->with('error', "The invitation email couldn't be sent. Please try again.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $member = $this->findMember($request, $id);
        $member->delete();

        return redirect()->route('merchant.team.index')
            ->with('success', "{$member->name} no longer has access.");
    }

    /** Only ever one of this merchant's own members; anything else is a 404. */
    private function findMember(Request $request, int $id): MerchantUser
    {
        return $request->user()->members()->findOrFail($id);
    }

    private function sendSetupEmail(MerchantUser $member, string $kind): bool
    {
        $token = $member->issueSetupToken(MerchantUser::INVITE_VALID_HOURS);

        try {
            Mail::to($member->email)->send(new TeamInviteMail($member, $token, $kind));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Team invite email failed', ['member_id' => $member->id, 'error' => $e->getMessage()]);

            return false;
        }
    }
}

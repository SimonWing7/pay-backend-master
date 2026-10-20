<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MerchantService extends Service
{
    public function login(string $email, string $password): ?Merchant
    {
        $merchant = Merchant::where('email', $email)->first();

        if (!$merchant || !Hash::check($password, $merchant->password)) {
            return null;
        }

        if (!$merchant->is_active) {
            return null;
        }

        return $merchant;
    }

    public function loginSession(string $email, string $password): bool
    {
        $merchant = Merchant::where('email', $email)->first();

        if ($merchant) {
            if (!Hash::check($password, $merchant->password) || !$merchant->is_active) {
                return false;
            }

            $this->startSession($merchant, null);
            return true;
        }

        // Not an account owner's login: try the people they've invited.
        $member = MerchantUser::where('email', $email)->whereNotNull('password')->first();

        if (!$member || !Hash::check($password, $member->password)) {
            return false;
        }

        $parent = $member->merchant;

        if (!$parent || $parent->trashed() || !$parent->is_active) {
            return false;
        }

        $member->forceFill(['last_login_at' => now()])->save();
        $this->startSession($parent, $member);

        return true;
    }

    /**
     * Sign in as the merchant account, remembering which member (if any) is
     * behind it. Always overwrites any earlier member id so a previous
     * person's role can't leak into the next sign-in on the same browser.
     */
    private function startSession(Merchant $merchant, ?MerchantUser $member): void
    {
        Auth::guard('merchants')->login($merchant);

        if (app()->bound('session.store') && request()->hasSession()) {
            request()->session()->regenerate();

            if ($member) {
                request()->session()->put('merchant_member_id', $member->id);
            } else {
                request()->session()->forget('merchant_member_id');
            }
        }
    }

    public function logout(): void
    {
        Auth::guard('merchants')->logout();

        if (request()->hasSession()) {
            request()->session()->forget('merchant_member_id');
        }
    }

    public function getAll(array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 15)
    {
        $query = Merchant::withTrashed();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('iban', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['onboarding'])) {
            $query->where('onboarding_status', $filters['onboarding']);
        }

        // Apply sorting
        $allowedSorts = ['name', 'email', 'created_at', 'updated_at', 'is_active'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination
        return $query->paginate($perPage)->withQueryString();
    }

    public function getById(int $id): ?Merchant
    {
        return Merchant::withTrashed()->find($id);
    }

    public function create(array $data): Merchant
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // New merchants must change an admin-set password, unless they are
        // choosing their own through the welcome email.
        $data['must_change_password'] = $data['must_change_password'] ?? true;

        return Merchant::create($data);
    }

    public function update(Merchant $merchant, array $data): Merchant
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }else{
            unset($data['password']);
        }

        $merchant->update($data);
        return $merchant->fresh();
    }

    public function delete(Merchant $merchant): bool
    {
        return $merchant->delete();
    }

    public function updatePassword(Merchant $merchant, string $newPassword, bool $mustChangePassword = false): Merchant
    {
        $merchant->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => $mustChangePassword,
        ]);

        return $merchant->fresh();
    }
}


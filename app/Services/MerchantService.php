<?php

namespace App\Services;

use App\Models\Merchant;
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

        if (!$merchant || !Hash::check($password, $merchant->password)) {
            return false;
        }

        if (!$merchant->is_active) {
            return false;
        }

        Auth::guard('merchants')->login($merchant);
        return true;
    }

    public function logout(): void
    {
        Auth::guard('merchants')->logout();
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

        // Set must_change_password to true for new merchants
        $data['must_change_password'] = true;

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


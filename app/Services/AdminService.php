<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminService extends Service
{
    public function login(string $email, string $password): bool
    {
        $admin = Admin::where('email', $email)->first();

        if (!$admin || !Hash::check($password, $admin->password)) {
            return false;
        }

        Auth::guard('admin')->login($admin);
        return true;
    }

    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }
}


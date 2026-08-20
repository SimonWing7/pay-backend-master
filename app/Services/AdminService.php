<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminService extends Service
{
    public function __construct(
        protected TwoFactorService $twoFactor
    ) {
    }

    /**
     * Verify email/password only — does not establish a session. Caller
     * decides whether to complete login directly or route through a 2FA
     * challenge first, based on hasTwoFactorEnabled().
     */
    public function attemptCredentials(string $email, string $password): ?Admin
    {
        $admin = Admin::where('email', $email)->first();

        if (!$admin || !Hash::check($password, $admin->password)) {
            return null;
        }

        return $admin;
    }

    public function completeLogin(Admin $admin): void
    {
        Auth::guard('admin')->login($admin);
    }

    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }

    public function verifyTwoFactorCode(Admin $admin, string $code): bool
    {
        $code = trim($code);

        if ($this->twoFactor->verifyKey($admin->two_factor_secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($admin, $code);
    }

    /**
     * @return array{secret: string, qr: string}
     */
    public function generateTwoFactorSetup(Admin $admin): array
    {
        $secret = $this->twoFactor->generateSecretKey();

        return [
            'secret' => $secret,
            'qr' => $this->twoFactor->getQrCodeInline('Edfundo Pay Admin', $admin->email, $secret),
        ];
    }

    /**
     * Verifies the code against a secret that hasn't been saved yet (the
     * one just shown on the setup screen). On success, saves it and
     * generates recovery codes.
     *
     * @return array<int, string>|null Plaintext recovery codes to show
     *  once, or null if the code didn't verify.
     */
    public function confirmTwoFactor(Admin $admin, string $secret, string $code): ?array
    {
        if (!$this->twoFactor->verifyKey($secret, trim($code))) {
            return null;
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $admin->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        return $recoveryCodes;
    }

    public function disableTwoFactor(Admin $admin): void
    {
        $admin->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * @return array<int, string> Plaintext codes to show once.
     */
    public function regenerateRecoveryCodes(Admin $admin): array
    {
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $admin->update(['two_factor_recovery_codes' => $recoveryCodes]);

        return $recoveryCodes;
    }

    private function consumeRecoveryCode(Admin $admin, string $code): bool
    {
        $codes = $admin->two_factor_recovery_codes ?? [];
        $index = array_search(strtoupper($code), $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $admin->update(['two_factor_recovery_codes' => array_values($codes)]);

        return true;
    }
}

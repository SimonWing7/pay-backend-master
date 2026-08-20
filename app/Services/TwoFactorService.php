<?php

namespace App\Services;

use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorService extends Service
{
    protected Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA();
    }

    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function getQrCodeInline(string $companyName, string $email, string $secret): string
    {
        return $this->engine->getQRCodeInline($companyName, $email, $secret);
    }

    public function verifyKey(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code);
    }

    /**
     * Plaintext codes to show once — caller is responsible for storing
     * them (encrypted) and never displaying them again after this.
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(bin2hex(random_bytes(5))))
            ->map(fn (string $code) => substr($code, 0, 5) . '-' . substr($code, 5))
            ->all();
    }
}

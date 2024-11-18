<?php

namespace App\Shared\Infrastructure\Security\TwoFactor;

use PragmaRX\Google2FA\Google2FA;

class GoogleAuthenticator implements TwoFactorAuthInterface
{
    private Google2FA $google2fa;
    private string $issuer;

    public function __construct(string $issuer)
    {
        $this->google2fa = new Google2FA();
        $this->issuer = $issuer;
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQrCodeUrl(string $username, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            $this->issuer,
            $username,
            $secret
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = bin2hex(random_bytes(10));
        }
        return $codes;
    }
} 
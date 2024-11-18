<?php
namespace App\Shared\Infrastructure\Security\TwoFactor;

interface TwoFactorAuthInterface
{
    public function generateSecret(): string;
    public function getQrCodeUrl(string $username, string $secret): string;
    public function verify(string $secret, string $code): bool;
    public function generateRecoveryCodes(): array;
} 
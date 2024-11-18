<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Client\Encryption;

class EncryptedCredentials
{
    private array $encryptedData;
    private string $encryptedKey;
    private string $fingerprint;

    public function __construct(
        array $encryptedData,
        string $encryptedKey,
        string $fingerprint
    ) {
        $this->encryptedData = $encryptedData;
        $this->encryptedKey = $encryptedKey;
        $this->fingerprint = $fingerprint;
    }

    public function getEncryptedData(): array
    {
        return $this->encryptedData;
    }

    public function getEncryptedKey(): string
    {
        return $this->encryptedKey;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function toJson(): string
    {
        return json_encode($this->encryptedData);
    }

    public static function fromJson(string $json, string $key, string $fingerprint): self
    {
        return new self(
            json_decode($json, true),
            $key,
            $fingerprint
        );
    }
} 
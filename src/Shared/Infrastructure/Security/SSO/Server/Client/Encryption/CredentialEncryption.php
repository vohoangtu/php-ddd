<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Client\Encryption;

use App\Shared\Infrastructure\Security\Encryption\EncryptionInterface;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\EncryptionKey;

class CredentialEncryption
{
    private EncryptionKey $masterKey;
    private array $config;

    public function __construct(string $masterKeyPath, array $config = [])
    {
        $this->masterKey = $this->loadOrCreateMasterKey($masterKeyPath);
        $this->config = array_merge([
            'algorithm' => 'aes-256-gcm',
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 4
        ], $config);
    }

    public function encryptCredentials(array $credentials): EncryptedCredentials
    {
        $keyPair = $this->generateKeyPair();
        
        $encryptedData = [
            'client_secret' => $this->encryptValue($credentials['client_secret'], $keyPair['encryption_key']),
            'additional_keys' => isset($credentials['additional_keys']) ? 
                $this->encryptValue($credentials['additional_keys'], $keyPair['encryption_key']) : null,
            'metadata' => isset($credentials['metadata']) ?
                $this->encryptValue(json_encode($credentials['metadata']), $keyPair['encryption_key']) : null
        ];

        return new EncryptedCredentials(
            $encryptedData,
            $this->encryptKey($keyPair['encryption_key']),
            $this->generateFingerprint($credentials)
        );
    }

    public function decryptCredentials(EncryptedCredentials $credentials): array
    {
        $encryptionKey = $this->decryptKey($credentials->getEncryptedKey());
        
        return [
            'client_secret' => $this->decryptValue($credentials->getEncryptedData()['client_secret'], $encryptionKey),
            'additional_keys' => $credentials->getEncryptedData()['additional_keys'] ? 
                $this->decryptValue($credentials->getEncryptedData()['additional_keys'], $encryptionKey) : null,
            'metadata' => $credentials->getEncryptedData()['metadata'] ?
                json_decode($this->decryptValue($credentials->getEncryptedData()['metadata'], $encryptionKey), true) : null,
            'fingerprint' => $credentials->getFingerprint()
        ];
    }

    private function generateKeyPair(): array
    {
        $encryptionKey = KeyFactory::generateEncryptionKey();
        $signingKey = KeyFactory::generateSigningKey();

        return [
            'encryption_key' => $encryptionKey,
            'signing_key' => $signingKey
        ];
    }

    private function encryptValue(string $value, EncryptionKey $key): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);
        
        $encrypted = sodium_crypto_aead_chacha20poly1305_encrypt(
            $value,
            $nonce,
            $nonce,
            $key->getRawKeyMaterial()
        );

        return base64_encode($nonce . $encrypted);
    }

    private function decryptValue(string $encrypted, EncryptionKey $key): string
    {
        $decoded = base64_decode($encrypted);
        $nonce = substr($decoded, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);

        return sodium_crypto_aead_chacha20poly1305_decrypt(
            $ciphertext,
            $nonce,
            $nonce,
            $key->getRawKeyMaterial()
        );
    }

    private function encryptKey(EncryptionKey $key): string
    {
        return sodium_crypto_box_seal(
            $key->getRawKeyMaterial(),
            $this->masterKey->getRawKeyMaterial()
        );
    }

    private function decryptKey(string $encryptedKey): EncryptionKey
    {
        $keyMaterial = sodium_crypto_box_seal_open(
            $encryptedKey,
            $this->masterKey->getRawKeyMaterial()
        );

        return new EncryptionKey($keyMaterial);
    }

    private function generateFingerprint(array $credentials): string
    {
        return hash_hmac(
            'sha256',
            json_encode($credentials),
            $this->masterKey->getRawKeyMaterial()
        );
    }

    private function loadOrCreateMasterKey(string $path): EncryptionKey
    {
        if (file_exists($path)) {
            return KeyFactory::loadEncryptionKey($path);
        }

        $key = KeyFactory::generateEncryptionKey();
        KeyFactory::save($key, $path);
        return $key;
    }
} 
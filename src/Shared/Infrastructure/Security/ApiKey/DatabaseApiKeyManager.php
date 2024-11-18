<?php

namespace App\Shared\Infrastructure\Security\ApiKey;

use App\Shared\Infrastructure\Database\DatabaseInterface;
use App\Shared\Infrastructure\Security\Encryption\EncryptionInterface;

class DatabaseApiKeyManager implements ApiKeyManagerInterface
{
    private DatabaseInterface $database;
    private EncryptionInterface $encryption;
    private array $config;

    public function __construct(
        DatabaseInterface $database,
        EncryptionInterface $encryption,
        array $config = []
    ) {
        $this->database = $database;
        $this->encryption = $encryption;
        $this->config = array_merge([
            'table' => 'api_keys',
            'key_prefix' => 'sk_',
            'hash_algo' => 'sha256',
        ], $config);
    }

    public function createKey(string $name, array $scopes = []): ApiKey
    {
        $key = $this->generateApiKey();
        $hashedKey = $this->hashKey($key);

        $keyData = [
            'name' => $name,
            'key_hash' => $hashedKey,
            'scopes' => json_encode($scopes),
            'last_used_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => $this->calculateExpiry(),
        ];

        $id = $this->database->table($this->config['table'])->insertGetId($keyData);

        return new ApiKey($id, $key, $name, $scopes);
    }

    public function validateKey(string $key): bool
    {
        $hashedKey = $this->hashKey($key);
        
        $keyInfo = $this->database->table($this->config['table'])
            ->where('key_hash', $hashedKey)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->first();

        if ($keyInfo) {
            $this->updateKeyUsage($keyInfo->id);
            return true;
        }

        return false;
    }

    private function generateApiKey(): string
    {
        return $this->config['key_prefix'] . bin2hex(random_bytes(24));
    }

    private function hashKey(string $key): string
    {
        return hash($this->config['hash_algo'], $key);
    }

    private function updateKeyUsage(int $keyId): void
    {
        $this->database->table($this->config['table'])
            ->where('id', $keyId)
            ->update([
                'last_used_at' => date('Y-m-d H:i:s'),
                'usage_count' => $this->database->raw('usage_count + 1'),
            ]);
    }

    public function getKeyInfo(string $key): ?ApiKeyInfo
    {
        $hashedKey = $this->hashKey($key);
        
        $keyData = $this->database->table($this->config['table'])
            ->where('key_hash', $hashedKey)
            ->first();

        if (!$keyData) {
            return null;
        }

        return new ApiKeyInfo(
            $keyData->id,
            $keyData->name,
            json_decode($keyData->scopes, true),
            $keyData->created_at,
            $keyData->last_used_at,
            $keyData->expires_at
        );
    }
} 
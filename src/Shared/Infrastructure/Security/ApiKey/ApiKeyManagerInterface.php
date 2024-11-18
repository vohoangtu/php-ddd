<?php

namespace App\Shared\Infrastructure\Security\ApiKey;

interface ApiKeyManagerInterface
{
    public function createKey(string $name, array $scopes = []): ApiKey;
    public function validateKey(string $key): bool;
    public function revokeKey(string $key): void;
    public function getKeyInfo(string $key): ?ApiKeyInfo;
    public function listKeys(int $userId): array;
} 
<?php
namespace App\Shared\Infrastructure\Security\SSO;

interface SSOProviderInterface
{
    public function initialize(array $config): void;
    public function authenticate(string $returnUrl): string;
    public function handleCallback(array $params): SSOUserData;
    public function logout(string $returnUrl): string;
    public function validateSession(): bool;
    public function getMetadata(): array;
} 
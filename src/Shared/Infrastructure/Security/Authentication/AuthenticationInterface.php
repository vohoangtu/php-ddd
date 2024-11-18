<?php

namespace App\Shared\Infrastructure\Security\Authentication;

interface AuthenticationInterface
{
    public function authenticate(string $username, string $password): ?AuthTokenInterface;
    public function validateToken(string $token): bool;
    public function refreshToken(string $refreshToken): ?AuthTokenInterface;
    public function revokeToken(string $token): void;
    public function getCurrentUser(): ?UserInterface;
} 
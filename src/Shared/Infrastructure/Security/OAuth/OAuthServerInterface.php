<?php
namespace App\Shared\Infrastructure\Security\OAuth;

interface OAuthServerInterface
{
    public function authorize(array $params): AuthorizationResponse;
    public function token(array $params): TokenResponse;
    public function validateToken(string $token): ?TokenInfo;
    public function revokeToken(string $token): void;
    public function getUserInfo(string $token): ?array;
} 
<?php

namespace App\Shared\Infrastructure\Api;

use App\User\Infrastructure\UserRepository;

class ApiAuthMiddleware
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function handle(): void
    {
        $token = $this->getBearerToken();
        
        if (!$token) {
            ApiResponse::error('Unauthorized', 401);
        }

        $user = $this->userRepository->findByApiToken($token);
        if (!$user) {
            ApiResponse::error('Invalid token', 401);
        }

        // Store user in request
        $_REQUEST['auth_user'] = $user;
    }

    private function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }
} 
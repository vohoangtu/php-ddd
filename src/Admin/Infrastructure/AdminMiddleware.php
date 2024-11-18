<?php

namespace App\Admin\Infrastructure;

use App\User\Application\AuthService;

class AdminMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(): void
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        if (!$this->authService->isAdmin()) {
            header('Location: /');
            exit;
        }
    }
} 
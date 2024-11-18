<?php

namespace App\Shared\Infrastructure;

class AuthMiddleware
{
    public function handle($next)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        return $next();
    }
} 
<?php

namespace App\User\Application;

use Jenssegers\Blade\Blade;

class AuthController
{
    private AuthService $authService;
    private Blade $blade;

    public function __construct(AuthService $authService, Blade $blade)
    {
        $this->authService = $authService;
        $this->blade = $blade;
    }

    public function showLoginForm()
    {
        if ($this->authService->isLoggedIn()) {
            header('Location: /');
            exit;
        }

        echo $this->blade->make('auth.login')->render();
    }

    public function login()
    {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($this->authService->login($email, $password)) {
            if ($this->authService->isAdmin()) {
                header('Location: /admin/dashboard');
            } else {
                header('Location: /');
            }
            exit;
        }

        $_SESSION['error'] = 'Invalid credentials';
        header('Location: /login');
        exit;
    }

    public function logout()
    {
        $this->authService->logout();
        header('Location: /login');
        exit;
    }
} 
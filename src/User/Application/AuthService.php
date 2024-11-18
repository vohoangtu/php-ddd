<?php

namespace App\User\Application;

use App\User\Infrastructure\UserRepository;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);
        
        if ($user && password_verify($password, $user->getPassword())) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_role'] = $user->getRole();
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        session_destroy();
    }

    public function getCurrentUser()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return $this->userRepository->findById($_SESSION['user_id']);
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin(): bool
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
} 
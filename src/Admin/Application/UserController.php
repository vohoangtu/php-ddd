<?php

namespace App\Admin\Application;

use App\User\Infrastructure\UserRepository;
use Jenssegers\Blade\Blade;

class UserController
{
    private UserRepository $userRepository;
    private Blade $blade;

    public function __construct(UserRepository $userRepository, Blade $blade)
    {
        $this->userRepository = $userRepository;
        $this->blade = $blade;
    }

    public function index()
    {
        $users = $this->userRepository->findAll();
        echo $this->blade->make('admin.users.index', [
            'users' => $users
        ])->render();
    }

    public function create()
    {
        echo $this->blade->make('admin.users.create')->render();
    }

    public function store()
    {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'password' => $_POST['password'] ?? '',
            'role' => filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING)
        ];

        if (!$data['name'] || !$data['email'] || !$data['password']) {
            $_SESSION['error'] = 'All fields are required';
            header('Location: /admin/users/create');
            exit;
        }

        if ($this->userRepository->findByEmail($data['email'])) {
            $_SESSION['error'] = 'Email already exists';
            header('Location: /admin/users/create');
            exit;
        }

        $this->userRepository->create($data);
        $_SESSION['success'] = 'User created successfully';
        header('Location: /admin/users');
        exit;
    }

    public function edit(int $id)
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }

        echo $this->blade->make('admin.users.edit', [
            'user' => $user
        ])->render();
    }

    public function update(int $id)
    {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'role' => filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING)
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        if (!$data['name'] || !$data['email']) {
            $_SESSION['error'] = 'Name and email are required';
            header("Location: /admin/users/$id/edit");
            exit;
        }

        $existingUser = $this->userRepository->findByEmail($data['email']);
        if ($existingUser && $existingUser->getId() !== $id) {
            $_SESSION['error'] = 'Email already exists';
            header("Location: /admin/users/$id/edit");
            exit;
        }

        $this->userRepository->update($id, $data);
        $_SESSION['success'] = 'User updated successfully';
        header('Location: /admin/users');
        exit;
    }

    public function delete(int $id)
    {
        $this->userRepository->delete($id);
        $_SESSION['success'] = 'User deleted successfully';
        header('Location: /admin/users');
        exit;
    }
} 
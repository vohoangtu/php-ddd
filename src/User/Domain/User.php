<?php

namespace App\User\Domain;

class User
{
    private int $id;
    private string $email;
    private string $password;
    private string $role;
    private string $name;

    public function __construct(
        int $id,
        string $email,
        string $password,
        string $role,
        string $name
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->name = $name;
    }

    public function getId(): int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRole(): string { return $this->role; }
    public function getName(): string { return $this->name; }
    public function isAdmin(): bool { return $this->role === 'admin'; }
} 
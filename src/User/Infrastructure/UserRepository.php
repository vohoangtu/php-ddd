<?php

namespace App\User\Infrastructure;

use App\User\Domain\User;
use Illuminate\Database\Capsule\Manager as DB;

class UserRepository
{
    public function findAll(): array
    {
        $users = DB::table('users')
            ->orderBy('created_at', 'desc')
            ->get();

        return array_map(function($user) {
            return $this->mapToUser($user);
        }, $users->all());
    }

    public function findById(int $id): ?User
    {
        $user = DB::table('users')->find($id);
        return $user ? $this->mapToUser($user) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $user = DB::table('users')
            ->where('email', $email)
            ->first();
        return $user ? $this->mapToUser($user) : null;
    }

    public function create(array $data): int
    {
        return DB::table('users')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'user',
            'created_at' => now()
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'user',
            'updated_at' => now()
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return DB::table('users')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('users')->delete($id) > 0;
    }

    private function mapToUser($data): User
    {
        return new User(
            $data->id,
            $data->email,
            $data->password,
            $data->role,
            $data->name
        );
    }
} 
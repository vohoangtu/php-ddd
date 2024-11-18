<?php

namespace App\Shared\Infrastructure\Authorization;

use App\Shared\Domain\Authorization\AuthorizationInterface;
use App\Shared\Infrastructure\Cache\CacheService;
use App\User\Domain\Entity\User;
use Illuminate\Support\Facades\DB;

class Authorization implements AuthorizationInterface
{
    private ?User $user;
    private CacheService $cache;
    private array $permissions = [];
    private array $roles = [];

    public function __construct(?User $user, CacheService $cache)
    {
        $this->user = $user;
        $this->cache = $cache;
        
        if ($user) {
            $this->loadUserPermissions();
        }
    }

    public function can(string $permission, $resource = null): bool
    {
        if (!$this->user) {
            return false;
        }

        // Super admin has all permissions
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Check direct permission
        if (isset($this->permissions[$permission])) {
            return $this->checkPermission($permission, $resource);
        }

        // Check role-based permissions
        foreach ($this->roles as $role) {
            if ($this->checkRolePermission($role, $permission, $resource)) {
                return true;
            }
        }

        return false;
    }

    public function cannot(string $permission, $resource = null): bool
    {
        return !$this->can($permission, $resource);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($roles, $this->roles));
    }

    private function loadUserPermissions(): void
    {
        $cacheKey = "user:{$this->user->getId()}:permissions";
        
        $this->permissions = $this->cache->remember($cacheKey, function () {
            return $this->loadPermissionsFromDatabase();
        }, 'auth', 3600);

        $this->roles = $this->cache->remember(
            "user:{$this->user->getId()}:roles",
            function () {
                return $this->loadRolesFromDatabase();
            },
            'auth',
            3600
        );
    }

    private function checkPermission(string $permission, $resource = null): bool
    {
        $permissionData = $this->permissions[$permission];

        if ($resource === null) {
            return true;
        }

        // Check resource-specific conditions
        if (is_callable($permissionData['conditions'] ?? null)) {
            return $permissionData['conditions']($resource, $this->user);
        }

        return true;
    }

    private function checkRolePermission(string $role, string $permission, $resource = null): bool
    {
        $rolePermissions = $this->cache->remember(
            "role:{$role}:permissions",
            function () use ($role) {
                return $this->loadRolePermissionsFromDatabase($role);
            },
            'auth',
            3600
        );

        return isset($rolePermissions[$permission]) &&
            $this->checkPermission($permission, $resource);
    }

    private function loadPermissionsFromDatabase(): array
    {
        return DB::table('user_permissions')
            ->select('permissions.name', 'user_permissions.conditions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_id', $this->user->getId())
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->name => [
                    'conditions' => json_decode($item->conditions, true)
                ]];
            })
            ->all();
    }

    private function loadRolesFromDatabase(): array
    {
        return DB::table('user_roles')
            ->select('roles.name')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_id', $this->user->getId())
            ->pluck('name')
            ->all();
    }

    private function loadRolePermissionsFromDatabase(string $role): array
    {
        return DB::table('role_permissions')
            ->select('permissions.name', 'role_permissions.conditions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->where('roles.name', $role)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->name => [
                    'conditions' => json_decode($item->conditions, true)
                ]];
            })
            ->all();
    }
} 
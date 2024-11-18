<?php

namespace App\Shared\Infrastructure\Security\Authorization;

use App\Shared\Infrastructure\Security\Authentication\AuthenticationInterface;

class RbacAuthorization implements AuthorizationInterface
{
    private AuthenticationInterface $auth;
    private array $policies = [];
    private array $roleHierarchy;

    public function __construct(
        AuthenticationInterface $auth,
        array $roleHierarchy = []
    ) {
        $this->auth = $auth;
        $this->roleHierarchy = $roleHierarchy;
    }

    public function can(string $permission, array $context = []): bool
    {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            return false;
        }

        if (isset($this->policies[$permission])) {
            return $this->policies[$permission]($user, $context);
        }

        foreach ($user->getRoles() as $role) {
            if ($this->checkRolePermission($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function addPolicy(string $permission, callable $callback): void
    {
        $this->policies[$permission] = $callback;
    }

    private function checkRolePermission(string $role, string $permission): bool
    {
        $roles = $this->getInheritedRoles($role);
        foreach ($roles as $inheritedRole) {
            if (isset($this->roleHierarchy[$inheritedRole]['permissions'])
                && in_array($permission, $this->roleHierarchy[$inheritedRole]['permissions'])
            ) {
                return true;
            }
        }
        return false;
    }

    private function getInheritedRoles(string $role): array
    {
        $roles = [$role];
        if (isset($this->roleHierarchy[$role]['inherits'])) {
            foreach ($this->roleHierarchy[$role]['inherits'] as $inheritedRole) {
                $roles = array_merge($roles, $this->getInheritedRoles($inheritedRole));
            }
        }
        return array_unique($roles);
    }

    public function hasAction(string $permission): bool
    {
        // Check if permission exists in any role
        foreach ($this->roleHierarchy as $role) {
            if (isset($role['permissions']) && in_array($permission, $role['permissions'])) {
                return true;
            }
        }
        
        // Check if permission has a custom policy
        return isset($this->policies[$permission]);
    }
} 
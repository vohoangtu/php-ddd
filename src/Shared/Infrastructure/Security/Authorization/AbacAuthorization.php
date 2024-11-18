<?php

namespace App\Shared\Infrastructure\Security\Authorization;

use App\Shared\Infrastructure\Security\Authentication\AuthenticationInterface;
use Illuminate\Database\Capsule\Manager as DB;

class AbacAuthorization implements AuthorizationInterface 
{
    private AuthenticationInterface $auth;
    private array $cache = [];

    public function __construct(AuthenticationInterface $auth) 
    {
        $this->auth = $auth;
    }

    public function can(string $action, array $context = []): bool 
    {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            return false;
        }

        $cacheKey = $this->generateCacheKey($user->getId(), $action, $context);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $policies = $this->getApplicablePolicies($action, $context);
        
        foreach ($policies as $policy) {
            $decision = $this->evaluatePolicy($policy, $user, $context);
            if ($decision !== null) {
                $this->cache[$cacheKey] = $decision;
                return $decision;
            }
        }

        return false;
    }

    private function evaluatePolicy(object $policy, object $user, array $context): ?bool 
    {
        $conditions = json_decode($policy->conditions, true);
        $attributes = $this->gatherAttributes($user, $context);
        
        try {
            $result = $this->evaluateConditions($conditions, $attributes);
            return $policy->effect === 'allow' ? $result : !$result;
        } catch (\Exception $e) {
            // Log error
            return null;
        }
    }

    private function gatherAttributes(object $user, array $context): array 
    {
        $attributes = [
            'user' => $this->getUserAttributes($user->getId()),
            'resource' => $this->getResourceAttributes($context['resource_id'] ?? null),
            'environment' => $this->getEnvironmentAttributes(),
            'context' => $context
        ];

        // Add dynamic attributes
        $dynamicAttributes = $this->getDynamicAttributes($user->getId(), $context);
        foreach ($dynamicAttributes as $key => $value) {
            $attributes[$key] = $value;
        }

        return $attributes;
    }

    private function getApplicablePolicies(string $action, array $context): array 
    {
        $query = DB::table('abac_policies')
            ->where('is_active', true)
            ->where(function($q) use ($action, $context) {
                $q->where('action', $action);
                if (isset($context['resource_type'])) {
                    $q->where('resource_type_id', function($sq) use ($context) {
                        $sq->select('id')
                          ->from('resource_types')
                          ->where('name', $context['resource_type']);
                    });
                }
            })
            ->orderBy('priority', 'desc')
            ->get();

        return $query->all();
    }

    private function getUserAttributes(int $userId): array 
    {
        return DB::table('user_attributes')
            ->select('attributes.name', 'user_attributes.value')
            ->join('attributes', 'attributes.id', '=', 'user_attributes.attribute_id')
            ->where('user_id', $userId)
            ->pluck('value', 'name')
            ->all();
    }

    private function getResourceAttributes(?int $resourceId): array 
    {
        if (!$resourceId) {
            return [];
        }

        return DB::table('resources')
            ->where('id', $resourceId)
            ->value('attribute_values') ?? [];
    }

    private function getEnvironmentAttributes(): array 
    {
        $attributes = DB::table('environment_attributes')
            ->pluck('value', 'name')
            ->all();

        // Add standard environment attributes
        $attributes['time'] = date('H:i');
        $attributes['dayOfWeek'] = date('N');
        $attributes['clientIp'] = $_SERVER['REMOTE_ADDR'] ?? null;

        return $attributes;
    }

    private function getDynamicAttributes(int $userId, array $context): array 
    {
        $providers = DB::table('dynamic_attribute_providers')
            ->where('is_active', true)
            ->get();

        $attributes = [];
        foreach ($providers as $provider) {
            $providerClass = $provider->provider_class;
            if (class_exists($providerClass)) {
                $instance = new $providerClass(json_decode($provider->config, true));
                $attributes = array_merge(
                    $attributes,
                    $instance->getAttributes($userId, $context)
                );
            }
        }

        return $attributes;
    }

    private function evaluateConditions(array $conditions, array $attributes): bool 
    {
        if (isset($conditions['all'])) {
            foreach ($conditions['all'] as $condition) {
                if (!$this->evaluateCondition($condition, $attributes)) {
                    return false;
                }
            }
            return true;
        }

        if (isset($conditions['any'])) {
            foreach ($conditions['any'] as $condition) {
                if ($this->evaluateCondition($condition, $attributes)) {
                    return true;
                }
            }
            return false;
        }

        return $this->evaluateCondition($conditions, $attributes);
    }

    private function evaluateCondition(array $condition, array $attributes): bool 
    {
        $value = $this->resolveAttributePath($condition['attribute'], $attributes);
        $targetValue = $this->resolveAttributePath($condition['value'], $attributes);

        return match ($condition['operator']) {
            'equals' => $value === $targetValue,
            'not_equals' => $value !== $targetValue,
            'greater_than' => $value > $targetValue,
            'less_than' => $value < $targetValue,
            'contains' => is_array($value) && in_array($targetValue, $value),
            'starts_with' => str_starts_with($value, $targetValue),
            'ends_with' => str_ends_with($value, $targetValue),
            'between' => $value >= $targetValue[0] && $value <= $targetValue[1],
            'in' => in_array($value, (array)$targetValue),
            default => false,
        };
    }

    private function resolveAttributePath(string $path, array $attributes): mixed 
    {
        $parts = explode('.', $path);
        $current = $attributes;

        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    private function generateCacheKey(int $userId, string $action, array $context): string 
    {
        return md5(json_encode([$userId, $action, $context]));
    }

    public function hasAction(string $action): bool
    {
        return DB::table('abac_policies')
            ->where('action', $action)
            ->where('is_active', true)
            ->exists();
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function addPolicy(string $action, array $policy): void
    {
        DB::table('abac_policies')->insert(array_merge(
            $policy,
            [
                'action' => $action,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ));
        
        // Clear cache for this action
        foreach ($this->cache as $key => $value) {
            if (strpos($key, $action) !== false) {
                unset($this->cache[$key]);
            }
        }
    }
} 
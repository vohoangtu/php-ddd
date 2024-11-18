<?php

namespace App\Shared\Infrastructure\Security\Authorization;

use App\Shared\Infrastructure\Security\Authentication\AuthenticationInterface;
use InvalidArgumentException;
use RuntimeException;

class HybridAuthorization implements AuthorizationInterface
{
    private RbacAuthorization $rbac;
    private AbacAuthorization $abac;
    private array $config;
    private array $strategyCache = [];

    public function __construct(
        AuthenticationInterface $auth,
        array $config = []
    ) {
        $this->validateConfig($config);
        
        $this->rbac = new RbacAuthorization($auth, $config['role_hierarchy'] ?? []);
        $this->abac = new AbacAuthorization($auth);
        $this->config = array_merge([
            'default_strategy' => 'any', // 'any' or 'all'
            'action_strategies' => [], // Override strategy for specific actions
            'cache_enabled' => true,
            'cache_ttl' => 3600, // 1 hour
            'strict_mode' => false, // If true, throws exceptions for undefined actions
        ], $config);
    }

    public function can(string $action, array $context = []): bool
    {
        try {
            if ($this->config['strict_mode'] && !$this->isActionDefined($action)) {
                throw new InvalidArgumentException("Undefined action: $action");
            }

            $cacheKey = $this->generateCacheKey($action, $context);
            if ($this->config['cache_enabled'] && isset($this->strategyCache[$cacheKey])) {
                return $this->strategyCache[$cacheKey];
            }

            $strategy = $this->config['action_strategies'][$action] 
                ?? $this->config['default_strategy'];

            $result = match ($strategy) {
                'all' => $this->rbac->can($action, $context) && $this->abac->can($action, $context),
                'any' => $this->rbac->can($action, $context) || $this->abac->can($action, $context),
                'rbac_only' => $this->rbac->can($action, $context),
                'abac_only' => $this->abac->can($action, $context),
                default => throw new InvalidArgumentException("Unknown strategy: $strategy")
            };

            if ($this->config['cache_enabled']) {
                $this->strategyCache[$cacheKey] = $result;
            }

            return $result;
        } catch (InvalidArgumentException $e) {
            if ($this->config['strict_mode']) {
                throw $e;
            }
            return false;
        } catch (\Exception $e) {
            throw new RuntimeException("Authorization check failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function clearCache(): void
    {
        $this->strategyCache = [];
    }

    public function addActionStrategy(string $action, string $strategy): void
    {
        if (!in_array($strategy, ['all', 'any', 'rbac_only', 'abac_only'])) {
            throw new InvalidArgumentException("Invalid strategy: $strategy");
        }
        
        $this->config['action_strategies'][$action] = $strategy;
        $this->clearActionFromCache($action);
    }

    public function removeActionStrategy(string $action): void
    {
        unset($this->config['action_strategies'][$action]);
        $this->clearActionFromCache($action);
    }

    public function getEffectiveStrategy(string $action): string
    {
        return $this->config['action_strategies'][$action] 
            ?? $this->config['default_strategy'];
    }

    public function setDefaultStrategy(string $strategy): void
    {
        if (!in_array($strategy, ['all', 'any', 'rbac_only', 'abac_only'])) {
            throw new InvalidArgumentException("Invalid strategy: $strategy");
        }
        
        $this->config['default_strategy'] = $strategy;
        $this->clearCache(); // Clear all cache as default strategy affects all unconfigured actions
    }

    private function validateConfig(array $config): void
    {
        if (isset($config['default_strategy']) && 
            !in_array($config['default_strategy'], ['all', 'any', 'rbac_only', 'abac_only'])) {
            throw new InvalidArgumentException(
                "Invalid default strategy: {$config['default_strategy']}"
            );
        }

        if (isset($config['action_strategies'])) {
            foreach ($config['action_strategies'] as $action => $strategy) {
                if (!in_array($strategy, ['all', 'any', 'rbac_only', 'abac_only'])) {
                    throw new InvalidArgumentException("Invalid strategy for action $action: $strategy");
                }
            }
        }
    }

    private function generateCacheKey(string $action, array $context): string
    {
        return md5($action . serialize($context));
    }

    private function clearActionFromCache(string $action): void
    {
        if (!$this->config['cache_enabled']) {
            return;
        }

        foreach ($this->strategyCache as $key => $value) {
            if (strpos($key, md5($action)) === 0) {
                unset($this->strategyCache[$key]);
            }
        }
    }

    private function isActionDefined(string $action): bool
    {
        // Check if action is defined in either RBAC or ABAC configurations
        try {
            return $this->rbac->hasAction($action) || $this->abac->hasAction($action);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getRbac(): RbacAuthorization
    {
        return $this->rbac;
    }

    public function getAbac(): AbacAuthorization
    {
        return $this->abac;
    }
} 
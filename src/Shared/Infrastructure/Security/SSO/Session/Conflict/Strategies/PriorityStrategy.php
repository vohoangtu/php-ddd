<?php

namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict\Strategies;

class PriorityStrategy implements ResolutionStrategyInterface
{
    private array $priorityRules;
    private array $devicePriorities;

    public function __construct(array $config = [])
    {
        $this->priorityRules = $config['priority_rules'] ?? [];
        $this->devicePriorities = $config['device_priorities'] ?? [];
    }

    public function resolve(array $changes, array $context): array
    {
        $resolved = [];
        $metadata = ['applied_rules' => []];

        foreach ($changes as $change) {
            $priority = $this->calculatePriority($change, $context);
            
            if (!isset($resolved[$change['key']]) || 
                $priority > $resolved[$change['key']]['priority']) {
                $resolved[$change['key']] = [
                    'value' => $change['value'],
                    'priority' => $priority,
                    'rule_applied' => $change['rule'] ?? 'default'
                ];
            }
        }

        return [
            'data' => array_map(function($item) {
                return $item['value'];
            }, $resolved),
            'metadata' => $metadata,
            'resolution_type' => 'priority'
        ];
    }

    private function calculatePriority(array $change, array $context): int
    {
        $priority = 0;

        // Device priority
        $deviceId = $change['device_id'] ?? null;
        if ($deviceId && isset($this->devicePriorities[$deviceId])) {
            $priority += $this->devicePriorities[$deviceId] * 1000;
        }

        // Rule-based priority
        foreach ($this->priorityRules as $rule => $value) {
            if ($this->matchesRule($change, $rule)) {
                $priority += $value;
            }
        }

        // Time-based priority
        $priority += (int)($change['timestamp'] * 100);

        return $priority;
    }

    private function matchesRule(array $change, string $rule): bool
    {
        switch ($rule) {
            case 'admin_device':
                return ($change['device_type'] ?? '') === 'admin';
            case 'secure_location':
                return $this->isSecureLocation($change['location'] ?? null);
            case 'business_hours':
                return $this->isBusinessHours($change['timestamp']);
            default:
                return false;
        }
    }
} 
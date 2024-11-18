<?php
namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict\Strategies;

class CustomResolutionStrategy implements ResolutionStrategyInterface
{
    private array $resolvers = [];
    private array $rules = [];

    public function addResolver(
        string $type,
        callable $resolver,
        array $rules = []
    ): void {
        $this->resolvers[$type] = $resolver;
        $this->rules[$type] = $rules;
    }

    public function resolve(array $changes, array $context): array
    {
        $resolved = [];
        $metadata = [
            'applied_rules' => [],
            'resolution_types' => []
        ];

        foreach ($changes as $change) {
            $type = $this->determineChangeType($change);
            
            if (isset($this->resolvers[$type])) {
                $result = $this->applyCustomResolver(
                    $type,
                    $change,
                    $changes,
                    $context
                );
                
                $resolved[$change['key']] = $result['value'];
                $metadata['applied_rules'][] = $type;
                $metadata['resolution_types'][] = $result['type'];
            } else {
                // Fallback to default resolution
                $resolved[$change['key']] = $this->defaultResolution($change);
                $metadata['resolution_types'][] = 'default';
            }
        }

        return [
            'data' => $resolved,
            'metadata' => $metadata,
            'resolution_type' => 'custom'
        ];
    }

    private function applyCustomResolver(
        string $type,
        array $change,
        array $allChanges,
        array $context
    ): array {
        $resolver = $this->resolvers[$type];
        $rules = $this->rules[$type];

        // Validate against rules
        if (!$this->validateRules($change, $rules)) {
            throw new ConflictException(
                "Change validation failed for type: {$type}"
            );
        }

        return $resolver($change, $allChanges, $context);
    }

    private function validateRules(array $change, array $rules): bool
    {
        foreach ($rules as $rule => $config) {
            switch ($rule) {
                case 'required_fields':
                    if (!$this->validateRequiredFields($change, $config)) {
                        return false;
                    }
                    break;
                    
                case 'value_constraints':
                    if (!$this->validateValueConstraints($change, $config)) {
                        return false;
                    }
                    break;
                    
                case 'time_constraints':
                    if (!$this->validateTimeConstraints($change, $config)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    private function determineChangeType(array $change): string
    {
        if (isset($change['type'])) {
            return $change['type'];
        }

        // Determine type based on value structure
        $value = $change['value'];
        
        if (is_array($value)) {
            return 'array';
        } elseif (is_object($value)) {
            return 'object';
        } else {
            return gettype($value);
        }
    }
} 
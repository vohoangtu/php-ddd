<?php
namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict\Strategies;

class SemanticMergeStrategy implements ResolutionStrategyInterface
{
    private array $mergeRules;
    private array $validators;

    public function __construct()
    {
        $this->initializeMergeRules();
        $this->initializeValidators();
    }

    public function resolve(array $changes, array $context): array
    {
        $grouped = $this->groupChangesByEntity($changes);
        $resolved = [];

        foreach ($grouped as $entity => $entityChanges) {
            $resolved[$entity] = $this->mergeEntityChanges(
                $entity,
                $entityChanges,
                $context
            );
        }

        return [
            'data' => $resolved,
            'metadata' => [
                'merged_entities' => array_keys($grouped),
                'applied_rules' => $this->getAppliedRules($resolved)
            ],
            'resolution_type' => 'semantic_merge'
        ];
    }

    private function mergeEntityChanges(
        string $entity,
        array $changes,
        array $context
    ): array {
        $mergeRule = $this->mergeRules[$entity] ?? 'default';
        $merged = [];

        switch ($mergeRule) {
            case 'aggregate':
                $merged = $this->mergeAggregateValues($changes);
                break;
            case 'state_machine':
                $merged = $this->mergeStateMachineTransitions($changes);
                break;
            case 'composite':
                $merged = $this->mergeCompositeStructure($changes);
                break;
            default:
                $merged = $this->mergeDefault($changes);
        }

        // Validate merged result
        if (!$this->validateMergedResult($entity, $merged)) {
            throw new ConflictException(
                "Invalid merge result for entity: {$entity}"
            );
        }

        return $merged;
    }

    private function mergeAggregateValues(array $changes): array
    {
        $result = [];
        
        foreach ($changes as $change) {
            $field = $change['field'];
            $value = $change['value'];

            if (!isset($result[$field])) {
                $result[$field] = $value;
                continue;
            }

            // Aggregate based on field type
            if (is_numeric($value)) {
                $result[$field] = $this->aggregateNumeric(
                    $result[$field],
                    $value,
                    $change['aggregate_type'] ?? 'sum'
                );
            } elseif (is_array($value)) {
                $result[$field] = $this->aggregateArray(
                    $result[$field],
                    $value,
                    $change['aggregate_type'] ?? 'union'
                );
            }
        }

        return $result;
    }

    private function mergeStateMachineTransitions(array $changes): array
    {
        $transitions = [];
        $finalState = null;

        // Sort changes by timestamp
        usort($changes, function($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        foreach ($changes as $change) {
            $from = $change['from_state'];
            $to = $change['to_state'];

            // Validate transition
            if ($finalState && $from !== $finalState) {
                continue; // Invalid transition
            }

            $transitions[] = [
                'from' => $from,
                'to' => $to,
                'timestamp' => $change['timestamp']
            ];

            $finalState = $to;
        }

        return [
            'current_state' => $finalState,
            'transitions' => $transitions
        ];
    }
} 
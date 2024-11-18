<?php

namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict\Strategies;

class MergeStrategy implements ResolutionStrategyInterface
{
    public function resolve(array $changes, array $context): array
    {
        $merged = [];
        $conflicts = [];

        foreach ($changes as $change) {
            $key = $change['key'];
            
            if (!isset($merged[$key])) {
                $merged[$key] = $change;
                continue;
            }

            if ($this->canMerge($merged[$key], $change)) {
                $merged[$key] = $this->mergeChanges(
                    $merged[$key],
                    $change,
                    $context
                );
            } else {
                $conflicts[] = [
                    'key' => $key,
                    'changes' => [$merged[$key], $change]
                ];
            }
        }

        return [
            'data' => $this->extractValues($merged),
            'conflicts' => $conflicts,
            'resolution_type' => 'merge',
            'merged_fields' => array_keys($merged)
        ];
    }

    private function canMerge(array $existing, array $new): bool
    {
        // Check if changes are within merge threshold
        $timeDiff = abs($existing['timestamp'] - $new['timestamp']);
        return $timeDiff <= $this->config['merge_threshold'];
    }

    private function mergeChanges(
        array $change1,
        array $change2,
        array $context
    ): array {
        $type = $this->determineDataType($change1['value'], $change2['value']);
        
        switch ($type) {
            case 'array':
                return $this->mergeArrays($change1, $change2);
            case 'object':
                return $this->mergeObjects($change1, $change2);
            case 'string':
                return $this->mergeStrings($change1, $change2);
            default:
                return $this->resolveByTimestamp($change1, $change2);
        }
    }

    private function mergeArrays(array $change1, array $change2): array
    {
        $merged = array_unique(array_merge(
            (array)$change1['value'],
            (array)$change2['value']
        ));

        return [
            'value' => $merged,
            'timestamp' => max($change1['timestamp'], $change2['timestamp']),
            'merged' => true
        ];
    }

    private function mergeObjects(array $change1, array $change2): array
    {
        $merged = [];
        $obj1 = (array)$change1['value'];
        $obj2 = (array)$change2['value'];

        foreach (array_keys($obj1 + $obj2) as $key) {
            if (!isset($obj2[$key])) {
                $merged[$key] = $obj1[$key];
            } elseif (!isset($obj1[$key])) {
                $merged[$key] = $obj2[$key];
            } else {
                $merged[$key] = $this->resolveByTimestamp(
                    ['value' => $obj1[$key], 'timestamp' => $change1['timestamp']],
                    ['value' => $obj2[$key], 'timestamp' => $change2['timestamp']]
                )['value'];
            }
        }

        return [
            'value' => $merged,
            'timestamp' => max($change1['timestamp'], $change2['timestamp']),
            'merged' => true
        ];
    }
} 
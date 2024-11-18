<?php

namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict\Strategies;

class OperationalTransformStrategy implements ResolutionStrategyInterface
{
    private array $operations = [];
    private array $history = [];

    public function resolve(array $changes, array $context): array
    {
        $operations = $this->convertToOperations($changes);
        $transformed = [];
        
        foreach ($operations as $op) {
            // Transform against all previous operations
            foreach ($transformed as $existingOp) {
                $op = $this->transform($op, $existingOp);
            }
            $transformed[] = $op;
        }

        return [
            'data' => $this->applyOperations($transformed),
            'operations' => $transformed,
            'resolution_type' => 'operational_transform'
        ];
    }

    private function transform(array $op1, array $op2): array
    {
        switch ($op1['type']) {
            case 'insert':
                return $this->transformInsert($op1, $op2);
            case 'delete':
                return $this->transformDelete($op1, $op2);
            case 'update':
                return $this->transformUpdate($op1, $op2);
            default:
                throw new ConflictException("Unknown operation type: {$op1['type']}");
        }
    }

    private function transformInsert(array $op1, array $op2): array
    {
        if ($op2['type'] === 'insert' && $op2['position'] <= $op1['position']) {
            return array_merge($op1, [
                'position' => $op1['position'] + strlen($op2['value'])
            ]);
        }
        
        if ($op2['type'] === 'delete' && $op2['position'] < $op1['position']) {
            return array_merge($op1, [
                'position' => $op1['position'] - $op2['length']
            ]);
        }

        return $op1;
    }

    private function applyOperations(array $operations): array
    {
        $result = [];
        
        foreach ($operations as $op) {
            switch ($op['type']) {
                case 'insert':
                    $result = $this->applyInsert($result, $op);
                    break;
                case 'delete':
                    $result = $this->applyDelete($result, $op);
                    break;
                case 'update':
                    $result = $this->applyUpdate($result, $op);
                    break;
            }
        }

        return $result;
    }
} 
<?php

namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict\Strategies;

class CRDTStrategy implements ResolutionStrategyInterface
{
    private array $crdts = [];

    public function resolve(array $changes, array $context): array
    {
        $resolved = [];

        foreach ($changes as $change) {
            $crdtType = $this->determineCRDTType($change);
            $crdt = $this->getCRDTInstance($crdtType, $change['key']);
            
            $crdt->apply($change);
            $resolved[$change['key']] = $crdt->getValue();
        }

        return [
            'data' => $resolved,
            'metadata' => [
                'crdt_types' => array_unique(array_column($changes, 'crdt_type')),
                'timestamp' => microtime(true)
            ],
            'resolution_type' => 'crdt'
        ];
    }

    private function determineCRDTType(array $change): string
    {
        if (isset($change['crdt_type'])) {
            return $change['crdt_type'];
        }

        // Auto-detect CRDT type based on data structure
        if (is_array($change['value'])) {
            return 'observed-remove-set';
        } elseif (is_numeric($change['value'])) {
            return 'pn-counter';
        } else {
            return 'last-write-wins-register';
        }
    }

    private function getCRDTInstance(string $type, string $key): CRDTInterface
    {
        if (!isset($this->crdts[$key])) {
            $this->crdts[$key] = $this->createCRDT($type);
        }
        return $this->crdts[$key];
    }

    private function createCRDT(string $type): CRDTInterface
    {
        switch ($type) {
            case 'pn-counter':
                return new PNCounter();
            case 'observed-remove-set':
                return new ObservedRemoveSet();
            case 'last-write-wins-register':
                return new LastWriteWinsRegister();
            default:
                throw new ConflictException("Unknown CRDT type: {$type}");
        }
    }
} 
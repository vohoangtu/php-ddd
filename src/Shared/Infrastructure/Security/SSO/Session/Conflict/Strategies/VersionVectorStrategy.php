<?php

namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict\Strategies;

class VersionVectorStrategy implements ResolutionStrategyInterface
{
    private array $vectorStore = [];

    public function resolve(array $changes, array $context): array
    {
        $vectors = $this->buildVersionVectors($changes);
        $resolved = [];

        foreach ($changes as $change) {
            $vector = $vectors[$change['id']];
            
            if ($this->isLatestVersion($vector, $change['key'])) {
                $resolved[$change['key']] = $change['value'];
                $this->updateVector($vector, $change['key']);
            }
        }

        return [
            'data' => $resolved,
            'vectors' => $vectors,
            'resolution_type' => 'version_vector'
        ];
    }

    private function buildVersionVectors(array $changes): array
    {
        $vectors = [];
        
        foreach ($changes as $change) {
            $deviceId = $change['device_id'];
            if (!isset($vectors[$deviceId])) {
                $vectors[$deviceId] = $this->getStoredVector($deviceId);
            }
        }

        return $vectors;
    }

    private function isLatestVersion(array $vector, string $key): bool
    {
        return !isset($this->vectorStore[$key]) || 
               $this->compareVectors($vector, $this->vectorStore[$key]) > 0;
    }

    private function compareVectors(array $v1, array $v2): int
    {
        $sum1 = array_sum($v1);
        $sum2 = array_sum($v2);
        return $sum1 <=> $sum2;
    }
} 
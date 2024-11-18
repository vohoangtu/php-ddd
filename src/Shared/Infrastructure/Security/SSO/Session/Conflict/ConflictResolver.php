<?php

namespace App\Shared\Infrastructure\Security\SSO\Session\Conflict;

class ConflictResolver
{
    private array $strategies;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'default_strategy' => 'last_write_wins',
            'merge_threshold' => 300, // 5 minutes
            'version_history' => 10,
        ], $config);

        $this->initializeStrategies();
    }

    private function initializeStrategies(): void
    {
        $this->strategies = [
            'last_write_wins' => new LastWriteWinsStrategy(),
            'first_write_wins' => new FirstWriteWinsStrategy(),
            'merge' => new MergeStrategy(),
            'version_vector' => new VersionVectorStrategy(),
            'custom' => new CustomResolutionStrategy(),
        ];
    }

    public function resolveConflict(
        string $type,
        array $changes,
        array $context = []
    ): ResolvedChange {
        $strategy = $this->getStrategy($type);
        
        try {
            $resolved = $strategy->resolve($changes, array_merge(
                $this->config,
                $context
            ));

            return new ResolvedChange(
                $resolved,
                $strategy->getName(),
                $this->generateMetadata($changes, $resolved)
            );
        } catch (ConflictException $e) {
            return $this->handleUnresolvableConflict($changes, $e);
        }
    }

    private function getStrategy(string $type): ResolutionStrategyInterface
    {
        return $this->strategies[$type] ?? 
               $this->strategies[$this->config['default_strategy']];
    }

    private function generateMetadata(array $changes, array $resolved): array
    {
        return [
            'timestamp' => microtime(true),
            'changes_count' => count($changes),
            'resolution_type' => $resolved['resolution_type'] ?? null,
            'conflicts_found' => $resolved['conflicts_found'] ?? 0,
            'merged_fields' => $resolved['merged_fields'] ?? [],
        ];
    }

    private function handleUnresolvableConflict(
        array $changes,
        ConflictException $e
    ): ResolvedChange {
        // Log the unresolvable conflict
        $this->logConflict($changes, $e);

        // Use manual resolution or fallback strategy
        return $this->manualResolution($changes);
    }
} 
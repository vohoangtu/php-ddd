<?php

namespace App\Shared\Infrastructure\Security\Audit;

use App\Shared\Infrastructure\Database\DatabaseInterface;
use App\Shared\Infrastructure\Security\Authentication\AuthenticationInterface;

class DatabaseAuditLogger implements AuditLoggerInterface
{
    private DatabaseInterface $database;
    private AuthenticationInterface $auth;
    private array $config;

    public function __construct(
        DatabaseInterface $database,
        AuthenticationInterface $auth,
        array $config = []
    ) {
        $this->database = $database;
        $this->auth = $auth;
        $this->config = array_merge([
            'table' => 'audit_logs',
            'retention_days' => 90,
        ], $config);
    }

    public function log(string $action, array $context = []): void
    {
        $user = $this->auth->getCurrentUser();
        
        $logEntry = [
            'action' => $action,
            'user_id' => $user?->getId(),
            'user_type' => $user?->getType(),
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'context' => json_encode($context),
            'created_at' => date('Y-m-d H:i:s'),
            'request_id' => $this->getRequestId(),
            'session_id' => session_id(),
        ];

        $this->database->table($this->config['table'])->insert($logEntry);
        
        // Clean old logs if needed
        if (rand(1, 100) === 1) { // 1% chance to trigger cleanup
            $this->cleanOldLogs();
        }
    }

    public function getAuditTrail(array $filters = []): array
    {
        $query = $this->database->table($this->config['table']);

        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    private function cleanOldLogs(): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$this->config['retention_days']} days"));
        
        $this->database->table($this->config['table'])
            ->where('created_at', '<', $cutoff)
            ->delete();
    }
} 
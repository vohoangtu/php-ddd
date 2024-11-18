<?php
namespace App\Shared\Infrastructure\Security\Audit;

interface AuditLoggerInterface
{
    public function log(string $action, array $context = []): void;
    public function getAuditTrail(array $filters = []): array;
    public function exportAuditLog(array $filters = [], string $format = 'json'): string;
} 
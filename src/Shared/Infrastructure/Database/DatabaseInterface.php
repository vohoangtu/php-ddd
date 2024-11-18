<?php

namespace App\Shared\Infrastructure\Database;

interface DatabaseInterface
{
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
    public function query(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): int;
    public function lastInsertId(): string;
} 
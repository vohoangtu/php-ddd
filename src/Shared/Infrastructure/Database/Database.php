<?php
namespace App\Shared\Infrastructure\Database;

use PDO;
use App\Shared\Infrastructure\Error\ErrorHandler;

class Database implements DatabaseInterface
{
    private PDO $pdo;
    private ErrorHandler $errorHandler;

    public function __construct(array $config, ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
        $this->connect($config);
    }

    private function connect(array $config): void
    {
        $dsn = sprintf(
            '%s:host=%s;dbname=%s;charset=utf8mb4',
            $config['driver'],
            $config['host'],
            $config['database']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (\PDOException $e) {
            $this->errorHandler->logError('Database connection failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->errorHandler->logError('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            $this->errorHandler->logError('Database execution failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
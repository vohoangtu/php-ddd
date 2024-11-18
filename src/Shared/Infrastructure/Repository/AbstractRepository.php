<?php

namespace App\Shared\Infrastructure\Repository;

use App\Shared\Infrastructure\Database\DatabaseInterface;
use App\Shared\Domain\Repository\RepositoryInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    protected DatabaseInterface $db;
    protected string $table;
    protected string $entityClass;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function find($id)
    {
        $result = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );

        return $result ? $this->hydrate($result[0]) : null;
    }

    public function findAll(array $criteria = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($criteria)) {
            $conditions = [];
            foreach ($criteria as $key => $value) {
                $conditions[] = "$key = ?";
                $params[] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $results = $this->db->query($sql, $params);
        return array_map([$this, 'hydrate'], $results);
    }

    public function create(array $data)
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->db->execute(
            "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)",
            array_values($data)
        );

        return $this->find($this->db->lastInsertId());
    }

    public function update($id, array $data): bool
    {
        $fields = array_map(
            fn($field) => "$field = ?",
            array_keys($data)
        );

        return (bool) $this->db->execute(
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?",
            [...array_values($data), $id]
        );
    }

    public function delete($id): bool
    {
        return (bool) $this->db->execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    protected function hydrate(object $data)
    {
        return new $this->entityClass((array) $data);
    }
} 
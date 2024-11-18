<?php

namespace App\Product\Infrastructure\Repository;

use App\Shared\Infrastructure\Repository\AbstractRepository;
use App\Product\Domain\Entity\Product;

class ProductRepository extends AbstractRepository
{
    protected string $table = 'products';
    protected string $entityClass = Product::class;

    public function findActive(int $limit = 10): array
    {
        $results = $this->db->query(
            "SELECT * FROM {$this->table} WHERE is_active = ? LIMIT ?",
            [true, $limit]
        );

        return array_map([$this, 'hydrate'], $results);
    }
} 
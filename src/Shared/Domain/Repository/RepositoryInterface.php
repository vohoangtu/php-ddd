<?php
namespace App\Shared\Domain\Repository;

interface RepositoryInterface
{
    public function find($id);
    public function findAll(array $criteria = []): array;
    public function create(array $data);
    public function update($id, array $data): bool;
    public function delete($id): bool;
}
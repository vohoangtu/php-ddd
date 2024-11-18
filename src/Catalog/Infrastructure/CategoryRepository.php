<?php

namespace App\Catalog\Infrastructure;

use App\Catalog\Domain\Category;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Str;

class CategoryRepository
{
    public function findAll(): array
    {
        $categories = DB::table('categories')
            ->orderBy('name')
            ->get();

        return array_map(function($category) {
            return $this->mapToCategory($category);
        }, $categories->all());
    }

    public function findById(int $id): ?Category
    {
        $category = DB::table('categories')->find($id);
        return $category ? $this->mapToCategory($category) : null;
    }

    public function create(array $data): int
    {
        $slug = Str::slug($data['name']);
        $imagePath = isset($_FILES['image']) ? $this->handleImageUpload($_FILES['image']) : null;

        return DB::table('categories')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => $slug,
            'parent_id' => $data['parent_id'] ?? null,
            'image' => $imagePath,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now()
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => Str::slug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'updated_at' => now()
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($_FILES['image']);
            if ($imagePath) {
                $updateData['image'] = $imagePath;
                $this->deleteOldImage($id);
            }
        }

        return DB::table('categories')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    public function delete(int $id): bool
    {
        // Check if category has products
        $hasProducts = DB::table('products')
            ->where('category_id', $id)
            ->exists();

        if ($hasProducts) {
            throw new \RuntimeException('Cannot delete category with associated products');
        }

        $this->deleteOldImage($id);
        return DB::table('categories')->delete($id) > 0;
    }

    public function getHierarchy(): array
    {
        $categories = $this->findAll();
        return $this->buildHierarchy($categories);
    }

    private function buildHierarchy(array $categories, ?int $parentId = null): array
    {
        $hierarchy = [];
        foreach ($categories as $category) {
            if ($category->getParentId() === $parentId) {
                $children = $this->buildHierarchy($categories, $category->getId());
                $hierarchy[] = [
                    'category' => $category,
                    'children' => $children
                ];
            }
        }
        return $hierarchy;
    }

    private function handleImageUpload($image): ?string
    {
        if ($image['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/categories/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid() . '_' . basename($image['name']);
        $uploadFile = $uploadDir . $filename;

        if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
            return '/uploads/categories/' . $filename;
        }

        return null;
    }

    private function deleteOldImage(int $id): void
    {
        $category = $this->findById($id);
        if ($category && $category->getImage()) {
            $imagePath = __DIR__ . '/../../../public' . $category->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }

    private function mapToCategory($data): Category
    {
        return new Category(
            $data->id,
            $data->name,
            $data->description,
            $data->slug,
            $data->parent_id,
            $data->image,
            (bool) $data->is_active,
            $data->created_at,
            $data->updated_at
        );
    }
} 
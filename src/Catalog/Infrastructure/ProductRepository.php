<?php

namespace App\Catalog\Infrastructure;

use App\Catalog\Domain\Product;
use Illuminate\Database\Capsule\Manager as DB;

class ProductRepository
{
    public function findAll(): array
    {
        $products = DB::table('products')->get();
        return array_map(fn($product) => $this->mapToProduct($product), $products->all());
    }

    public function findById(int $id): ?Product
    {
        $product = DB::table('products')->find($id);
        return $product ? $this->mapToProduct($product) : null;
    }

    private function mapToProduct($data): Product
    {
        return new Product(
            $data->id,
            $data->name,
            $data->description,
            $data->price,
            $data->stock
        );
    }

    private function handleImageUpload($image): ?string
    {
        if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid() . '_' . basename($image['name']);
        $uploadFile = $uploadDir . $filename;

        if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
            return '/uploads/products/' . $filename;
        }

        return null;
    }

    public function create(array $data): int
    {
        $imagePath = isset($_FILES['image']) ? $this->handleImageUpload($_FILES['image']) : null;

        return DB::table('products')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'category_id' => $data['category_id'] ?? null,
            'image' => $imagePath,
            'featured' => $data['featured'] ?? false,
            'created_at' => now()
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'category_id' => $data['category_id'] ?? null,
            'featured' => $data['featured'] ?? false,
            'updated_at' => now()
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($_FILES['image']);
            if ($imagePath) {
                $updateData['image'] = $imagePath;
                
                // Delete old image
                $oldProduct = $this->findById($id);
                if ($oldProduct && $oldProduct->getImage()) {
                    $oldImagePath = __DIR__ . '/../../../public' . $oldProduct->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            }
        }

        return DB::table('products')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);
        if ($product && $product->getImage()) {
            $imagePath = __DIR__ . '/../../../public' . $product->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        return DB::table('products')->delete($id) > 0;
    }
} 
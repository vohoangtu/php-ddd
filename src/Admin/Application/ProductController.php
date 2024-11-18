<?php

namespace App\Admin\Application;

use App\Catalog\Infrastructure\ProductRepository;
use App\Shared\Infrastructure\Security\Authorization\AuthorizationInterface;
use Jenssegers\Blade\Blade;

class ProductController
{
    private ProductRepository $productRepository;
    private AuthorizationInterface $authorization;
    private Blade $blade;

    public function __construct(
        ProductRepository $productRepository,
        AuthorizationInterface $authorization,
        Blade $blade
    ) {
        $this->productRepository = $productRepository;
        $this->authorization = $authorization;
        $this->blade = $blade;
    }

    public function index()
    {
        if (!$this->authorization->can('view_products')) {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /admin');
            exit;
        }

        $products = $this->productRepository->findAll();
        echo $this->blade->make('admin.products.index', [
            'products' => $products
        ])->render();
    }

    public function create()
    {
        if (!$this->authorization->can('create_product')) {
            $_SESSION['error'] = 'Unauthorized action';
            header('Location: /admin/products');
            exit;
        }

        echo $this->blade->make('admin.products.create')->render();
    }

    public function store()
    {
        if (!$this->authorization->can('create_product')) {
            $_SESSION['error'] = 'Unauthorized action';
            header('Location: /admin/products');
            exit;
        }

        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'price' => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT),
            'stock' => filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT),
            'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT),
            'department' => filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING),
            'sku' => filter_input(INPUT_POST, 'sku', FILTER_SANITIZE_STRING),
            'is_active' => isset($_POST['is_active']),
            'low_stock_threshold' => filter_input(INPUT_POST, 'low_stock_threshold', FILTER_VALIDATE_INT)
        ];

        if (!$data['name'] || !$data['price']) {
            $_SESSION['error'] = 'Name and price are required';
            header('Location: /admin/products/create');
            exit;
        }

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $data['image'] = $this->handleImageUpload($_FILES['image']);
        }

        $id = $this->productRepository->create($data);
        $_SESSION['success'] = 'Product created successfully';
        header('Location: /admin/products');
        exit;
    }

    public function edit(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            $_SESSION['error'] = 'Product not found';
            header('Location: /admin/products');
            exit;
        }

        if (!$this->authorization->can('update_product', [
            'resource_id' => $id,
            'resource_type' => 'product',
            'category_id' => $product->getCategoryId(),
            'department' => $product->getDepartment()
        ])) {
            $_SESSION['error'] = 'Unauthorized action';
            header('Location: /admin/products');
            exit;
        }

        echo $this->blade->make('admin.products.edit', [
            'product' => $product
        ])->render();
    }

    public function update(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            $_SESSION['error'] = 'Product not found';
            header('Location: /admin/products');
            exit;
        }
        
        // Check if user can update this product using ABAC
        if (!$this->authorization->can('update_product', [
            'resource_id' => $id,
            'resource_type' => 'product',
            'category_id' => $product->getCategoryId(),
            'department' => $product->getDepartment()
        ])) {
            $_SESSION['error'] = 'Unauthorized action';
            header('Location: /admin/products');
            exit;
        }

        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'price' => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT),
            'stock' => filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT),
            'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT),
            'department' => filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING),
            'sku' => filter_input(INPUT_POST, 'sku', FILTER_SANITIZE_STRING),
            'is_active' => isset($_POST['is_active']),
            'low_stock_threshold' => filter_input(INPUT_POST, 'low_stock_threshold', FILTER_VALIDATE_INT)
        ];

        if (!$data['name'] || !$data['price']) {
            $_SESSION['error'] = 'Name and price are required';
            header("Location: /admin/products/$id/edit");
            exit;
        }

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $data['image'] = $this->handleImageUpload($_FILES['image']);
            if ($product->getImage()) {
                $this->deleteOldImage($product->getImage());
            }
        }

        $this->productRepository->update($id, $data);
        $_SESSION['success'] = 'Product updated successfully';
        header('Location: /admin/products');
        exit;
    }

    public function delete(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            $_SESSION['error'] = 'Product not found';
            header('Location: /admin/products');
            exit;
        }

        if (!$this->authorization->can('delete_product', [
            'resource_id' => $id,
            'resource_type' => 'product',
            'category_id' => $product->getCategoryId(),
            'department' => $product->getDepartment()
        ])) {
            $_SESSION['error'] = 'Unauthorized action';
            header('Location: /admin/products');
            exit;
        }

        // Delete product image if exists
        if ($product->getImage()) {
            $this->deleteOldImage($product->getImage());
        }

        $this->productRepository->delete($id);
        $_SESSION['success'] = 'Product deleted successfully';
        header('Location: /admin/products');
        exit;
    }

    private function handleImageUpload(array $file): ?string
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \RuntimeException('Invalid file type');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException('File too large');
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid() . '_' . basename($file['name']);
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }

        return 'uploads/products/' . $filename;
    }

    private function deleteOldImage(string $path): void
    {
        $fullPath = __DIR__ . '/../../../public/' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
} 
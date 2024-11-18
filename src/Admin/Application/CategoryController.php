<?php
namespace App\Admin\Application;

use App\Catalog\Infrastructure\CategoryRepository;
use Jenssegers\Blade\Blade;

class CategoryController
{
    private CategoryRepository $categoryRepository;
    private Blade $blade;

    public function __construct(CategoryRepository $categoryRepository, Blade $blade)
    {
        $this->categoryRepository = $categoryRepository;
        $this->blade = $blade;
    }

    public function index()
    {
        $categories = $this->categoryRepository->getHierarchy();
        echo $this->blade->make('admin.categories.index', [
            'categories' => $categories
        ])->render();
    }

    public function create()
    {
        $categories = $this->categoryRepository->findAll();
        echo $this->blade->make('admin.categories.create', [
            'categories' => $categories
        ])->render();
    }

    public function store()
    {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'parent_id' => filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT),
            'is_active' => isset($_POST['is_active'])
        ];

        if (!$data['name']) {
            $_SESSION['error'] = 'Category name is required';
            header('Location: /admin/categories/create');
            exit;
        }

        try {
            $this->categoryRepository->create($data);
            $_SESSION['success'] = 'Category created successfully';
            header('Location: /admin/categories');
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create category: ' . $e->getMessage();
            header('Location: /admin/categories/create');
        }
        exit;
    }

    public function edit(int $id)
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            $_SESSION['error'] = 'Category not found';
            header('Location: /admin/categories');
            exit;
        }

        $categories = $this->categoryRepository->findAll();
        echo $this->blade->make('admin.categories.edit', [
            'category' => $category,
            'categories' => $categories
        ])->render();
    }

    public function update(int $id)
    {
        $data = [
            'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'parent_id' => filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT),
            'is_active' => isset($_POST['is_active'])
        ];

        if (!$data['name']) {
            $_SESSION['error'] = 'Category name is required';
            header("Location: /admin/categories/$id/edit");
            exit;
        }

        try {
            $this->categoryRepository->update($id, $data);
            $_SESSION['success'] = 'Category updated successfully';
            header('Location: /admin/categories');
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update category: ' . $e->getMessage();
            header("Location: /admin/categories/$id/edit");
        }
        exit;
    }

    public function delete(int $id)
    {
        try {
            $this->categoryRepository->delete($id);
            $_SESSION['success'] = 'Category deleted successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to delete category: ' . $e->getMessage();
        }
        header('Location: /admin/categories');
        exit;
    }
}
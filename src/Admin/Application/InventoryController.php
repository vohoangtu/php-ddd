<?php

namespace App\Admin\Application;

use App\Inventory\Application\InventoryService;
use Jenssegers\Blade\Blade;

class InventoryController
{
    private InventoryService $inventoryService;
    private Blade $blade;

    public function __construct(InventoryService $inventoryService, Blade $blade)
    {
        $this->inventoryService = $inventoryService;
        $this->blade = $blade;
    }

    public function index(): void
    {
        $lowStockProducts = $this->inventoryService->getLowStockProducts();
        
        echo $this->blade->make('admin.inventory.index', [
            'lowStockProducts' => $lowStockProducts
        ])->render();
    }

    public function show(int $id): void
    {
        try {
            $data = $this->inventoryService->getProductInventoryDetails($id);
            
            echo $this->blade->make('admin.inventory.show', [
                'product' => $data['product'],
                'movements' => $data['movements'],
                'alerts' => $data['alerts']
            ])->render();
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/inventory');
            exit;
        }
    }

    public function adjust(int $id): void
    {
        $quantity = (int)filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
        $reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING);

        if (!$quantity) {
            $_SESSION['error'] = 'Invalid quantity';
            header("Location: /admin/inventory/products/$id");
            exit;
        }

        try {
            $this->inventoryService->adjustStock($id, $quantity, $reason, $reference);
            $_SESSION['success'] = 'Stock adjusted successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: /admin/inventory/products/$id");
        exit;
    }
} 
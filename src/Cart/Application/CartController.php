<?php

namespace App\Cart\Application;

use Jenssegers\Blade\Blade;

class CartController
{
    private CartService $cartService;
    private Blade $blade;

    public function __construct(CartService $cartService, Blade $blade)
    {
        $this->cartService = $cartService;
        $this->blade = $blade;
    }

    public function index(): void
    {
        echo $this->blade->make('cart.index', [
            'items' => $this->cartService->getItems(),
            'subtotal' => $this->cartService->getSubtotal(),
            'tax' => $this->cartService->calculateTax(),
            'shipping' => $this->cartService->calculateShipping(),
            'total' => $this->cartService->getTotal()
        ])->render();
    }

    public function add(): void
    {
        try {
            $productId = (int)filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity = (int)filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

            $this->cartService->addItem($productId, $quantity);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added to cart',
                    'cartCount' => $this->cartService->getCount()
                ]);
                return;
            }

            $_SESSION['success'] = 'Product added to cart';
            header('Location: /cart');
        } catch (\Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                return;
            }

            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        }
        exit;
    }

    public function update(): void
    {
        try {
            $productId = (int)filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity = (int)filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

            $this->cartService->updateQuantity($productId, $quantity);

            echo json_encode([
                'success' => true,
                'cartCount' => $this->cartService->getCount(),
                'subtotal' => $this->cartService->getSubtotal(),
                'tax' => $this->cartService->calculateTax(),
                'shipping' => $this->cartService->calculateShipping(),
                'total' => $this->cartService->getTotal()
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function remove(): void
    {
        try {
            $productId = (int)filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $this->cartService->removeItem($productId);

            echo json_encode([
                'success' => true,
                'cartCount' => $this->cartService->getCount(),
                'subtotal' => $this->cartService->getSubtotal(),
                'tax' => $this->cartService->calculateTax(),
                'shipping' => $this->cartService->calculateShipping(),
                'total' => $this->cartService->getTotal()
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
} 
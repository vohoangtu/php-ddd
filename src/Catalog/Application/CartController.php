<?php

namespace App\Catalog\Application;

use App\Catalog\Infrastructure\ProductRepository;
use Jenssegers\Blade\Blade;

class CartController
{
    private CartService $cartService;
    private ProductRepository $productRepository;
    private Blade $blade;

    public function __construct(
        CartService $cartService,
        ProductRepository $productRepository,
        Blade $blade
    ) {
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->blade = $blade;
    }

    public function show()
    {
        $cartItems = $this->cartService->getItems();
        $products = [];
        $total = 0;

        foreach ($cartItems as $productId => $quantity) {
            $product = $this->productRepository->findById($productId);
            if ($product) {
                $products[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        echo $this->blade->make('cart.show', [
            'items' => $products,
            'total' => $total
        ])->render();
    }

    public function add(int $productId, int $quantity)
    {
        $this->cartService->addItem($productId, $quantity);
        header('Location: /cart');
        exit;
    }

    public function update(int $productId, int $quantity)
    {
        $this->cartService->updateQuantity($productId, $quantity);
        header('Location: /cart');
        exit;
    }

    public function remove(int $productId)
    {
        $this->cartService->removeItem($productId);
        header('Location: /cart');
        exit;
    }
} 
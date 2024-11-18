<?php

namespace App\Order\Application;

use App\Catalog\Application\CartService;
use App\Catalog\Infrastructure\ProductRepository;
use App\Order\Infrastructure\OrderRepository;
use Jenssegers\Blade\Blade;

class CheckoutController
{
    private CartService $cartService;
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;
    private Blade $blade;

    public function __construct(
        CartService $cartService,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        Blade $blade
    ) {
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->blade = $blade;
    }

    public function showCheckoutForm()
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

        echo $this->blade->make('checkout.form', [
            'items' => $products,
            'total' => $total
        ])->render();
    }

    public function processCheckout()
    {
        $cartItems = $this->cartService->getItems();
        if (empty($cartItems)) {
            header('Location: /cart');
            exit;
        }

        // Validate input
        $customerName = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_STRING);
        $customerEmail = filter_input(INPUT_POST, 'customer_email', FILTER_SANITIZE_EMAIL);
    }
} 
<?php

namespace App\Wishlist\Application;

use Jenssegers\Blade\Blade;
use App\Auth\Domain\AuthenticationService;

class WishlistController
{
    private WishlistService $wishlistService;
    private AuthenticationService $authService;
    private Blade $blade;

    public function __construct(
        WishlistService $wishlistService,
        AuthenticationService $authService,
        Blade $blade
    ) {
        $this->wishlistService = $wishlistService;
        $this->authService = $authService;
        $this->blade = $blade;
    }

    public function index(): void
    {
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $wishlistItems = $this->wishlistService->getWishlist($user->id);
        
        echo $this->blade->make('wishlist.index', [
            'items' => $wishlistItems
        ])->render();
    }

    public function add(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                throw new \Exception('Please login to add items to wishlist');
            }

            $productId = (int)filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            if (!$productId) {
                throw new \Exception('Invalid product');
            }

            $this->wishlistService->addItem($user->id, $productId);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added to wishlist',
                    'count' => $this->wishlistService->getWishlistCount($user->id)
                ]);
                return;
            }

            $_SESSION['success'] = 'Product added to wishlist';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } catch (\Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
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

    public function remove(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                throw new \Exception('Please login to remove items from wishlist');
            }

            $productId = (int)filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            if (!$productId) {
                throw new \Exception('Invalid product');
            }

            $this->wishlistService->removeItem($user->id, $productId);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product removed from wishlist',
                    'count' => $this->wishlistService->getWishlistCount($user->id)
                ]);
                return;
            }

            $_SESSION['success'] = 'Product removed from wishlist';
            header('Location: /wishlist');
        } catch (\Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                return;
            }

            $_SESSION['error'] = $e->getMessage();
            header('Location: /wishlist');
        }
        exit;
    }

    public function moveToCart(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                throw new \Exception('Please login to move items to cart');
            }

            $productId = (int)filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            if (!$productId) {
                throw new \Exception('Invalid product');
            }

            $this->wishlistService->moveToCart($user->id, $productId);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product moved to cart',
                    'wishlistCount' => $this->wishlistService->getWishlistCount($user->id)
                ]);
                return;
            }

            $_SESSION['success'] = 'Product moved to cart';
            header('Location: /wishlist');
        } catch (\Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                return;
            }

            $_SESSION['error'] = $e->getMessage();
            header('Location: /wishlist');
        }
        exit;
    }

    public function clear(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                throw new \Exception('Please login to clear wishlist');
            }

            $this->wishlistService->clearWishlist($user->id);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Wishlist cleared'
                ]);
                return;
            }

            $_SESSION['success'] = 'Wishlist cleared';
            header('Location: /wishlist');
        } catch (\Exception $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                return;
            }

            $_SESSION['error'] = $e->getMessage();
            header('Location: /wishlist');
        }
        exit;
    }
} 
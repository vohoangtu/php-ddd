<?php
namespace App\Wishlist\Application;

use Illuminate\Database\Capsule\Manager as DB;
use App\Wishlist\Domain\Exception\WishlistException;

class WishlistService
{
    public function addItem(int $userId, int $productId): void
    {
        try {
            // Check if product exists
            $product = DB::table('products')
                ->where('id', $productId)
                ->where('is_active', true)
                ->first();

            if (!$product) {
                throw new WishlistException('Product not found or unavailable');
            }

            // Check if item already exists in wishlist
            $exists = DB::table('wishlist_items')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                throw new WishlistException('Product already in wishlist');
            }

            // Add to wishlist
            DB::table('wishlist_items')->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => now()
            ]);
        } catch (WishlistException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WishlistException('Failed to add item to wishlist');
        }
    }

    public function removeItem(int $userId, int $productId): void
    {
        try {
            $deleted = DB::table('wishlist_items')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();

            if (!$deleted) {
                throw new WishlistException('Item not found in wishlist');
            }
        } catch (WishlistException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WishlistException('Failed to remove item from wishlist');
        }
    }

    public function getWishlist(int $userId): array
    {
        return DB::table('wishlist_items')
            ->select(
                'products.*',
                'categories.name as category_name',
                'wishlist_items.created_at as added_at'
            )
            ->join('products', 'products.id', '=', 'wishlist_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('wishlist_items.user_id', $userId)
            ->where('products.is_active', true)
            ->orderBy('wishlist_items.created_at', 'desc')
            ->get()
            ->all();
    }

    public function clearWishlist(int $userId): void
    {
        try {
            DB::table('wishlist_items')
                ->where('user_id', $userId)
                ->delete();
        } catch (\Exception $e) {
            throw new WishlistException('Failed to clear wishlist');
        }
    }

    public function moveToCart(int $userId, int $productId): void
    {
        DB::beginTransaction();
        
        try {
            // Get product details
            $product = DB::table('products')
                ->where('id', $productId)
                ->where('is_active', true)
                ->first();

            if (!$product) {
                throw new WishlistException('Product not found or unavailable');
            }

            // Check stock
            if ($product->stock < 1) {
                throw new WishlistException('Product out of stock');
            }

            // Add to cart (assuming cart is session-based)
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if (isset($_SESSION['cart'][$productId])) {
                throw new WishlistException('Product already in cart');
            }

            $_SESSION['cart'][$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'image' => $product->image
            ];

            // Remove from wishlist
            $this->removeItem($userId, $productId);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new WishlistException($e->getMessage());
        }
    }

    public function isInWishlist(int $userId, int $productId): bool
    {
        return DB::table('wishlist_items')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    public function getWishlistCount(int $userId): int
    {
        return DB::table('wishlist_items')
            ->where('user_id', $userId)
            ->count();
    }
}
<?php

namespace App\Shared\Infrastructure;

use Jenssegers\Blade\Blade;
use App\Catalog\Application\CartService;
use App\Catalog\Infrastructure\ProductRepository;
use App\Order\Infrastructure\OrderRepository;
use App\Catalog\Application\ProductController;
use App\Catalog\Application\CartController;
use App\Order\Application\OrderController;
use App\Order\Application\CheckoutController;
use App\User\Infrastructure\UserRepository;
use App\User\Application\AuthService;
use App\User\Application\AuthController;
use App\Admin\Application\AdminController;
use App\Admin\Infrastructure\AdminMiddleware;
use App\Shared\Infrastructure\Email\EmailService;
use App\Order\Application\OrderNotificationService;
use App\Admin\Application\AnalyticsService;
use App\Shared\Infrastructure\Api\ApiAuthMiddleware;
use App\Api\Controllers\ProductApiController;
use App\Api\Controllers\OrderApiController;
use App\Catalog\Application\SearchService;
use App\Catalog\Application\SearchController;
use App\Admin\Application\ReportService;
use App\Admin\Application\ReportController;
use App\Inventory\Application\InventoryService;
use App\Inventory\Application\InventoryController;
use App\Payment\Application\PaymentService;
use App\Payment\Application\PaymentController;
use App\Order\Application\OrderFulfillmentService;
use App\Admin\Application\OrderFulfillmentController;
use App\Wishlist\Application\WishlistService;
use App\Wishlist\Application\WishlistController;
use App\Recommendation\Application\RecommendationService;
use App\Notification\Application\EmailNotificationService;
use App\Shared\Infrastructure\Error\ErrorHandler;
use App\Notification\Infrastructure\Queue\EmailQueue;
use App\Notification\Infrastructure\Console\ProcessEmailQueueCommand;
use App\Shared\Infrastructure\RateLimiter\RateLimiter;
use App\Shared\Infrastructure\Cache\CacheService;
use App\Shared\Infrastructure\Middleware\RateLimitMiddleware;

class Container
{
    private array $services = [];

    public function __construct()
    {
        $this->registerServices();
    }

    private function registerServices(): void
    {
        // Core Services
        $this->services['blade'] = function() {
            $viewsPath = realpath(__DIR__ . '/../../../views');
            $cachePath = realpath(__DIR__ . '/../../../cache');
            return new Blade($viewsPath, $cachePath);
        };

        // Repositories
        $this->services['product_repository'] = function() {
            return new ProductRepository();
        };

        $this->services['order_repository'] = function() {
            return new OrderRepository();
        };

        $this->services['user_repository'] = function() {
            return new UserRepository();
        };

        // Services
        $this->services['cart_service'] = function() {
            return new CartService(
                $this->get('product_repository')
            );
        };

        $this->services['auth_service'] = function() {
            return new AuthService($this->get('user_repository'));
        };

        // Controllers
        $this->services['product_controller'] = function() {
            return new ProductController(
                $this->get('product_repository'),
                $this->get('blade')
            );
        };

        $this->services['cart_controller'] = function() {
            return new CartController(
                $this->get('cart_service'),
                $this->get('blade')
            );
        };

        $this->services['order_controller'] = function() {
            return new OrderController(
                $this->get('order_repository'),
                $this->get('product_repository'),
                $this->get('blade')
            );
        };

        $this->services['checkout_controller'] = function() {
            return new CheckoutController(
                $this->get('cart_service'),
                $this->get('product_repository'),
                $this->get('order_repository'),
                $this->get('blade')
            );
        };

        $this->services['auth_controller'] = function() {
            return new AuthController(
                $this->get('auth_service'),
                $this->get('blade')
            );
        };

        $this->services['admin_controller'] = function() {
            return new AdminController(
                $this->get('product_repository'),
                $this->get('order_repository'),
                $this->get('analytics_service'),
                $this->get('blade')
            );
        };

        $this->services['admin_middleware'] = function() {
            return new AdminMiddleware($this->get('auth_service'));
        };

        $this->services['email_service'] = function() {
            return new EmailService();
        };

        $this->services['order_notification_service'] = function() {
            return new OrderNotificationService(
                $this->get('email_service'),
                $this->get('order_repository')
            );
        };

        $this->services['analytics_service'] = function() {
            return new AnalyticsService();
        };

        $this->services['api_auth_middleware'] = function() {
            return new ApiAuthMiddleware($this->get('user_repository'));
        };

        $this->services['product_api_controller'] = function() {
            return new ProductApiController($this->get('product_repository'));
        };

        $this->services['order_api_controller'] = function() {
            return new OrderApiController($this->get('order_repository'));
        };

        $this->services['search_service'] = function() {
            return new SearchService();
        };

        $this->services['search_controller'] = function() {
            return new SearchController(
                $this->get('search_service'),
                $this->get('blade')
            );
        };

        $this->services['report_service'] = function() {
            return new ReportService();
        };

        $this->services['report_controller'] = function() {
            return new ReportController(
                $this->get('report_service'),
                $this->get('blade')
            );
        };

        $this->services['inventory_service'] = function() {
            return new InventoryService();
        };

        $this->services['inventory_controller'] = function() {
            return new InventoryController(
                $this->get('inventory_service'),
                $this->get('blade')
            );
        };

        $this->services['payment_service'] = function() {
            return new PaymentService();
        };

        $this->services['payment_controller'] = function() {
            return new PaymentController(
                $this->get('payment_service'),
                $this->get('blade')
            );
        };

        $this->services['order_fulfillment_service'] = function() {
            return new OrderFulfillmentService(
                $this->get('notification_service')
            );
        };

        $this->services['order_fulfillment_controller'] = function() {
            return new OrderFulfillmentController(
                $this->get('order_fulfillment_service'),
                $this->get('blade')
            );
        };

        $this->services['wishlist_service'] = function() {
            return new WishlistService();
        };

        $this->services['wishlist_controller'] = function() {
            return new WishlistController(
                $this->get('wishlist_service'),
                $this->get('auth_service'),
                $this->get('blade')
            );
        };

        $this->services['recommendation_service'] = function() {
            return new RecommendationService();
        };

        $this->services['email_notification_service'] = function() {
            return new EmailNotificationService();
        };

        $this->services['error_handler'] = function() {
            return new ErrorHandler();
        };

        $this->services['email_queue'] = function() {
            return new EmailQueue(
                $this->get('error_handler')
            );
        };

        $this->services['process_email_queue_command'] = function() {
            return new ProcessEmailQueueCommand(
                $this->get('email_queue'),
                $this->get('error_handler')
            );
        };

        $this->services['rate_limiter'] = function() {
            return new RateLimiter(
                $this->get('error_handler')
            );
        };

        $this->services['cache'] = function() {
            return new CacheService(
                $this->get('error_handler')
            );
        };

        $this->services['rate_limit_middleware'] = function() {
            return new RateLimitMiddleware(
                $this->get('rate_limiter')
            );
        };
    }

    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            throw new \RuntimeException("Service $id not found in container.");
        }
        return $this->services[$id]();
    }
} 
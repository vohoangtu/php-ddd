<?php
namespace App\Admin\Application;

use Illuminate\Database\Capsule\Manager as DB;

class AnalyticsService
{
    public function getDashboardStats(): array
    {
        return [
            'sales' => $this->getSalesStats(),
            'products' => $this->getProductStats(),
            'customers' => $this->getCustomerStats(),
            'orders' => $this->getOrderStats(),
            'topProducts' => $this->getTopProducts(),
            'recentOrders' => $this->getRecentOrders(),
            'salesChart' => $this->getSalesChartData(),
            'categoryStats' => $this->getCategoryStats()
        ];
    }

    private function getSalesStats(): array
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        return [
            'today' => DB::table('orders')
                ->whereDate('created_at', $today)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
                
            'this_month' => DB::table('orders')
                ->where('created_at', 'like', $thisMonth . '%')
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
                
            'last_month' => DB::table('orders')
                ->where('created_at', 'like', $lastMonth . '%')
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
                
            'growth' => $this->calculateGrowth(
                DB::table('orders')
                    ->where('created_at', 'like', $lastMonth . '%')
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount'),
                DB::table('orders')
                    ->where('created_at', 'like', $thisMonth . '%')
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount')
            )
        ];
    }

    private function getProductStats(): array
    {
        return [
            'total' => DB::table('products')->count(),
            'active' => DB::table('products')->where('stock', '>', 0)->count(),
            'low_stock' => DB::table('products')->where('stock', '<=', 10)->count(),
            'out_of_stock' => DB::table('products')->where('stock', 0)->count()
        ];
    }

    private function getCustomerStats(): array
    {
        $thisMonth = date('Y-m');
        
        return [
            'total' => DB::table('users')->where('role', 'user')->count(),
            'new_this_month' => DB::table('users')
                ->where('role', 'user')
                ->where('created_at', 'like', $thisMonth . '%')
                ->count(),
            'with_orders' => DB::table('orders')
                ->distinct('customer_email')
                ->count('customer_email')
        ];
    }

    private function getOrderStats(): array
    {
        return [
            'total' => DB::table('orders')->count(),
            'pending' => DB::table('orders')->where('status', 'pending')->count(),
            'processing' => DB::table('orders')->where('status', 'processing')->count(),
            'completed' => DB::table('orders')->where('status', 'completed')->count(),
            'cancelled' => DB::table('orders')->where('status', 'cancelled')->count()
        ];
    }

    private function getTopProducts(int $limit = 5): array
    {
        return DB::table('order_items')
            ->select(
                'products.id',
                'products.name',
                'products.price',
                'products.stock',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name', 'products.price', 'products.stock')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    private function getRecentOrders(int $limit = 5): array
    {
        return DB::table('orders')
            ->select('orders.*', DB::raw('COUNT(order_items.id) as items_count'))
            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('orders.id')
            ->orderBy('orders.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    private function getSalesChartData(): array
    {
        $days = [];
        $sales = [];
        $orders = [];

        for ($i = 30; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $days[] = date('M d', strtotime($date));

            $dailySales = DB::table('orders')
                ->whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            $sales[] = $dailySales;

            $dailyOrders = DB::table('orders')
                ->whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->count();
            $orders[] = $dailyOrders;
        }

        return [
            'labels' => $days,
            'sales' => $sales,
            'orders' => $orders
        ];
    }

    private function getCategoryStats(): array
    {
        return DB::table('products')
            ->select(
                'categories.name',
                DB::raw('COUNT(products.id) as total_products'),
                DB::raw('SUM(products.stock) as total_stock'),
                DB::raw('AVG(products.price) as average_price')
            )
            ->rightJoin('categories', 'categories.id', '=', 'products.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->get()
            ->all();
    }

    private function calculateGrowth(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }
}
<?php

namespace App\Shared\Infrastructure\Security\Authorization\Providers;

use App\Shared\Infrastructure\Security\Authorization\DynamicAttributeProviderInterface;
use Illuminate\Database\Capsule\Manager as DB;

class UserActivityAttributeProvider implements DynamicAttributeProviderInterface 
{
    private array $config;

    public function __construct(array $config) 
    {
        $this->config = $config;
    }

    public function getAttributes(int $userId, array $context): array 
    {
        return [
            'user_activity' => [
                'last_login' => $this->getLastLogin($userId),
                'login_count' => $this->getLoginCount($userId),
                'recent_orders' => $this->getRecentOrdersCount($userId),
                'total_spent' => $this->getTotalSpent($userId)
            ]
        ];
    }

    private function getLastLogin(int $userId): ?string 
    {
        return DB::table('audit_logs')
            ->where('user_id', $userId)
            ->where('action', 'login')
            ->orderBy('created_at', 'desc')
            ->value('created_at');
    }

    private function getLoginCount(int $userId): int 
    {
        return DB::table('audit_logs')
            ->where('user_id', $userId)
            ->where('action', 'login')
            ->count();
    }

    private function getRecentOrdersCount(int $userId): int 
    {
        return DB::table('orders')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
    }

    private function getTotalSpent(int $userId): float 
    {
        return DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('total_amount') ?? 0.0;
    }
} 
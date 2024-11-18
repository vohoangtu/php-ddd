<?php
namespace App\Shared\Infrastructure\WebSocket\Channel;

use App\Shared\Infrastructure\WebSocket\Connection;

interface ChannelAuthorizationInterface
{
    public function canJoinChannel(Connection $connection, string $channel): bool;
    public function canPublishToChannel(Connection $connection, string $channel): bool;
} 
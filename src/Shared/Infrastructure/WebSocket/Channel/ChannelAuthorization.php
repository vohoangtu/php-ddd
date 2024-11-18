<?php

namespace App\Shared\Infrastructure\WebSocket\Channel;

use App\Shared\Infrastructure\WebSocket\Connection;
use App\Shared\Infrastructure\Auth\AuthorizationInterface;

class ChannelAuthorization implements ChannelAuthorizationInterface
{
    private AuthorizationInterface $auth;
    private array $channelRules;

    public function __construct(AuthorizationInterface $auth, array $channelRules = [])
    {
        $this->auth = $auth;
        $this->channelRules = $channelRules;
    }

    public function canJoinChannel(Connection $connection, string $channel): bool
    {
        if (str_starts_with($channel, 'private-')) {
            return $this->authorizePrivateChannel($connection, $channel);
        }

        if (str_starts_with($channel, 'presence-')) {
            return $this->authorizePresenceChannel($connection, $channel);
        }

        return true;
    }

    public function canPublishToChannel(Connection $connection, string $channel): bool
    {
        $userId = $connection->getUserId();
        if (!$userId) {
            return false;
        }

        if (isset($this->channelRules[$channel]['publish'])) {
            return $this->auth->can(
                $this->channelRules[$channel]['publish'],
                ['channel' => $channel]
            );
        }

        return true;
    }

    private function authorizePrivateChannel(Connection $connection, string $channel): bool
    {
        $userId = $connection->getUserId();
        if (!$userId) {
            return false;
        }

        if (isset($this->channelRules[$channel]['join'])) {
            return $this->auth->can(
                $this->channelRules[$channel]['join'],
                ['channel' => $channel]
            );
        }

        return true;
    }

    private function authorizePresenceChannel(Connection $connection, string $channel): bool
    {
        return $this->authorizePrivateChannel($connection, $channel);
    }
} 
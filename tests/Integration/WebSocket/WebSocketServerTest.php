<?php

namespace Tests\Integration\WebSocket;

use App\Shared\Infrastructure\WebSocket\WebSocketServer;
use App\Shared\Infrastructure\WebSocket\Connection;
use App\Shared\Infrastructure\Auth\AuthenticationInterface;
use App\Shared\Infrastructure\Logging\LoggerInterface;
use App\Shared\Infrastructure\Event\EventDispatcherInterface;
use App\Shared\Infrastructure\WebSocket\Channel\ChannelAuthorizationInterface;
use App\Shared\Infrastructure\WebSocket\StateSync\StateSyncManager;
use App\Shared\Infrastructure\WebSocket\Health\ConnectionHealthCheck;
use Tests\Integration\IntegrationTestCase;
use Mockery;

class WebSocketServerTest extends IntegrationTestCase
{
    private WebSocketServer $server;
    private $mockAuth;
    private $mockLogger;
    private $mockEventDispatcher;
    private $mockChannelAuth;
    private $mockStateSync;
    private $mockHealthCheck;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockAuth = Mockery::mock(AuthenticationInterface::class);
        $this->mockLogger = Mockery::mock(LoggerInterface::class);
        $this->mockEventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->mockChannelAuth = Mockery::mock(ChannelAuthorizationInterface::class);
        $this->mockStateSync = Mockery::mock(StateSyncManager::class);
        $this->mockHealthCheck = Mockery::mock(ConnectionHealthCheck::class);

        $this->server = new WebSocketServer(
            $this->mockAuth,
            $this->mockLogger,
            $this->mockEventDispatcher,
            $this->mockChannelAuth,
            $this->mockStateSync,
            $this->mockHealthCheck
        );
    }

    public function testHandleAuthentication(): void
    {
        $mockConnection = Mockery::mock(Connection::class);
        $token = 'valid_token';
        $userId = 1;

        $this->mockAuth
            ->shouldReceive('validateToken')
            ->with($token)
            ->andReturn(true);

        $this->mockAuth
            ->shouldReceive('getUserIdFromToken')
            ->with($token)
            ->andReturn($userId);

        $this->mockLogger
            ->shouldReceive('info')
            ->once();

        $this->mockEventDispatcher
            ->shouldReceive('dispatch')
            ->once();

        $this->server->onOpen($mockConnection);
    }

    public function testHandleSubscription(): void
    {
        $mockConnection = $this->createAuthenticatedConnection();
        $channel = 'test-channel';

        $this->mockChannelAuth
            ->shouldReceive('canJoinChannel')
            ->with($mockConnection, $channel)
            ->andReturn(true);

        $this->mockStateSync
            ->shouldReceive('syncState')
            ->once()
            ->with($mockConnection, $channel);

        $this->server->onMessage($mockConnection, json_encode([
            'type' => 'subscribe',
            'channel' => $channel
        ]));
    }

    private function createAuthenticatedConnection(): Connection
    {
        $mockRatchetConnection = Mockery::mock(Connection::class);
        return new Connection($mockRatchetConnection, 1);
    }
} 
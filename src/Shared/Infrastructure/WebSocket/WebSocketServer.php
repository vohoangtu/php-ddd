<?php
namespace App\Shared\Infrastructure\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Shared\Infrastructure\Auth\AuthenticationInterface;
use App\Shared\Infrastructure\Logging\LoggerInterface;
use App\Shared\Infrastructure\Event\EventDispatcherInterface;
use App\Shared\Infrastructure\WebSocket\Channel\PresenceChannel;
use App\Shared\Infrastructure\WebSocket\Channel\ChannelAuthorizationInterface;
use App\Shared\Infrastructure\WebSocket\StateSync\StateSyncManager;
use App\Shared\Infrastructure\WebSocket\Health\ConnectionHealthCheck;

class WebSocketServer implements MessageComponentInterface, WebSocketServerInterface
{
    private \SplObjectStorage $connections;
    private AuthenticationInterface $auth;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private array $middleware = [];
    private PresenceChannel $presenceChannel;
    private ChannelAuthorizationInterface $channelAuth;
    private StateSyncManager $stateSync;
    private ConnectionHealthCheck $healthCheck;

    public function __construct(
        AuthenticationInterface $auth,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        ChannelAuthorizationInterface $channelAuth,
        StateSyncManager $stateSync,
        ConnectionHealthCheck $healthCheck
    ) {
        $this->connections = new \SplObjectStorage;
        $this->auth = $auth;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->channelAuth = $channelAuth;
        $this->stateSync = $stateSync;
        $this->healthCheck = $healthCheck;
    }

    public function start(int $port): void
    {
        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer(
                new \Ratchet\WebSocket\WsServer($this)
            ),
            $port
        );

        $this->logger->info('WebSocket server started', ['port' => $port]);
        $server->run();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        try {
            $token = $this->getAuthToken($conn);
            $userId = $this->authenticateConnection($token);
            
            $connection = new Connection($conn, $userId);
            $this->connections->attach($conn, $connection);

            $this->eventDispatcher->dispatch(new WebSocketEvent(
                'connection.opened',
                ['connection' => $connection]
            ));

            $this->logger->info('New WebSocket connection', [
                'connection_id' => $conn->resourceId,
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Connection authentication failed', [
                'error' => $e->getMessage()
            ]);
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $connection = $this->connections[$from];
            $data = $this->decodeMessage($msg);

            foreach ($this->middleware as $middleware) {
                if (!$middleware->handle($connection, $data)) {
                    return;
                }
            }

            $this->handleMessage($connection, $data);

        } catch (\Exception $e) {
            $this->logger->error('Message handling failed', [
                'error' => $e->getMessage(),
                'connection_id' => $from->resourceId
            ]);
        }
    }

    private function handleMessage(Connection $connection, array $data): void
    {
        switch ($data['type'] ?? '') {
            case 'subscribe':
                $this->handleSubscribe($connection, $data);
                break;
            case 'unsubscribe':
                $this->handleUnsubscribe($connection, $data);
                break;
            case 'message':
                $this->handleClientMessage($connection, $data);
                break;
            default:
                $this->eventDispatcher->dispatch(new WebSocketEvent(
                    'message.received',
                    ['connection' => $connection, 'data' => $data]
                ));
        }
    }

    private function handleSubscribe(Connection $connection, array $data): void
    {
        if (!isset($data['channel'])) {
            return;
        }

        $channel = $data['channel'];
        
        if (!$this->channelAuth->canJoinChannel($connection, $channel)) {
            $connection->send([
                'type' => 'error',
                'message' => 'Subscription denied'
            ]);
            return;
        }

        $connection->subscribe($channel);
        
        if (str_starts_with($channel, 'presence-')) {
            $this->presenceChannel->join($connection, $data['user_info'] ?? []);
        }

        $this->stateSync->syncState($connection, $channel);
        
        $this->eventDispatcher->dispatch(new WebSocketEvent(
            'channel.subscribed',
            ['connection' => $connection, 'channel' => $channel]
        ));
    }

    public function broadcast(string $channel, array $message): void
    {
        foreach ($this->connections as $conn) {
            $connection = $this->connections[$conn];
            if ($connection->isSubscribedTo($channel)) {
                $connection->send($message);
            }
        }
    }

    public function broadcastToUser(int $userId, array $message): void
    {
        foreach ($this->connections as $conn) {
            $connection = $this->connections[$conn];
            if ($connection->getUserId() === $userId) {
                $connection->send($message);
            }
        }
    }

    public function broadcastToUsers(array $userIds, array $message): void
    {
        foreach ($this->connections as $conn) {
            $connection = $this->connections[$conn];
            if (in_array($connection->getUserId(), $userIds)) {
                $connection->send($message);
            }
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $connection = $this->connections[$conn];
        
        $this->eventDispatcher->dispatch(new WebSocketEvent(
            'connection.closed',
            ['connection' => $connection]
        ));

        $this->connections->detach($conn);
        
        $this->logger->info('Connection closed', [
            'connection_id' => $conn->resourceId,
            'user_id' => $connection->getUserId()
        ]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->logger->error('WebSocket error', [
            'connection_id' => $conn->resourceId,
            'error' => $e->getMessage()
        ]);
        
        $conn->close();
    }

    public function addMiddleware(WebSocketMiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function getConnections(): array
    {
        $connections = [];
        foreach ($this->connections as $conn) {
            $connections[] = $this->connections[$conn];
        }
        return $connections;
    }

    private function authenticateConnection(?string $token): ?int
    {
        if (!$token) {
            return null;
        }

        return $this->auth->validateToken($token)
            ? $this->auth->getUserIdFromToken($token)
            : null;
    }

    private function decodeMessage(string $message): array
    {
        $data = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON message');
        }
        return $data;
    }

    private function canSubscribeToChannel(Connection $connection, string $channel): bool
    {
        // Implement channel authorization logic here
        return true;
    }

    private function handleClientMessage(Connection $connection, array $data): void
    {
        if (!isset($data['channel'])) {
            return;
        }

        $channel = $data['channel'];

        if (!$this->channelAuth->canPublishToChannel($connection, $channel)) {
            $connection->send([
                'type' => 'error',
                'message' => 'Publishing not allowed'
            ]);
            return;
        }

        if (isset($data['state'])) {
            foreach ($data['state'] as $key => $value) {
                $this->stateSync->setState($channel, $key, $value);
            }
        }

        $this->broadcast($channel, [
            'type' => 'message',
            'channel' => $channel,
            'data' => $data['data'] ?? null,
            'sender' => $connection->getUserId()
        ]);
    }
}
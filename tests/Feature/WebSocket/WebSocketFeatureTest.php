<?php

namespace Tests\Feature\WebSocket;

use App\Shared\Infrastructure\WebSocket\WebSocketServer;
use Tests\Feature\FeatureTestCase;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;

class WebSocketFeatureTest extends FeatureTestCase
{
    private WebSocketServer $server;
    private string $wsUrl = 'ws://localhost:8080';

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = $this->app->get(WebSocketServer::class);
        
        // Start WebSocket server in a separate process
        $this->startWebSocketServer();
    }

    public function testClientConnection(): void
    {
        $loop = Loop::get();
        $connected = false;
        $received = [];

        \Ratchet\Client\connect($this->wsUrl)->then(
            function(WebSocket $conn) use (&$connected, &$received, $loop) {
                $connected = true;

                $conn->on('message', function($msg) use (&$received) {
                    $received[] = json_decode($msg, true);
                });

                // Send test message
                $conn->send(json_encode([
                    'type' => 'subscribe',
                    'channel' => 'test-channel'
                ]));

                // Close after 1 second
                $loop->addTimer(1, function() use ($conn) {
                    $conn->close();
                });
            },
            function($e) {
                $this->fail("Could not connect: {$e->getMessage()}");
            }
        );

        $loop->run();

        $this->assertTrue($connected);
        $this->assertNotEmpty($received);
    }

    public function testPresenceChannel(): void
    {
        $loop = Loop::get();
        $users = [];

        // Connect two test clients
        $this->connectTestClient('user1', function($conn) use (&$users) {
            $conn->on('message', function($msg) use (&$users) {
                $data = json_decode($msg, true);
                if ($data['type'] === 'presence.joined') {
                    $users[] = $data['user'];
                }
            });

            $conn->send(json_encode([
                'type' => 'subscribe',
                'channel' => 'presence-test',
                'user_info' => ['id' => 1, 'name' => 'User 1']
            ]));
        });

        $this->connectTestClient('user2', function($conn) use (&$users) {
            $conn->send(json_encode([
                'type' => 'subscribe',
                'channel' => 'presence-test',
                'user_info' => ['id' => 2, 'name' => 'User 2']
            ]));
        });

        $loop->run();

        $this->assertCount(2, $users);
    }

    private function startWebSocketServer(): void
    {
        $pid = pcntl_fork();
        
        if ($pid === 0) {
            $this->server->start(8080);
            exit;
        }

        // Wait for server to start
        sleep(1);
        $this->serverPid = $pid;
    }

    protected function tearDown(): void
    {
        if (isset($this->serverPid)) {
            posix_kill($this->serverPid, SIGTERM);
        }
        parent::tearDown();
    }

    private function connectTestClient(string $id, callable $callback): void
    {
        \Ratchet\Client\connect($this->wsUrl)->then(
            function(WebSocket $conn) use ($callback) {
                $callback($conn);
            },
            function($e) {
                $this->fail("Client connection failed: {$e->getMessage()}");
            }
        );
    }
} 
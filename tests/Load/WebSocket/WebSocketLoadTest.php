<?php

namespace Tests\Load\WebSocket;

use Tests\Load\LoadTestCase;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;

class WebSocketLoadTest extends LoadTestCase
{
    private const CONCURRENT_USERS = 100;
    private const TEST_DURATION = 60; // seconds
    private const MESSAGE_INTERVAL = 0.1; // seconds

    private array $metrics = [
        'connections' => 0,
        'messages_sent' => 0,
        'messages_received' => 0,
        'errors' => 0,
        'latencies' => []
    ];

    public function testConcurrentConnections(): void
    {
        $loop = Loop::get();
        $startTime = microtime(true);

        // Create concurrent connections
        for ($i = 0; $i < self::CONCURRENT_USERS; $i++) {
            $this->connectTestClient($i);
        }

        // Run test for specified duration
        $loop->addTimer(self::TEST_DURATION, function() use ($loop) {
            $loop->stop();
        });

        $loop->run();

        // Assert metrics
        $this->assertGreaterThanOrEqual(self::CONCURRENT_USERS * 0.9, $this->metrics['connections']);
        $this->assertLessThan(100, $this->calculateAverageLatency());
        $this->assertLessThan(self::CONCURRENT_USERS * 0.1, $this->metrics['errors']);
    }

    private function connectTestClient(int $userId): void
    {
        \Ratchet\Client\connect('ws://localhost:8080')->then(
            function(WebSocket $conn) use ($userId) {
                $this->metrics['connections']++;

                // Subscribe to test channel
                $conn->send(json_encode([
                    'type' => 'subscribe',
                    'channel' => 'load-test'
                ]));

                // Send periodic messages
                $loop = Loop::get();
                $loop->addPeriodicTimer(self::MESSAGE_INTERVAL, function() use ($conn, $userId) {
                    $timestamp = microtime(true);
                    $conn->send(json_encode([
                        'type' => 'message',
                        'channel' => 'load-test',
                        'data' => [
                            'user_id' => $userId,
                            'timestamp' => $timestamp
                        ]
                    ]));
                    $this->metrics['messages_sent']++;
                });

                // Handle received messages
                $conn->on('message', function($msg) {
                    $data = json_decode($msg, true);
                    if (isset($data['data']['timestamp'])) {
                        $latency = (microtime(true) - $data['data']['timestamp']) * 1000;
                        $this->metrics['latencies'][] = $latency;
                    }
                    $this->metrics['messages_received']++;
                });

                $conn->on('error', function($error) {
                    $this->metrics['errors']++;
                });
            },
            function($e) {
                $this->metrics['errors']++;
            }
        );
    }

    private function calculateAverageLatency(): float
    {
        if (empty($this->metrics['latencies'])) {
            return 0;
        }
        return array_sum($this->metrics['latencies']) / count($this->metrics['latencies']);
    }
} 
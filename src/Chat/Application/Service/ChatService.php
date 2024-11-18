<?php

namespace App\Chat\Application\Service;

use App\Shared\Infrastructure\WebSocket\WebSocketServerInterface;

class ChatService
{
    private WebSocketServerInterface $wsServer;

    public function __construct(WebSocketServerInterface $wsServer)
    {
        $this->wsServer = $wsServer;
    }

    public function sendMessage(int $userId, string $roomId, string $message): void
    {
        $this->wsServer->broadcast("chat.room.{$roomId}", [
            'type' => 'chat.message',
            'data' => [
                'user_id' => $userId,
                'room_id' => $roomId,
                'message' => $message,
                'timestamp' => time()
            ]
        ]);
    }

    public function notifyTyping(int $userId, string $roomId): void
    {
        $this->wsServer->broadcast("chat.room.{$roomId}", [
            'type' => 'chat.typing',
            'data' => [
                'user_id' => $userId,
                'room_id' => $roomId
            ]
        ]);
    }
} 
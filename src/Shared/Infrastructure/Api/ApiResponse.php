<?php

namespace App\Shared\Infrastructure\Api;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success', int $code = 200): void
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function error(string $message = 'Error', int $code = 400, array $errors = []): void
    {
        self::send([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    private static function send(array $data, int $code): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
} 
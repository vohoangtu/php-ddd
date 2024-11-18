<?php

namespace App\Shared\Infrastructure\Security\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'x-frame-options' => 'DENY',
            'x-content-type-options' => 'nosniff',
            'x-xss-protection' => '1; mode=block',
            'referrer-policy' => 'strict-origin-when-cross-origin',
            'content-security-policy' => null,
            'permissions-policy' => null,
            'strict-transport-security' => 'max-age=31536000; includeSubDomains'
        ], $config);
    }

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        foreach ($this->config as $header => $value) {
            if ($value !== null) {
                $response = $response->withHeader($this->normalizeHeaderName($header), $value);
            }
        }

        return $response;
    }

    private function normalizeHeaderName(string $header): string
    {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', $header)));
    }
} 
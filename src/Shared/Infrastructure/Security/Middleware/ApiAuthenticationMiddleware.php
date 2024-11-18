<?php

namespace App\Shared\Infrastructure\Security\Middleware;

use App\Shared\Infrastructure\Security\ApiKey\ApiKeyManagerInterface;
use App\Shared\Infrastructure\Security\Audit\AuditLoggerInterface;

class ApiAuthenticationMiddleware implements MiddlewareInterface
{
    private ApiKeyManagerInterface $apiKeyManager;
    private AuditLoggerInterface $auditLogger;

    public function __construct(
        ApiKeyManagerInterface $apiKeyManager,
        AuditLoggerInterface $auditLogger
    ) {
        $this->apiKeyManager = $apiKeyManager;
        $this->auditLogger = $auditLogger;
    }

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $apiKey = $this->extractApiKey($request);
        
        if (!$apiKey) {
            return $this->unauthorized('API key is missing');
        }

        if (!$this->apiKeyManager->validateKey($apiKey)) {
            $this->auditLogger->log('api_auth_failed', [
                'key' => substr($apiKey, 0, 8) . '...',
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
            ]);
            return $this->unauthorized('Invalid API key');
        }

        $keyInfo = $this->apiKeyManager->getKeyInfo($apiKey);
        
        // Log successful authentication
        $this->auditLogger->log('api_auth_success', [
            'key_id' => $keyInfo->getId(),
            'scopes' => $keyInfo->getScopes(),
        ]);

        return $handler->handle($request->withAttribute('api_key_info', $keyInfo));
    }

    private function extractApiKey(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return $request->getQueryParams()['api_key'] ?? null;
    }
} 
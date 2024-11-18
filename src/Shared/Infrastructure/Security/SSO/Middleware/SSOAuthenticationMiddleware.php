<?php

namespace App\Shared\Infrastructure\Security\SSO\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Shared\Infrastructure\Security\SSO\SSOSessionManager;
use App\Shared\Infrastructure\Security\SSO\SSOProviderInterface;

class SSOAuthenticationMiddleware implements MiddlewareInterface
{
    private SSOSessionManager $sessionManager;
    private SSOProviderInterface $ssoProvider;
    private array $config;

    public function __construct(
        SSOSessionManager $sessionManager,
        SSOProviderInterface $ssoProvider,
        array $config = []
    ) {
        $this->sessionManager = $sessionManager;
        $this->ssoProvider = $ssoProvider;
        $this->config = array_merge([
            'login_url' => '/sso/login',
            'callback_url' => '/sso/callback',
            'exclude_paths' => []
        ], $config);
    }

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $path = $request->getUri()->getPath();

        // Skip authentication for excluded paths
        if ($this->isExcludedPath($path)) {
            return $handler->handle($request);
        }

        // Handle SSO callback
        if ($path === $this->config['callback_url']) {
            return $this->handleCallback($request, $handler);
        }

        // Validate existing session
        if ($this->sessionManager->validateSession()) {
            $user = $this->sessionManager->getCurrentUser();
            return $handler->handle($request->withAttribute('sso_user', $user));
        }

        // Redirect to SSO login
        return $this->redirectToLogin($request->getUri()->getPath());
    }

    private function handleCallback(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            $userData = $this->ssoProvider->handleCallback(
                $request->getQueryParams()
            );
            
            $this->sessionManager->createSession($userData, 'saml');
            
            $returnTo = $request->getQueryParams()['RelayState'] ?? '/';
            return new RedirectResponse($returnTo);
            
        } catch (SSOException $e) {
            // Handle SSO errors
            return new JsonResponse([
                'error' => 'SSO Authentication failed',
                'message' => $e->getMessage()
            ], 401);
        }
    }

    private function isExcludedPath(string $path): bool
    {
        foreach ($this->config['exclude_paths'] as $excludedPath) {
            if (strpos($path, $excludedPath) === 0) {
                return true;
            }
        }
        return false;
    }

    private function redirectToLogin(string $returnTo): ResponseInterface
    {
        $loginUrl = $this->config['login_url'] . '?returnTo=' . urlencode($returnTo);
        return new RedirectResponse($loginUrl);
    }
} 
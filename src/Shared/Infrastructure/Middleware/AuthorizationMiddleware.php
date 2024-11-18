<?php

namespace App\Shared\Infrastructure\Middleware;

use App\Shared\Domain\Authorization\AuthorizationInterface;
use App\Shared\Infrastructure\Http\Response;

class AuthorizationMiddleware implements MiddlewareInterface
{
    private AuthorizationInterface $auth;
    private string $permission;
    private $resource;

    public function __construct(
        AuthorizationInterface $auth,
        string $permission,
        $resource = null
    ) {
        $this->auth = $auth;
        $this->permission = $permission;
        $this->resource = $resource;
    }

    public function handle(callable $next, ...$params): mixed
    {
        if ($this->auth->cannot($this->permission, $this->resource)) {
            return Response::json([
                'error' => 'Unauthorized',
                'message' => 'You do not have permission to perform this action'
            ], 403);
        }

        return $next(...$params);
    }
} 
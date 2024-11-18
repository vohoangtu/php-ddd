<?php

namespace App\Shared\Infrastructure;

use App\Shared\Infrastructure\Container\Container;
use App\Shared\Infrastructure\Http\{Request, Response};
use App\Shared\Infrastructure\Routing\Router;
use App\Shared\Infrastructure\Error\ErrorHandler;

class Kernel
{
    private Container $container;
    private Router $router;
    private ErrorHandler $errorHandler;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->router = $container->get('router');
        $this->errorHandler = $container->get('error_handler');
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->router->dispatch(
                $request->getMethod(),
                $request->getUri()
            );

            if (!$response instanceof Response) {
                $response = new Response((string) $response);
            }

            return $response;
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(\Exception $e): Response
    {
        $this->errorHandler->logError($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($this->container->get('config')->get('app.debug')) {
            return Response::json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }

        return Response::json([
            'error' => 'Internal Server Error'
        ], 500);
    }
} 
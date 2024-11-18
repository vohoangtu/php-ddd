<?php

namespace App\User\Infrastructure\Routes;

use App\Shared\Infrastructure\Routing\RouteCollection;

class AuthRoutes extends RouteCollection
{
    public function register(): void
    {
        $this->router->get('/login', function() {
            $this->container->get('auth_controller')->showLoginForm();
        });

        $this->router->post('/login', function() {
            $this->container->get('auth_controller')->login();
        });

        $this->router->get('/logout', function() {
            $this->container->get('auth_controller')->logout();
        });
    }
} 
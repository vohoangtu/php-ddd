<?php

namespace App\Shared\Infrastructure\Security\SSO\Server;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use DI\Container;

class SSOServerBootstrap
{
    private Container $container;
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->container = $this->createContainer();
    }

    public function start(): void
    {
        $app = AppFactory::createFromContainer($this->container);
        
        // Add middleware
        $this->registerMiddleware($app);
        
        // Register routes
        $this->registerRoutes($app);
        
        // Start server
        $app->run();
    }

    private function loadConfig(): array
    {
        return [
            'server' => [
                'issuer' => $_ENV['SSO_ISSUER'],
                'encryption_key' => $_ENV['SSO_ENCRYPTION_KEY'],
                'private_key_path' => $_ENV['SSO_PRIVATE_KEY_PATH'],
                'public_key_path' => $_ENV['SSO_PUBLIC_KEY_PATH'],
            ],
            'database' => [
                'driver' => $_ENV['DB_DRIVER'],
                'host' => $_ENV['DB_HOST'],
                'database' => $_ENV['DB_DATABASE'],
                'username' => $_ENV['DB_USERNAME'],
                'password' => $_ENV['DB_PASSWORD'],
            ],
            'cache' => [
                'driver' => $_ENV['CACHE_DRIVER'],
                'host' => $_ENV['CACHE_HOST'],
                'port' => $_ENV['CACHE_PORT'],
            ],
            'protocols' => [
                'oauth2' => [
                    'enabled' => true,
                    'access_token_lifetime' => 3600,
                    'refresh_token_lifetime' => 86400,
                ],
                'oidc' => [
                    'enabled' => true,
                    'id_token_lifetime' => 3600,
                ],
                'saml' => [
                    'enabled' => true,
                    'metadata_path' => $_ENV['SAML_METADATA_PATH'],
                ],
            ],
        ];
    }

    private function createContainer(): Container
    {
        $container = new Container();

        // Register services
        $container->set('config', $this->config);
        
        $container->set(DatabaseInterface::class, function() {
            return new DatabaseAdapter($this->config['database']);
        });
        
        $container->set(CacheInterface::class, function() {
            return new CacheAdapter($this->config['cache']);
        });
        
        $container->set(EncryptionInterface::class, function() {
            return new EncryptionService($this->config['server']['encryption_key']);
        });

        // Register SSO components
        $container->set(SSOServer::class, function(Container $c) {
            return new SSOServer(
                $c->get(DatabaseInterface::class),
                $c->get(CacheInterface::class),
                $c->get(EncryptionInterface::class),
                $c->get(TokenManagerInterface::class),
                $c->get(SessionManagerInterface::class),
                $this->config['server']
            );
        });

        return $container;
    }

    private function registerMiddleware($app): void
    {
        // Add CORS middleware
        $app->add(new CorsMiddleware());
        
        // Add authentication middleware
        $app->add(new AuthenticationMiddleware());
        
        // Add validation middleware
        $app->add(new ValidationMiddleware());
        
        // Add error handling middleware
        $app->add(new ErrorHandlerMiddleware());
    }

    private function registerRoutes($app): void
    {
        // OAuth2 endpoints
        $app->post('/oauth/token', [OAuth2Controller::class, 'token']);
        $app->get('/oauth/authorize', [OAuth2Controller::class, 'authorize']);
        
        // OIDC endpoints
        $app->get('/.well-known/openid-configuration', [OIDCController::class, 'configuration']);
        $app->get('/oidc/userinfo', [OIDCController::class, 'userinfo']);
        
        // SAML endpoints
        $app->post('/saml/acs', [SAMLController::class, 'assertionConsumerService']);
        $app->get('/saml/metadata', [SAMLController::class, 'metadata']);
        
        // Client management endpoints
        $app->post('/client', [ClientController::class, 'create']);
        $app->put('/client/{id}', [ClientController::class, 'update']);
        $app->delete('/client/{id}', [ClientController::class, 'delete']);
    }
} 
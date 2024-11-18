<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Protocol;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

class OAuth2Handler implements ProtocolHandlerInterface
{
    private AuthorizationServer $server;
    private ClientRepositoryInterface $clientRepository;
    private array $config;

    public function __construct(
        AuthorizationServer $server,
        ClientRepositoryInterface $clientRepository,
        array $config = []
    ) {
        $this->server = $server;
        $this->clientRepository = $clientRepository;
        $this->config = array_merge([
            'auth_code_lifetime' => 600,
            'access_token_lifetime' => 3600,
            'refresh_token_lifetime' => 86400,
            'supported_grant_types' => [
                'authorization_code',
                'refresh_token',
                'client_credentials'
            ]
        ], $config);

        $this->configureServer();
    }

    public function authenticate(array $params): AuthResult
    {
        $client = $this->clientRepository->getClientEntity($params['client_id']);
        
        if ($params['response_type'] === 'code') {
            return $this->handleAuthorizationRequest($params, $client);
        }

        return $this->handleTokenRequest($params, $client);
    }

    private function handleAuthorizationRequest(array $params, ClientEntity $client): AuthResult
    {
        $authRequest = $this->server->validateAuthorizationRequest($params);
        $authRequest->setUser(new UserEntity($params['user_id']));
        $authRequest->setAuthorizationApproved(true);

        $response = $this->server->completeAuthorizationRequest($authRequest);

        return new AuthResult(
            $params['user_id'],
            $this->extractUserAttributes($params),
            $params['scope'] ? explode(' ', $params['scope']) : [],
            $response->getRedirectUri()
        );
    }

    private function handleTokenRequest(array $params, ClientEntity $client): AuthResult
    {
        $response = $this->server->respondToAccessTokenRequest($params);

        return new AuthResult(
            $response->getUserIdentifier(),
            $this->extractUserAttributes($params),
            $response->getScopes(),
            null,
            [
                'access_token' => $response->getAccessToken(),
                'refresh_token' => $response->getRefreshToken(),
                'expires_in' => $response->getExpiresIn()
            ]
        );
    }

    private function configureServer(): void
    {
        // Configure Authorization Code grant
        if (in_array('authorization_code', $this->config['supported_grant_types'])) {
            $authCodeGrant = new AuthCodeGrant(
                $this->clientRepository->getAuthCodeRepository(),
                $this->clientRepository->getRefreshTokenRepository(),
                new \DateInterval('PT' . $this->config['auth_code_lifetime'] . 'S')
            );
            
            $authCodeGrant->setRefreshTokenTTL(
                new \DateInterval('PT' . $this->config['refresh_token_lifetime'] . 'S')
            );
            
            $this->server->enableGrantType($authCodeGrant);
        }

        // Configure Refresh Token grant
        if (in_array('refresh_token', $this->config['supported_grant_types'])) {
            $refreshTokenGrant = new RefreshTokenGrant(
                $this->clientRepository->getRefreshTokenRepository()
            );
            
            $refreshTokenGrant->setRefreshTokenTTL(
                new \DateInterval('PT' . $this->config['refresh_token_lifetime'] . 'S')
            );
            
            $this->server->enableGrantType($refreshTokenGrant);
        }
    }
} 
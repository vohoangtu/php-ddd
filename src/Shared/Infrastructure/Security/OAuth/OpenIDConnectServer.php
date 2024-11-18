<?php

namespace App\Shared\Infrastructure\Security\OAuth;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class OpenIDConnectServer implements OAuthServerInterface
{
    private AuthorizationServer $authServer;
    private ResourceServer $resourceServer;
    private ClientRepositoryInterface $clientRepository;

    public function __construct(
        AuthorizationServer $authServer,
        ResourceServer $resourceServer,
        ClientRepositoryInterface $clientRepository
    ) {
        $this->authServer = $authServer;
        $this->resourceServer = $resourceServer;
        $this->clientRepository = $clientRepository;
        
        $this->configureGrants();
    }

    private function configureGrants(): void
    {
        // Configure Authorization Code Grant
        $authCodeGrant = new AuthCodeGrant(
            $this->authCodeRepository,
            $this->refreshTokenRepository,
            new \DateInterval('PT10M') // Authorization code TTL
        );
        $authCodeGrant->setRefreshTokenTTL(new \DateInterval('P1M'));
        
        $this->authServer->enableGrantType($authCodeGrant);

        // Configure Refresh Token Grant
        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL(new \DateInterval('P1M'));
        
        $this->authServer->enableGrantType($refreshTokenGrant);
    }

    public function authorize(array $params): AuthorizationResponse
    {
        try {
            $authRequest = $this->authServer->validateAuthorizationRequest($params);
            
            // Add OpenID Connect scopes and claims
            if (in_array('openid', $authRequest->getScopes())) {
                $authRequest->setScopes(array_merge(
                    $authRequest->getScopes(),
                    ['profile', 'email']
                ));
            }

            return new AuthorizationResponse(
                $authRequest,
                $this->authServer->completeAuthorizationRequest($authRequest)
            );
        } catch (\Exception $e) {
            throw new OAuthException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function token(array $params): TokenResponse
    {
        try {
            $response = $this->authServer->respondToAccessTokenRequest($params);
            
            return new TokenResponse(
                $response->getAccessToken(),
                $response->getRefreshToken(),
                $response->getExpiresIn()
            );
        } catch (\Exception $e) {
            throw new OAuthException($e->getMessage(), $e->getCode(), $e);
        }
    }
} 
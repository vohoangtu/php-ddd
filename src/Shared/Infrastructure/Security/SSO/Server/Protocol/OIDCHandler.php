<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Protocol;

use OpenIDConnectServer\IdTokenResponse;
use OpenIDConnectServer\ClaimExtractor;

class OIDCHandler extends OAuth2Handler
{
    private ClaimExtractor $claimExtractor;
    private array $supportedScopes;

    public function __construct(
        AuthorizationServer $server,
        ClientRepositoryInterface $clientRepository,
        ClaimExtractor $claimExtractor,
        array $config = []
    ) {
        parent::__construct($server, $clientRepository, $config);
        
        $this->claimExtractor = $claimExtractor;
        $this->supportedScopes = array_merge([
            'openid',
            'profile',
            'email',
            'address',
            'phone'
        ], $config['supported_scopes'] ?? []);
    }

    public function authenticate(array $params): AuthResult
    {
        $result = parent::authenticate($params);
        
        if (in_array('openid', $result->getScopes())) {
            $result = $this->enrichWithOIDCClaims($result, $params);
        }

        return $result;
    }

    private function enrichWithOIDCClaims(AuthResult $result, array $params): AuthResult
    {
        $claims = $this->claimExtractor->extract(
            $result->getScopes(),
            $result->getAttributes()
        );

        $idToken = $this->createIdToken(
            $result->getUserId(),
            $claims,
            $params['client_id'],
            $params['nonce'] ?? null
        );

        return $result->withAdditionalData(['id_token' => $idToken]);
    }

    private function createIdToken(
        string $userId,
        array $claims,
        string $clientId,
        ?string $nonce
    ): string {
        $now = time();
        
        $payload = array_merge($claims, [
            'iss' => $this->config['issuer'],
            'sub' => $userId,
            'aud' => $clientId,
            'iat' => $now,
            'exp' => $now + $this->config['id_token_lifetime'],
            'auth_time' => $now
        ]);

        if ($nonce) {
            $payload['nonce'] = $nonce;
        }

        return JWT::encode(
            $payload,
            $this->config['private_key'],
            'RS256',
            $this->config['key_id']
        );
    }
} 
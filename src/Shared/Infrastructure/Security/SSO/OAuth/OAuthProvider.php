<?php
namespace App\Shared\Infrastructure\Security\SSO\OAuth;

use App\Shared\Infrastructure\Security\SSO\SSOProviderInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

class OAuthProvider implements SSOProviderInterface
{
    private GenericProvider $provider;
    private array $config;
    private ?AccessToken $token = null;

    public function initialize(array $config): void
    {
        $this->config = array_merge([
            'clientId' => '',
            'clientSecret' => '',
            'redirectUri' => '',
            'urlAuthorize' => '',
            'urlAccessToken' => '',
            'urlResourceOwnerDetails' => '',
            'scopes' => ['openid', 'profile', 'email'],
            'pkce' => true,
            'responseType' => 'code',
        ], $config);

        $this->provider = new GenericProvider([
            'clientId' => $this->config['clientId'],
            'clientSecret' => $this->config['clientSecret'],
            'redirectUri' => $this->config['redirectUri'],
            'urlAuthorize' => $this->config['urlAuthorize'],
            'urlAccessToken' => $this->config['urlAccessToken'],
            'urlResourceOwnerDetails' => $this->config['urlResourceOwnerDetails'],
        ]);
    }

    public function authenticate(string $returnUrl): string
    {
        $options = [
            'scope' => implode(' ', $this->config['scopes']),
            'state' => $this->generateState($returnUrl),
        ];

        if ($this->config['pkce']) {
            $codeVerifier = $this->generateCodeVerifier();
            $options['code_challenge'] = $this->generateCodeChallenge($codeVerifier);
            $options['code_challenge_method'] = 'S256';
            
            $_SESSION['oauth2_code_verifier'] = $codeVerifier;
        }

        return $this->provider->getAuthorizationUrl($options);
    }

    public function handleCallback(array $params): SSOUserData
    {
        if (empty($params['code'])) {
            throw new SSOException('Authorization code is missing');
        }

        $options = [];
        if ($this->config['pkce'] && isset($_SESSION['oauth2_code_verifier'])) {
            $options['code_verifier'] = $_SESSION['oauth2_code_verifier'];
            unset($_SESSION['oauth2_code_verifier']);
        }

        try {
            $this->token = $this->provider->getAccessToken('authorization_code', [
                'code' => $params['code'],
            ] + $options);

            $resourceOwner = $this->provider->getResourceOwner($this->token);
            $claims = $this->getOpenIDClaims();

            return new SSOUserData(
                $resourceOwner->getId(),
                $claims['email'] ?? null,
                $claims['given_name'] ?? null,
                $claims['family_name'] ?? null,
                $claims['roles'] ?? [],
                $claims
            );
        } catch (\Exception $e) {
            throw new SSOException('OAuth authentication failed: ' . $e->getMessage());
        }
    }

    private function getOpenIDClaims(): array
    {
        if (!$this->token) {
            return [];
        }

        $idToken = $this->token->getValues()['id_token'] ?? null;
        if (!$idToken) {
            return [];
        }

        [$header, $payload, $signature] = explode('.', $idToken);
        return json_decode(base64_decode($payload), true);
    }

    private function generateCodeVerifier(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function generateCodeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function generateState(string $returnUrl): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth2_state'] = [
            'value' => $state,
            'return_url' => $returnUrl
        ];
        return $state;
    }
}
<?php

namespace App\Shared\Infrastructure\Security\SSO\SAML;

use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Settings;
use App\Shared\Infrastructure\Security\SSO\SSOProviderInterface;
use App\Shared\Infrastructure\Security\SSO\SSOUserData;

class SAMLProvider implements SSOProviderInterface
{
    private Auth $auth;
    private array $config;
    private array $attributeMapping;

    public function __construct(array $attributeMapping = [])
    {
        $this->attributeMapping = array_merge([
            'email' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
            'firstName' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
            'lastName' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
            'roles' => 'http://schemas.microsoft.com/ws/2008/06/identity/claims/role'
        ], $attributeMapping);
    }

    public function initialize(array $config): void
    {
        $this->config = array_merge([
            'strict' => true,
            'debug' => false,
            'baseurl' => null,
            'sp' => [
                'entityId' => '',
                'assertionConsumerService' => [
                    'url' => '',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
                ],
                'singleLogoutService' => [
                    'url' => '',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                'x509cert' => '',
                'privateKey' => ''
            ],
            'idp' => [
                'entityId' => '',
                'singleSignOnService' => [
                    'url' => '',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ],
                'singleLogoutService' => [
                    'url' => '',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ],
                'x509cert' => ''
            ]
        ], $config);

        $this->auth = new Auth($this->config);
    }

    public function authenticate(string $returnUrl): string
    {
        $this->auth->login($returnUrl);
        return $this->auth->getLastRequestXML();
    }

    public function handleCallback(array $params): SSOUserData
    {
        $this->auth->processResponse();

        if ($this->auth->getErrors()) {
            throw new SSOException(
                'SAML Authentication failed: ' . implode(', ', $this->auth->getErrors())
            );
        }

        $attributes = $this->auth->getAttributes();
        
        return new SSOUserData(
            $this->auth->getNameId(),
            $this->getAttribute($attributes, 'email'),
            $this->getAttribute($attributes, 'firstName'),
            $this->getAttribute($attributes, 'lastName'),
            $this->getArrayAttribute($attributes, 'roles'),
            $attributes
        );
    }

    private function getAttribute(array $attributes, string $key): ?string
    {
        $mapping = $this->attributeMapping[$key] ?? null;
        return $attributes[$mapping][0] ?? null;
    }

    private function getArrayAttribute(array $attributes, string $key): array
    {
        $mapping = $this->attributeMapping[$key] ?? null;
        return $attributes[$mapping] ?? [];
    }

    public function validateSession(): bool
    {
        return $this->auth->isAuthenticated();
    }

    public function logout(string $returnUrl): string
    {
        return $this->auth->logout($returnUrl);
    }

    public function getMetadata(): array
    {
        $settings = new Settings($this->config, true);
        return [
            'metadata' => $settings->getSPMetadata(),
            'errors' => $settings->validateMetadata()
        ];
    }
} 
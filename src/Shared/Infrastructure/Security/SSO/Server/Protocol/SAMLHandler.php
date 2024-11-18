<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Protocol;

use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Settings;

class SAMLHandler implements ProtocolHandlerInterface
{
    private array $config;
    private ?Auth $auth = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function authenticate(array $params): AuthResult
    {
        $this->initializeSAML($params);

        if (!$this->auth->isAuthenticated()) {
            $this->auth->login();
        }

        $attributes = $this->auth->getAttributes();
        $nameId = $this->auth->getNameId();

        return new AuthResult(
            $nameId,
            $attributes,
            ['saml'],
            $this->auth->getLastRequestID()
        );
    }

    public function validateRequest(array $params): void
    {
        $this->initializeSAML($params);
        
        try {
            $this->auth->processResponse();
            
            if ($this->auth->getErrors()) {
                throw new SSOException(
                    'SAML validation failed: ' . implode(', ', $this->auth->getErrors())
                );
            }
        } catch (\Exception $e) {
            throw new SSOException('SAML request validation failed: ' . $e->getMessage());
        }
    }

    private function initializeSAML(array $params): void
    {
        if ($this->auth) {
            return;
        }

        $settings = new Settings([
            'strict' => true,
            'debug' => $this->config['debug'] ?? false,
            'sp' => [
                'entityId' => $this->config['sp_entity_id'],
                'assertionConsumerService' => [
                    'url' => $this->config['acs_url'],
                ],
                'singleLogoutService' => [
                    'url' => $this->config['sls_url'],
                ],
                'x509cert' => $this->config['sp_cert'],
                'privateKey' => $this->config['sp_key'],
            ],
            'idp' => [
                'entityId' => $this->config['idp_entity_id'],
                'singleSignOnService' => [
                    'url' => $this->config['idp_sso_url'],
                ],
                'singleLogoutService' => [
                    'url' => $this->config['idp_sls_url'],
                ],
                'x509cert' => $this->config['idp_cert'],
            ],
        ]);

        $this->auth = new Auth($settings);
    }
} 
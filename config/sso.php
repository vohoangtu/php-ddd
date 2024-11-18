<?php
return [
    'providers' => [
        'saml' => [
            'class' => \App\Shared\Infrastructure\Security\SSO\SAML\SAMLProvider::class,
            'config' => [
                'sp' => [
                    'entityId' => env('SAML_SP_ENTITY_ID'),
                    'assertionConsumerService' => [
                        'url' => env('SAML_SP_ACS_URL'),
                    ],
                    'singleLogoutService' => [
                        'url' => env('SAML_SP_SLS_URL'),
                    ],
                    'x509cert' => env('SAML_SP_CERT'),
                    'privateKey' => env('SAML_SP_KEY'),
                ],
                'idp' => [
                    'entityId' => env('SAML_IDP_ENTITY_ID'),
                    'singleSignOnService' => [
                        'url' => env('SAML_IDP_SSO_URL'),
                    ],
                    'singleLogoutService' => [
                        'url' => env('SAML_IDP_SLS_URL'),
                    ],
                    'x509cert' => env('SAML_IDP_CERT'),
                ],
            ],
            'attribute_mapping' => [
                'email' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
                'firstName' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
                'lastName' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
                'roles' => 'http://schemas.microsoft.com/ws/2008/06/identity/claims/role',
            ],
        ],
    ],
    'session' => [
        'lifetime' => 3600,
        'refresh_interval' => 300,
    ],
    'middleware' => [
        'exclude_paths' => [
            '/health',
            '/metrics',
            '/sso/metadata',
        ],
    ],
]; 
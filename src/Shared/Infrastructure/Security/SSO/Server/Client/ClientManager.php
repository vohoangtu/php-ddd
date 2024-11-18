<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Client;

use App\Shared\Infrastructure\Database\DatabaseInterface;
use App\Shared\Infrastructure\Security\Encryption\EncryptionInterface;

class ClientManager
{
    private DatabaseInterface $database;
    private EncryptionInterface $encryption;
    private array $config;

    public function __construct(
        DatabaseInterface $database,
        EncryptionInterface $encryption,
        array $config = []
    ) {
        $this->database = $database;
        $this->encryption = $encryption;
        $this->config = array_merge([
            'client_id_length' => 32,
            'client_secret_length' => 64,
            'allowed_grant_types' => [
                'authorization_code',
                'refresh_token',
                'client_credentials'
            ],
            'max_redirect_uris' => 10
        ], $config);
    }

    public function createClient(array $data): ClientEntity
    {
        $this->validateClientData($data);

        $clientId = $this->generateClientId();
        $clientSecret = $this->generateClientSecret();

        $client = new ClientEntity([
            'client_id' => $clientId,
            'client_secret' => $this->hashSecret($clientSecret),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'redirect_uris' => $data['redirect_uris'],
            'grant_types' => $data['grant_types'],
            'scopes' => $data['scopes'] ?? [],
            'is_confidential' => $data['is_confidential'] ?? true,
            'metadata' => $data['metadata'] ?? [],
            'created_at' => time(),
            'updated_at' => time()
        ]);

        $this->saveClient($client);

        return $client->withPlainSecret($clientSecret);
    }

    public function updateClient(string $clientId, array $data): ClientEntity
    {
        $client = $this->getClient($clientId);
        if (!$client) {
            throw new ClientException('Client not found');
        }

        $this->validateClientData($data, $client);

        $updateData = array_merge(
            $client->toArray(),
            $data,
            ['updated_at' => time()]
        );

        if (isset($data['client_secret'])) {
            $updateData['client_secret'] = $this->hashSecret($data['client_secret']);
        }

        $updatedClient = new ClientEntity($updateData);
        $this->saveClient($updatedClient);

        return $updatedClient;
    }

    public function rotateClientSecret(string $clientId): array
    {
        $client = $this->getClient($clientId);
        if (!$client) {
            throw new ClientException('Client not found');
        }

        $newSecret = $this->generateClientSecret();
        
        $this->updateClient($clientId, [
            'client_secret' => $this->hashSecret($newSecret)
        ]);

        return [
            'client_id' => $clientId,
            'client_secret' => $newSecret
        ];
    }

    private function validateClientData(array $data, ?ClientEntity $existing = null): void
    {
        // Validate redirect URIs
        if (isset($data['redirect_uris'])) {
            if (count($data['redirect_uris']) > $this->config['max_redirect_uris']) {
                throw new ClientException('Too many redirect URIs');
            }

            foreach ($data['redirect_uris'] as $uri) {
                if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                    throw new ClientException('Invalid redirect URI: ' . $uri);
                }
            }
        }

        // Validate grant types
        if (isset($data['grant_types'])) {
            foreach ($data['grant_types'] as $grant) {
                if (!in_array($grant, $this->config['allowed_grant_types'])) {
                    throw new ClientException('Invalid grant type: ' . $grant);
                }
            }
        }

        // Additional validations...
    }

    private function generateClientId(): string
    {
        return bin2hex(random_bytes($this->config['client_id_length'] / 2));
    }

    private function generateClientSecret(): string
    {
        return bin2hex(random_bytes($this->config['client_secret_length'] / 2));
    }

    private function hashSecret(string $secret): string
    {
        return password_hash($secret, PASSWORD_ARGON2ID);
    }
} 
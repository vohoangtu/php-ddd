<?php

namespace App\Shared\Infrastructure\Security\SSO\Server\Client;

use App\Shared\Infrastructure\Database\DatabaseInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use App\Shared\Infrastructure\Security\SSO\Server\Client\Encryption\CredentialEncryption;

class ClientRepository implements ClientRepositoryInterface
{
    private DatabaseInterface $database;
    private CacheInterface $cache;
    private CredentialEncryption $encryption;

    public function __construct(
        DatabaseInterface $database,
        CacheInterface $cache,
        CredentialEncryption $encryption
    ) {
        $this->database = $database;
        $this->cache = $cache;
        $this->encryption = $encryption;
    }

    public function getClientEntity(
        string $clientId,
        ?string $grantType = null,
        ?string $clientSecret = null,
        bool $mustValidateSecret = true
    ): ?ClientEntity {
        $client = $this->getFromCache($clientId);
        
        if (!$client) {
            $client = $this->getFromDatabase($clientId);
            if ($client) {
                $this->cache->set("client:{$clientId}", $client, 3600);
            }
        }

        if (!$client) {
            return null;
        }

        if ($mustValidateSecret && !$this->validateClient($client, $clientSecret)) {
            return null;
        }

        if ($grantType && !in_array($grantType, $client->getAllowedGrantTypes())) {
            return null;
        }

        return $client;
    }

    private function validateClient(ClientEntity $client, ?string $secret): bool
    {
        if (!$client->isConfidential()) {
            return true;
        }

        if (!$secret) {
            return false;
        }

        $encryptedCredentials = $this->getEncryptedCredentials($client->getIdentifier());
        if (!$encryptedCredentials) {
            return false;
        }

        $decryptedCredentials = $this->encryption->decryptCredentials($encryptedCredentials);
        
        return hash_equals(
            $decryptedCredentials['client_secret'],
            $secret
        );
    }

    private function getFromCache(string $clientId): ?ClientEntity
    {
        $data = $this->cache->get("client:{$clientId}");
        return $data ? new ClientEntity($data) : null;
    }

    private function getFromDatabase(string $clientId): ?ClientEntity
    {
        $data = $this->database->table('oauth_clients')
            ->where('client_id', $clientId)
            ->where('active', true)
            ->first();

        return $data ? new ClientEntity((array)$data) : null;
    }
    
    public function getClientCredentials($clientIdentifier): ?array
    {
        $client = $this->getClientEntity($clientIdentifier);
        
        if (!$client) {
            return null;
        }

        return [
            'client_id' => $client->getIdentifier(),
            'client_secret' => $client->getSecret(),
            'redirect_uris' => $client->getRedirectUri(),
            'is_confidential' => $client->isConfidential(),
            'grant_types' => $client->getAllowedGrantTypes(),
            'scopes' => $client->getScopes()
        ];
    }

    public function saveClient(ClientEntity $client): void
    {
        $credentials = [
            'client_secret' => $client->getSecret(),
            'additional_keys' => $client->getAdditionalKeys(),
            'metadata' => $client->getMetadata()
        ];

        $encryptedCredentials = $this->encryption->encryptCredentials($credentials);

        $this->database->transaction(function() use ($client, $encryptedCredentials) {
            $this->database->table('oauth_clients')->insert([
                'client_id' => $client->getIdentifier(),
                'encrypted_credentials' => $encryptedCredentials->toJson(),
                'fingerprint' => $encryptedCredentials->getFingerprint(),
                'name' => $client->getName(),
                'redirect_uris' => json_encode($client->getRedirectUri()),
                'grant_types' => json_encode($client->getAllowedGrantTypes()),
                'scopes' => json_encode($client->getScopes()),
                'is_confidential' => $client->isConfidential(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->database->table('oauth_client_secrets')->insert([
                'client_id' => $client->getIdentifier(),
                'encrypted_key' => $encryptedCredentials->getEncryptedKey(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        });

        $this->cache->delete("client:{$client->getIdentifier()}");
    }

    private function getEncryptedCredentials(string $clientId): ?EncryptedCredentials
    {
        $data = $this->database->table('oauth_clients')
            ->select(['encrypted_credentials', 'fingerprint'])
            ->where('client_id', $clientId)
            ->first();

        if (!$data) {
            return null;
        }

        $key = $this->database->table('oauth_client_secrets')
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->value('encrypted_key');

        return new EncryptedCredentials(
            json_decode($data->encrypted_credentials, true),
            $key,
            $data->fingerprint
        );
    }

    private function rotateClientKey(string $clientId): void
    {
        $client = $this->getClientEntity($clientId);
        if (!$client) {
            throw new ClientException('Client not found');
        }

        $credentials = [
            'client_secret' => $client->getSecret(),
            'additional_keys' => $client->getAdditionalKeys(),
            'metadata' => $client->getMetadata()
        ];

        $encryptedCredentials = $this->encryption->encryptCredentials($credentials);

        $this->database->transaction(function() use ($clientId, $encryptedCredentials) {
            $this->database->table('oauth_clients')
                ->where('client_id', $clientId)
                ->update([
                    'encrypted_credentials' => $encryptedCredentials->toJson(),
                    'fingerprint' => $encryptedCredentials->getFingerprint(),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            $this->database->table('oauth_client_secrets')->insert([
                'client_id' => $clientId,
                'encrypted_key' => $encryptedCredentials->getEncryptedKey(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        });

        $this->cache->delete("client:{$clientId}");
    }
} 
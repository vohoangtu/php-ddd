<?php
namespace App\Shared\Infrastructure\Security\SSO\Security;

use App\Shared\Infrastructure\Security\Encryption\EncryptionInterface;
use App\Shared\Infrastructure\Cache\CacheInterface;
use App\Shared\Infrastructure\Security\SSO\Session\SessionException;

class SecurityEnhancer
{
    private EncryptionInterface $encryption;
    private CacheInterface $cache;
    private array $config;

    public function __construct(
        EncryptionInterface $encryption,
        CacheInterface $cache,
        array $config = []
    ) {
        $this->encryption = $encryption;
        $this->cache = $cache;
        $this->config = array_merge([
            'max_failed_attempts' => 5,
            'lockout_duration' => 900,
            'ip_rate_limit' => 100,
            'ip_rate_window' => 3600,
            'require_2fa' => true,
            'session_binding' => true,
            'fingerprint_check' => true
        ], $config);
    }

    public function validateRequest(array $request, array $context): bool
    {
        // IP-based rate limiting
        if (!$this->checkIpRateLimit($request['ip'])) {
            throw new SecurityException('Rate limit exceeded');
        }

        // Device fingerprint validation
        if ($this->config['fingerprint_check'] && !$this->validateFingerprint($request, $context)) {
            throw new SecurityException('Invalid device fingerprint');
        }

        // Geolocation anomaly detection
        if (!$this->validateGeolocation($request, $context)) {
            $this->flagSuspiciousActivity($request, 'geolocation_anomaly');
            return false;
        }

        return true;
    }

    public function bindSession(string $sessionId, array $context): string
    {
        $binding = [
            'user_agent' => $context['user_agent'],
            'ip' => $context['ip'],
            'fingerprint' => $this->generateFingerprint($context),
            'timestamp' => time()
        ];

        $bindingToken = $this->encryption->encrypt(json_encode($binding));
        $this->cache->set("session_binding:{$sessionId}", $bindingToken);

        return $bindingToken;
    }

    public function validateSessionBinding(string $sessionId, array $context): bool
    {
        $bindingToken = $this->cache->get("session_binding:{$sessionId}");
        if (!$bindingToken) {
            return false;
        }

        try {
            $binding = json_decode($this->encryption->decrypt($bindingToken), true);
            
            return $binding['fingerprint'] === $this->generateFingerprint($context) &&
                   $binding['ip'] === $context['ip'];
        } catch (\Exception $e) {
            return false;
        }
    }

    private function generateFingerprint(array $context): string
    {
        return hash('sha256', json_encode([
            $context['user_agent'] ?? '',
            $context['ip'] ?? '',
            $context['device_id'] ?? ''
        ]));
    }

    private function checkIpRateLimit(string $ip): bool
    {
        $key = "rate_limit:ip:{$ip}";
        $attempts = (int)$this->cache->get($key, 0);
        
        if ($attempts >= $this->config['ip_rate_limit']) {
            return false;
        }
        
        $this->cache->increment($key);
        if ($attempts === 0) {
            $this->cache->expire($key, $this->config['ip_rate_window']);
        }
        
        return true;
    }

    private function validateGeolocation(array $request, array $context): bool
    {
        if (!isset($context['last_location'])) {
            return true;
        }

        $distance = $this->calculateDistance(
            $context['last_location'],
            $request['location']
        );

        $timeDiff = time() - $context['last_location']['timestamp'];
        $maxSpeed = 1000; // km/h

        return $distance / ($timeDiff / 3600) <= $maxSpeed;
    }

    private function calculateDistance(array $loc1, array $loc2): float
    {
        $lat1 = deg2rad($loc1['latitude']);
        $lon1 = deg2rad($loc1['longitude']);
        $lat2 = deg2rad($loc2['latitude']);
        $lon2 = deg2rad($loc2['longitude']);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat/2) * sin($dlat/2) +
             cos($lat1) * cos($lat2) *
             sin($dlon/2) * sin($dlon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return 6371 * $c; // Earth's radius in km
    }

    public function flagSuspiciousActivity(array $request, string $reason): void
    {
        $this->cache->lpush('suspicious_activity', [
            'timestamp' => time(),
            'ip' => $request['ip'],
            'user_id' => $request['user_id'] ?? null,
            'reason' => $reason,
            'context' => $request
        ]);
    }

    public function validateSession(string $sessionId, array $context): bool
    {
        if ($this->config['session_binding']) {
            return $this->validateSessionBinding($sessionId, $context);
        }
        return true;
    }
}
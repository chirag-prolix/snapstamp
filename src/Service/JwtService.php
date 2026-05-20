<?php

namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Cache\CacheItemPoolInterface;
use Ramsey\Uuid\Uuid;

class JwtService
{
    public function __construct(
        private readonly string $privateKeyPath,
        private readonly string $publicKeyPath,
        private readonly CacheItemPoolInterface $cache,
        private readonly int $accessTtl,
        private readonly int $refreshTtl,
    ) {}

    public function generateAccessToken(User $user): string
    {
        $now = time();
        $payload = [
            'sub'   => $user->getId(),
            'email' => $user->getEmail(),
            'role'  => $this->getPrimaryRole($user),
            'type'  => 'access',
            'jti'   => Uuid::uuid4()->toString(),
            'iat'   => $now,
            'exp'   => $now + $this->accessTtl,
        ];

        return JWT::encode($payload, $this->readPrivateKey(), 'RS256');
    }

    public function generateRefreshToken(User $user): string
    {
        $now = time();
        $payload = [
            'sub'  => $user->getId(),
            'type' => 'refresh',
            'jti'  => Uuid::uuid4()->toString(),
            'iat'  => $now,
            'exp'  => $now + $this->refreshTtl,
        ];

        return JWT::encode($payload, $this->readPrivateKey(), 'RS256');
    }

    public function decode(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->readPublicKey(), 'RS256'));
        return (array) $decoded;
    }

    public function isBlacklisted(string $jti): bool
    {
        return $this->cache->getItem($this->blacklistKey($jti))->isHit();
    }

    public function blacklist(string $jti, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }
        $item = $this->cache->getItem($this->blacklistKey($jti));
        $item->set(true);
        $item->expiresAfter($ttl);
        $this->cache->save($item);
    }

    private function getPrimaryRole(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_MERCHANT', $roles, true)) {
            return 'ROLE_MERCHANT';
        }
        if (in_array('ROLE_CUSTOMER', $roles, true)) {
            return 'ROLE_CUSTOMER';
        }
        return 'ROLE_USER';
    }

    private function blacklistKey(string $jti): string
    {
        return 'blacklist.' . str_replace('-', '_', $jti);
    }

    private function readPrivateKey(): string
    {
        return (string) file_get_contents($this->privateKeyPath);
    }

    private function readPublicKey(): string
    {
        return (string) file_get_contents($this->publicKeyPath);
    }
}

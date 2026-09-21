<?php
// Senast uppdaterad: 2026-09-20 18:08 | DB-backed privacy-preserving rate limiter

declare(strict_types=1);
namespace ThaiNews\Security;

use PDO;

final class RateLimiter
{
    public function __construct(private PDO $pdo, private string $secret) {}

    public function clientKey(string $route, string $raw): string
    {
        return hash_hmac('sha256', $route . '|' . $raw, $this->secret);
    }

    public function allow(string $route, string $keyHash, int $limit, int $windowSeconds): bool
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $bucket = intdiv($now->getTimestamp(), $windowSeconds) * $windowSeconds;
        $start = gmdate('Y-m-d H:i:s', $bucket);
        $expires = gmdate('Y-m-d H:i:s', $bucket + $windowSeconds + 60);
        $sql = "INSERT INTO api_rate_limits(route, client_key_hash, window_start, request_count, expires_at)
                VALUES(?,?,?,?,?)
                ON DUPLICATE KEY UPDATE request_count=request_count+1, expires_at=VALUES(expires_at)";
        $this->pdo->prepare($sql)->execute([$route, $keyHash, $start, 1, $expires]);
        $stmt = $this->pdo->prepare('SELECT request_count FROM api_rate_limits WHERE route=? AND client_key_hash=? AND window_start=?');
        $stmt->execute([$route, $keyHash, $start]);
        return (int)$stmt->fetchColumn() <= $limit;
    }
}

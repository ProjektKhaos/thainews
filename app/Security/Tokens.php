<?php
// Senast uppdaterad: 2026-09-20 18:08 | Signed tokens

declare(strict_types=1);
namespace ThaiNews\Security;

final class Tokens
{
    public static function unsubscribeSignature(int $subscriberId, int $version, string $secret): string
    {
        return self::b64url(hash_hmac('sha256', $subscriberId . ':' . $version, $secret, true));
    }
    public static function verifyUnsubscribe(int $subscriberId, int $version, string $signature, string $secret): bool
    {
        return hash_equals(self::unsubscribeSignature($subscriberId, $version, $secret), $signature);
    }
    private static function b64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

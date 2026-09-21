<?php
// Senast uppdaterad: 2026-09-20 18:12 | Anonymous visitor identity

declare(strict_types=1);
namespace ThaiNews\Preferences;

final class VisitorIdentity
{
    public static function raw(string $cookiePath = '/', bool $secure = true): string
    {
        $existing = $_COOKIE['tn_visitor'] ?? '';
        if (is_string($existing) && preg_match('/^[A-Za-z0-9_-]{43}$/', $existing)) return $existing;
        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        setcookie('tn_visitor', $raw, [
            'expires' => time() + 31536000, 'path' => $cookiePath ?: '/',
            'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax'
        ]);
        $_COOKIE['tn_visitor'] = $raw;
        return $raw;
    }
    public static function hash(string $raw, string $secret): string { return hash_hmac('sha256', $raw, $secret); }
}

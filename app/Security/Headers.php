<?php
// Senast uppdaterad: 2026-09-20 18:08 | Security headers

declare(strict_types=1);
namespace ThaiNews\Security;

final class Headers
{
    public static function send(string $nonce, bool $noindex = false): void
    {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self'; script-src 'self' 'nonce-{$nonce}'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; upgrade-insecure-requests");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        if ($noindex) header('X-Robots-Tag: noindex, nofollow');
    }
}

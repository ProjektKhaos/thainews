<?php
// Senast uppdaterad: 2026-09-20 18:08 | CSRF helper

declare(strict_types=1);
namespace ThaiNews\Security;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return (string)$_SESSION['csrf'];
    }
    public static function validate(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf']) && hash_equals((string)$_SESSION['csrf'], $token);
    }
}

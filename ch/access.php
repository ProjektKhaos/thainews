<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);

// En enkel sessionsspärr för Cosmos House publika sidor.
function cosmos_project_base(int $levels): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = dirname($script, $levels);
    return $base === '/' || $base === '.' ? '' : rtrim($base, '/');
}

function cosmos_project_url(int $levels, string $path = ''): string {
    return cosmos_project_base($levels) . '/' . ltrim($path, '/');
}

function cosmos_access_session(int $levels): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: frame-ancestors 'none'; object-src 'none'");
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '28800');
    session_name('cosmos_house_access');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => cosmos_project_url($levels),
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function cosmos_require_access(int $levels, bool $api = false): void {
    cosmos_access_session($levels);
    header('Cache-Control: private, no-store');
    if (!empty($_SESSION['cosmos_site_access'])) return;
    if ($api) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Logga in för att fortsätta.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $next = $_SERVER['REQUEST_URI'] ?? cosmos_project_url($levels, 'en/');
    header('Location: ' . cosmos_project_url($levels, 'login.php') . '?next=' . rawurlencode($next), true, 302);
    exit;
}

function cosmos_safe_next(mixed $candidate, int $levels): string {
    $fallback = cosmos_project_url($levels, 'en/');
    if (!is_string($candidate) || $candidate === '' || preg_match('/[\x00-\x1f\\\\]/', $candidate)) return $fallback;
    $parts = parse_url($candidate);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['fragment'])) return $fallback;
    $path = $parts['path'] ?? '';
    $base = cosmos_project_base($levels);
    if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '..')) return $fallback;
    if ($base !== '' && $path !== $base && !str_starts_with($path, $base . '/')) return $fallback;
    if ($path === cosmos_project_url($levels, 'login.php')) return $fallback;
    return $candidate;
}

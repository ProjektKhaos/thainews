<?php
// Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | Version 1.00 | av: KlⒶssⓔ & Ⓐberg
/** Gemensam uppstart: session, databas, formulärskydd och HTML-hjälpare. */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/markets.php';

// Besök och visning av datum utgår från lokal tid i Chiang Mai.
date_default_timezone_set('Asia/Bangkok');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('cm_market_journal');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/review_translation.php';
market_language();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = 'index.php', array $query = []): string
{
    // Baserar länkar på projektets faktiska sökväg – fungerar även i undermapp.
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    $url = ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
    if ($query) $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return $url;
}

function go(string $path = 'index.php', array $query = []): never
{
    header('Location: ' . app_url($path, $query), true, 303);
    exit;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException(tr('PHP-tillägget pdo_sqlite saknas. Aktivera/installera php-sqlite3 på servern.'));
    }
    if (!is_dir(STORAGE_DIR) && !mkdir(STORAGE_DIR, 0700, true) && !is_dir(STORAGE_DIR)) {
        throw new RuntimeException(tr('Kunde inte skapa lagringsmappen storage/.'));
    }
    foreach ([STORAGE_DIR . '/photos', STORAGE_DIR . '/tmp'] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException(tr('Kunde inte skapa lagringsmappen: %s', basename($dir)));
        }
    }
    try {
        $pdo = new PDO('sqlite:' . STORAGE_DIR . '/market_journal.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys=ON');
        $pdo->exec('PRAGMA busy_timeout=5000');
        $pdo->exec('CREATE TABLE IF NOT EXISTS admins (id INTEGER PRIMARY KEY, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, market_slug TEXT NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5), visited_on TEXT, created_at TEXT NOT NULL, updated_at TEXT NOT NULL)');
        $reviewColumns = $pdo->query('PRAGMA table_info(reviews)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('source_language', $reviewColumns, true)) {
            $pdo->exec("ALTER TABLE reviews ADD COLUMN source_language TEXT NOT NULL DEFAULT 'sv'");
        }
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_reviews_market ON reviews(market_slug, created_at DESC)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS review_translations (review_id INTEGER NOT NULL REFERENCES reviews(id) ON DELETE CASCADE, target_language TEXT NOT NULL, source_hash TEXT NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, updated_at TEXT NOT NULL, PRIMARY KEY(review_id, target_language))');
        $pdo->exec('CREATE TABLE IF NOT EXISTS photos (id INTEGER PRIMARY KEY AUTOINCREMENT, review_id INTEGER NOT NULL REFERENCES reviews(id) ON DELETE CASCADE, filename TEXT NOT NULL UNIQUE, mime TEXT NOT NULL, created_at TEXT NOT NULL)');
        return $pdo;
    } catch (PDOException $e) {
        throw new RuntimeException(tr('Databasen kunde inte öppnas. Kontrollera skrivbehörigheten på storage/ och att pdo_sqlite är aktiverat.'), 0, $e);
    }
}

function is_installed(): bool
{
    // Tillåt att startsidan visar felsökningsmeddelande om SQLite saknas.
    try { return (bool)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn(); }
    catch (Throwable $e) { return false; }
}

function is_admin(): bool
{
    return is_installed() && !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) go('admin.php');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit(tr('Formuläret har gått ut. Ladda om sidan och försök igen.'));
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $value = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $value;
}

function date_se(string $value): string
{
    $t = strtotime($value);
    if ($t === false) return $value;
    if (market_language() === 'en') return date('j M Y', $t);
    $months = [1=>'jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
    return date('j', $t) . ' ' . $months[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function mb_safe_lower(string $text): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower(str_replace(['Å','Ä','Ö'], ['å','ä','ö'], $text));
}

function stars(int $value): string
{
    return str_repeat('★', max(0, min(5, $value))) . str_repeat('☆', max(0, 5 - $value));
}

function validate_media(string $tmp, int $size): string
{
    if ($size < 1) throw new RuntimeException(tr('Filen får inte vara tom.'));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        if ($size > MAX_IMAGE_BYTES) throw new RuntimeException(tr('En bild får vara högst 25 MB.'));
        $dimensions = @getimagesize($tmp);
        if (!$dimensions || ($dimensions['mime'] ?? '') !== $mime || $dimensions[0] > 16000 || $dimensions[1] > 16000 || ($dimensions[0] * $dimensions[1]) > 60000000) {
            throw new RuntimeException(tr('Ogiltig bild eller för stora bilddimensioner.'));
        }
    } elseif (in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'], true)) {
        if ($size > MAX_VIDEO_BYTES) throw new RuntimeException(tr('En video får vara högst 200 MB.'));
        $handle = fopen($tmp, 'rb');
        if ($handle === false) throw new RuntimeException(tr('Ogiltig videofil.'));
        try { $signature = fread($handle, 16); }
        finally { fclose($handle); }
        $isWebm = $mime === 'video/webm' && str_starts_with($signature, "\x1A\x45\xDF\xA3");
        $isMp4 = $mime !== 'video/webm' && substr($signature, 4, 4) === 'ftyp';
        if (!$isWebm && !$isMp4) throw new RuntimeException(tr('Ogiltig videofil.'));
    } else {
        throw new RuntimeException(tr('Bara JPG, PNG, WebP, MP4, WebM och MOV är tillåtna.'));
    }
    return $mime;
}

function media_extension(string $mime): string
{
    return ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'][$mime] ?? 'bin';
}

function store_uploaded_media(string $tmp, string $mime): string
{
    $filename = bin2hex(random_bytes(16)) . '.' . media_extension($mime);
    $dest = STORAGE_DIR . '/photos/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) throw new RuntimeException(tr('Kunde inte spara filen på servern.'));
    @chmod($dest, 0600);
    return $filename;
}

function view(string $file, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/views/' . $file;
}

function market_images(): array
{
    static $images = null;
    if ($images === null) {
        $json = file_get_contents(__DIR__ . '/../assets/market-images/manifest.json');
        $images = is_string($json) ? (json_decode($json, true) ?: []) : [];
    }
    return $images;
}

function market_image(string $slug): ?array
{
    return market_images()[$slug] ?? null;
}

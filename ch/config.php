<?php
// Senast uppdaterad: 2026-10-09 18:30 (Asia/Bangkok) | KlⒶssⓔ & Ⓐberg
// KONFIGURATION: Uppgifterna nedan är DEMO-VÄRDEN tills husägaren bekräftar dem.
declare(strict_types=1);
date_default_timezone_set('Asia/Bangkok');
const SITE_NAME = 'Cosmos House';
const CURRENCY = 'THB';
const NIGHTLY_RATE = 2800; // EXEMPELPRIS – ersätt före lansering.
const CLEANING_FEE = 600; // EXEMPELAVGIFT – ersätt före lansering.
const MAX_GUESTS = 4; // Bekräfta verklig kapacitet före publicering.
const MIN_NIGHTS = 1;
const MAX_NIGHTS = 90;
// Bokning öppnas först efter verifierade priser, villkor och adminrutiner.
const BOOKING_OPEN = false;
// COSMOS_ADMIN_PASSWORD måste sättas som miljövariabel för att admin ska kunna logga in.
function site_base_path(): string {
    // Statiska språkfiler ligger en eller två nivåer under projektroten.
    // Relativa URL:er gör även undersidorna flyttbara utan ny generering.
    if (defined('COSMOS_STATIC_EXPORT') && COSMOS_STATIC_EXPORT) {
        return (defined('COSMOS_PAGE_SLUG') && COSMOS_PAGE_SLUG !== 'home') ? '../..' : '..';
    }
    // SCRIPT_NAME innehåller skriptets URL, oberoende av katalogens namn och djup.
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = dirname($script);
    return $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');
}
function url(string $path = ''): string {
    return site_base_path() . '/' . ltrim($path, '/');
}
function asset_url(string $path): string { return url('assets/' . ltrim($path, '/')); }
function base_url(string $path = ''): string { return url($path); }
function store_path(): string {
    $configured = getenv('COSMOS_STORAGE_DIR');
    if (!$configured || $configured[0] !== '/') throw new RuntimeException('Privat lagring är inte konfigurerad.');
    $directory = realpath($configured);
    $project = realpath(__DIR__);
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: $project;
    if ($directory === false || !is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Privat lagring saknas eller är inte skrivbar.');
    }
    foreach ([$project, $documentRoot] as $publicRoot) {
        if ($directory === $publicRoot || str_starts_with($directory, $publicRoot . '/')) {
            throw new RuntimeException('Privat lagring får inte ligga i webbroten.');
        }
    }
    return $directory . '/bookings.json';
}
function read_bookings(): array {
    $path = store_path();
    $contents = is_file($path) ? file_get_contents($path) : false;
    $data = $contents === false ? null : json_decode($contents, true);
    if (!is_array($data) || !array_is_list($data)) throw new RuntimeException('Bokningsdata saknas eller är ogiltig.');
    return $data;
}
function date_valid(string $value): bool {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}
function overlaps(string $a, string $b, string $c, string $d): bool { return $a < $d && $c < $b; }
function conflict(array $bookings, string $start, string $end, string $ignore = ''): bool {
    foreach ($bookings as $b) {
        if (($b['status'] ?? '') === 'confirmed' && ($b['id'] ?? '') !== $ignore && overlaps($start, $end, $b['checkin'], $b['checkout'])) return true;
    }
    return false;
}
function json_response(array $result, int $status = 200): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}

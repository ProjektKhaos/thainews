<?php
// Senast uppdaterad: 2026-10-09 18:36 (Asia/Bangkok) | KlⒶssⓔ & Ⓐberg
// Ingången leder till en fristående språkversion. Äldre ?lang-länkar stöds.
declare(strict_types=1);
require __DIR__ . '/access.php';
cosmos_require_access(1);
require __DIR__ . '/config.php';
$language = $_GET['lang'] ?? 'en';
if (!is_string($language) || !in_array($language, ['en', 'th', 'sv'], true)) $language = 'en';
header('Cache-Control: no-store');
// Bara äldre ?lang-länkar behöver ett toppankare för att neutralisera #book.
$legacyAnchor = array_key_exists('lang', $_GET) ? '#top' : '';
header('Location: ' . url($language . '/') . $legacyAnchor, true, 302);
exit;

<?php
// Senast uppdaterad: 2026-10-12 10:00 (Asia/Bangkok) | KlⒶssⓔ & Ⓐberg
// Bygger en fristående PHP-sida med förgenererad HTML och inloggningsspärr.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$language = $argv[1] ?? '';
$page = $argv[2] ?? 'home';
if (!in_array($language, ['en', 'th', 'sv'], true)) {
    fwrite(STDERR, "Använd: php scripts/render_language.php en|th|sv home|house|gallery|experience|location|book\n");
    exit(2);
}
if (!in_array($page, ['home', 'house', 'gallery', 'experience', 'location', 'book'], true)) {
    fwrite(STDERR, "Okänd sida: {$page}\n");
    exit(2);
}
define('COSMOS_STATIC_EXPORT', true);
define('COSMOS_PAGE_LANGUAGE', $language);
define('COSMOS_PAGE_SLUG', $page);
ob_start();
require dirname(__DIR__) . '/page-source.php';
$html = ob_get_clean();
$fieldNumber=0;
$html=preg_replace_callback('/<(h1|h2|p|strong|small)\b([^>]*)>/i',static function(array $match) use(&$fieldNumber):string {
    $fieldNumber++;
    return '<'.$match[1].$match[2].' data-cms="copy-'.sprintf('%02d',$fieldNumber).'">';
},$html);
if (!is_string($html) || !str_contains($html, '<html lang="' . $language . '">')) {
    fwrite(STDERR, "Kunde inte rendera sidan för {$language}.\n");
    exit(1);
}
$directory = dirname(__DIR__) . '/' . $language . ($page === 'home' ? '' : '/' . $page);
if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
    fwrite(STDERR, "Kunde inte skapa katalog för {$language}/{$page}.\n");
    exit(1);
}
$accessPath = $page === 'home' ? '../access.php' : '../../access.php';
$levels = $page === 'home' ? 2 : 3;
$cmsPath=$page==='home'?'../app/Cms.php':'../../app/Cms.php';
$guard = "<?php\n// Senast uppdaterad 2026-10-12 (Asia/Bangkok)\ndeclare(strict_types=1);\nrequire __DIR__ . '/{$accessPath}';\ncosmos_require_access({$levels});\nrequire_once __DIR__ . '/{$cmsPath}';\nheader('Content-Type: text/html; charset=utf-8');\nob_start();\n?>\n";
$output = $guard . ($page === 'book'
    ? str_replace('<!-- COSMOS_RUNTIME_BOOKING -->', "<?php require __DIR__ . '/../../booking_widget.php'; cosmos_booking_widget('{$language}'); ?>", $html)
    : $html);
$output.=$language==='en' ? "<?php echo \\Cosmos\\Cms::apply((string)ob_get_clean(),'page-{$page}'); ?>\n" : "<?php echo ob_get_clean(); ?>\n";
$temp = tempnam($directory, '.index-');
if ($temp === false || file_put_contents($temp, $output) !== strlen($output) || !chmod($temp, 0644) || !rename($temp, $directory . '/index.php')) {
    if ($temp !== false && is_file($temp)) unlink($temp);
    fwrite(STDERR, "Kunde inte spara sidan för {$language}.\n");
    exit(1);
}
$oldHtml = $directory . '/index.html';
if (is_file($oldHtml) && !unlink($oldHtml)) {
    fwrite(STDERR, "Kunde inte ta bort oskyddad HTML: {$oldHtml}\n");
    exit(1);
}
echo "Skapade {$language}/" . ($page === 'home' ? '' : $page . '/') . "index.php\n";

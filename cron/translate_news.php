<?php
// Senast uppdaterad: 2026-09-20 19:10 | Translate and cache article headlines

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/bootstrap.php';

use ThaiNews\Translation\GoogleCloudTranslationProvider;
use ThaiNews\Translation\TranslationRepository;
use ThaiNews\Translation\TranslationService;

$options = getopt('', ['language:', 'limit:', 'dry-run']);
$requested = isset($options['language']) ? (string) $options['language'] : null;
$limit = max(1, min(100, (int) ($options['limit'] ?? config()->get('translation.batch_limit', 50))));
$dryRun = array_key_exists('dry-run', $options);
$translationEnabled = (bool) config()->get('translation.enabled', false);
if (!$translationEnabled && !$dryRun) {
    echo "Headline translation is not configured.\n";
    exit(0);
}
$supported = (array) config()->get('supported_languages', ['en', 'th', 'sv']);
$targets = $requested === null ? $supported : [$requested];
foreach ($targets as $language) {
    if (!in_array($language, $supported, true)) {
        fwrite(STDERR, "Unsupported language.\n");
        exit(64);
    }
}

$lockPath = (string) config()->get('storage.locks', dirname(__DIR__) . '/storage/locks') . '/translate_news.lock';
$lock = fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    logger()->log('translation', 'info', 'translation_skipped_locked');
    exit(0);
}

try {
    $service = new TranslationService(
        new TranslationRepository(db()),
        new GoogleCloudTranslationProvider(config()),
        logger()
    );
    $results = [];
    foreach ($targets as $language) {
        $results[$language] = $service->run((string) $language, $limit, $dryRun);
    }
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $failed = array_sum(array_column($results, 'failed'));
    exit($failed > 0 ? 1 : 0);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}

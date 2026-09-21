<?php
// Senast uppdaterad: 2026-09-20 19:10 | Google Cloud Translation Basic v2 adapter

declare(strict_types=1);

namespace ThaiNews\Translation;

use ThaiNews\Config;

final class GoogleCloudTranslationProvider implements TranslationProviderInterface
{
    private const ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct(private Config $config) {}

    public function name(): string
    {
        return 'google_cloud_v2';
    }

    public function translate(array $texts, string $sourceLanguage, string $targetLanguage): array
    {
        if ($texts === []) {
            return [];
        }

        $supported = (array) $this->config->get('supported_languages', ['en', 'th', 'sv']);
        if (!in_array($sourceLanguage, $supported, true) || !in_array($targetLanguage, $supported, true)) {
            throw new \InvalidArgumentException('Unsupported translation language.');
        }

        $apiKey = $this->config->requireString('translation.api_key');
        $payload = json_encode([
            'q' => array_values($texts),
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $body = '';
        $tooLarge = false;
        $handle = curl_init(self::ENDPOINT . '?key=' . rawurlencode($apiKey));
        if ($handle === false) {
            throw new \RuntimeException('Unable to initialize translation request.');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_CONNECTTIMEOUT => (int) $this->config->get('translation.connect_timeout', 5),
            CURLOPT_TIMEOUT => (int) $this->config->get('translation.timeout', 30),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, &$tooLarge): int {
                if (strlen($body) + strlen($chunk) > 2_000_000) {
                    $tooLarge = true;
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($ok === false || $tooLarge) {
            throw new \RuntimeException('Translation transport failed.');
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Translation provider returned HTTP ' . $status . '.');
        }

        try {
            $decoded = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \RuntimeException('Translation provider returned malformed JSON.');
        }
        $rows = $decoded['data']['translations'] ?? $decoded['translations'] ?? null;
        if (!is_array($rows) || count($rows) !== count($texts)) {
            throw new \RuntimeException('Translation provider returned an unexpected result count.');
        }

        $translated = [];
        foreach ($rows as $row) {
            $text = is_array($row) ? ($row['translatedText'] ?? null) : null;
            if (!is_string($text) || trim($text) === '') {
                throw new \RuntimeException('Translation provider returned an empty result.');
            }
            $translated[] = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $translated;
    }
}

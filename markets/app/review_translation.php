<?php
declare(strict_types=1);

/** Translate author-written reviews and cache each language until the source changes. */
function translated_reviews(PDO $pdo, array $reviews, string $language): array
{
    if ($reviews === []) return $reviews;

    $ids = array_map(static fn(array $review): int => (int)$review['id'], $reviews);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM review_translations WHERE review_id IN ($placeholders) AND target_language = ?");
    $stmt->execute([...$ids, $language]);
    $cached = [];
    foreach ($stmt->fetchAll() as $row) $cached[(int)$row['review_id']] = $row;

    $save = $pdo->prepare("INSERT INTO review_translations (review_id, target_language, source_hash, title, body, updated_at) VALUES (?, ?, ?, ?, ?, datetime('now')) ON CONFLICT(review_id, target_language) DO UPDATE SET source_hash=excluded.source_hash, title=excluded.title, body=excluded.body, updated_at=excluded.updated_at");
    foreach ($reviews as &$review) {
        $sourceLanguage = in_array($review['source_language'] ?? 'sv', ['sv', 'en'], true) ? $review['source_language'] : 'sv';
        if ($sourceLanguage === $language) continue;

        $hash = hash('sha256', $sourceLanguage . "\0" . $review['title'] . "\0" . $review['body']);
        $translation = $cached[(int)$review['id']] ?? null;
        if (!$translation || !hash_equals($translation['source_hash'], $hash)) {
            try {
                $title = translate_review_text($review['title'], $sourceLanguage, $language);
                $body = translate_review_text($review['body'], $sourceLanguage, $language);
                $save->execute([(int)$review['id'], $language, $hash, $title, $body]);
                $translation = ['title' => $title, 'body' => $body];
            } catch (Throwable $error) {
                error_log('Market review translation failed for review ' . (int)$review['id'] . ': ' . $error->getMessage());
                continue;
            }
        }
        $review['display_title'] = $translation['title'];
        $review['display_body'] = $translation['body'];
    }
    unset($review);
    return $reviews;
}

function translate_review_text(string $text, string $sourceLanguage, string $targetLanguage): string
{
    if ($text === '' || $sourceLanguage === $targetLanguage) return $text;
    if (!extension_loaded('curl')) throw new RuntimeException('PHP curl extension is missing');

    // The same text endpoint is used for both directions. POST avoids URL length limits for reviews.
    $url = 'https://translate.googleapis.com/translate_a/single?' . http_build_query([
        'client' => 'gtx', 'sl' => $sourceLanguage, 'tl' => $targetLanguage, 'dt' => 't',
    ]);
    $curl = curl_init($url);
    if ($curl === false) throw new RuntimeException('Unable to initialize translation request');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['q' => $text], '', '&', PHP_QUERY_RFC3986),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if (!is_string($response) || $status !== 200 || strlen($response) > 150000) {
        throw new RuntimeException('Translation service returned HTTP ' . $status);
    }
    $data = json_decode($response, true);
    if (!is_array($data) || !is_array($data[0] ?? null)) throw new RuntimeException('Invalid translation response');
    $segments = [];
    foreach ($data[0] as $segment) {
        if (is_array($segment) && is_string($segment[0] ?? null)) $segments[] = $segment[0];
    }
    $translated = implode('', $segments);
    if (trim($translated) === '') throw new RuntimeException('Empty translation response');
    return $translated;
}

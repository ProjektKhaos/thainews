<?php
// Senast uppdaterad: 2026-09-20 19:10 | Cached article-title translations

declare(strict_types=1);

namespace ThaiNews\Translation;

use PDO;

final class TranslationRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function pending(string $targetLanguage, int $limit): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT a.id,a.title,s.source_language
                FROM articles a
                JOIN news_sources s ON s.id=a.source_id AND s.enabled=1
                LEFT JOIN article_translations t ON t.article_id=a.id AND t.language=?
                WHERE s.source_language<>?
                  AND (t.article_id IS NULL OR t.source_text_hash<>SHA2(a.title,256)
                       OR (t.status='failed' AND (t.next_attempt_at IS NULL OR t.next_attempt_at<=UTC_TIMESTAMP())))
                ORDER BY a.published_at DESC,a.id DESC
                LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$targetLanguage, $targetLanguage]);
        return $stmt->fetchAll();
    }

    /** @return array{translated_title:string,provider:string}|null */
    public function findReusable(string $sourceLanguage, string $targetLanguage, string $sourceTitle): ?array
    {
        $stmt = $this->pdo->prepare('SELECT translated_text translated_title,provider FROM translation_memory WHERE source_language=? AND target_language=? AND source_text_hash=? AND source_text=? LIMIT 1');
        $stmt->execute([$sourceLanguage, $targetLanguage, hash('sha256', $sourceTitle), $sourceTitle]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveSuccess(int $articleId, string $sourceLanguage, string $language, string $sourceTitle, string $translatedTitle, string $provider): void
    {
        $sourceTitle = mb_strimwidth($sourceTitle, 0, 1000, '', 'UTF-8');
        $translatedTitle = mb_strimwidth($translatedTitle, 0, 1000, '', 'UTF-8');
        $memory = "INSERT INTO translation_memory(source_language,target_language,source_text_hash,source_text,translated_text,provider,created_at,updated_at)
                   VALUES(?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())
                   ON DUPLICATE KEY UPDATE source_text=VALUES(source_text),translated_text=VALUES(translated_text),provider=VALUES(provider),updated_at=UTC_TIMESTAMP()";
        $this->pdo->prepare($memory)->execute([$sourceLanguage, $language, hash('sha256', $sourceTitle), $sourceTitle, $translatedTitle, $provider]);
        $sql = "INSERT INTO article_translations(article_id,language,translated_title,source_text_hash,provider,status,attempts,last_error_code,next_attempt_at,translated_at,created_at,updated_at)
                VALUES(?,?,?,?,?,'success',1,NULL,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE translated_title=VALUES(translated_title),source_text_hash=VALUES(source_text_hash),provider=VALUES(provider),status='success',attempts=attempts+1,last_error_code=NULL,next_attempt_at=NULL,translated_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP()";
        $this->pdo->prepare($sql)->execute([
            $articleId,
            $language,
            $translatedTitle,
            hash('sha256', $sourceTitle),
            $provider,
        ]);
    }

    public function saveFailure(int $articleId, string $language, string $sourceTitle, string $provider, string $errorCode): void
    {
        $sql = "INSERT INTO article_translations(article_id,language,translated_title,source_text_hash,provider,status,attempts,last_error_code,next_attempt_at,translated_at,created_at,updated_at)
                VALUES(?,?,NULL,?,?,'failed',1,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE),NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE translated_title=NULL,source_text_hash=VALUES(source_text_hash),provider=VALUES(provider),status='failed',attempts=attempts+1,last_error_code=VALUES(last_error_code),next_attempt_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 MINUTE),updated_at=UTC_TIMESTAMP()";
        $this->pdo->prepare($sql)->execute([$articleId, $language, hash('sha256', $sourceTitle), $provider, $errorCode]);
    }
}

<?php
// Senast uppdaterad: 2026-09-20 18:12 | News persistence/query layer

declare(strict_types=1);
namespace ThaiNews\News;

use PDO;

final class NewsRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<SourceDefinition> */
    public function enabledSources(?string $slug = null): array
    {
        $sql = 'SELECT * FROM news_sources WHERE enabled=1' . ($slug !== null ? ' AND slug=?' : '') . ' ORDER BY default_order, id';
        $stmt = $this->pdo->prepare($sql); $stmt->execute($slug !== null ? [$slug] : []);
        $rows = $stmt->fetchAll();
        return array_map(fn(array $r) => new SourceDefinition(
            (int)$r['id'], (string)$r['slug'], (string)$r['name'], (string)$r['website_url'],
            (string)$r['feed_url'], (string)$r['adapter_type'], (string)$r['source_language'],
            (int)$r['default_order'], json_decode((string)($r['adapter_config'] ?? '{}'), true) ?: []
        ), $rows);
    }

    /** @return array{inserted:int,updated:int} */
    public function upsertArticle(SourceDefinition $source, ArticleCandidate $a, bool $dryRun = false): array
    {
        $hash = hash('sha256', $a->url);
        $check = $this->pdo->prepare('SELECT id FROM articles WHERE source_id=? AND (external_id=? OR url_hash=?) LIMIT 1');
        $check->execute([$source->id, $a->externalId, $hash]);
        $exists = $check->fetchColumn() !== false;
        if ($dryRun) return ['inserted' => $exists ? 0 : 1, 'updated' => $exists ? 1 : 0];
        $sql = "INSERT INTO articles(source_id,external_id,canonical_url,url_hash,title,excerpt,image_url,published_at,first_seen_at,last_seen_at,created_at,updated_at)
                VALUES(?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE
                    canonical_url=VALUES(canonical_url), url_hash=VALUES(url_hash), title=VALUES(title),
                    excerpt=IF(VALUES(excerpt)<>'',VALUES(excerpt),excerpt),
                    image_url=IF(VALUES(image_url) IS NOT NULL AND VALUES(image_url)<>'',VALUES(image_url),image_url),
                    published_at=VALUES(published_at), last_seen_at=UTC_TIMESTAMP(), updated_at=UTC_TIMESTAMP()";
        $this->pdo->prepare($sql)->execute([
            $source->id, $a->externalId, $a->url, $hash, $a->title, $a->excerpt, $a->imageUrl,
            $a->publishedAt->format('Y-m-d H:i:s')
        ]);
        return ['inserted' => $exists ? 0 : 1, 'updated' => $exists ? 1 : 0];
    }

    /** @return list<array<string,mixed>> */
    public function latestForSource(int $sourceId, string $language, int $limit = 12): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->pdo->prepare("SELECT a.*, s.name AS source_name, s.slug AS source_slug, s.website_url,
                                           COALESCE(t.translated_title,a.title) AS display_title,
                                           CASE WHEN t.translated_title IS NULL THEN 0 ELSE 1 END AS title_is_translated
                                    FROM articles a JOIN news_sources s ON s.id=a.source_id
                                    LEFT JOIN article_translations t ON t.article_id=a.id AND t.language=? AND t.status='success' AND t.source_text_hash=SHA2(a.title,256)
                                    WHERE a.source_id=? ORDER BY a.published_at DESC, a.id DESC LIMIT {$limit}");
        $stmt->execute([$language,$sourceId]); return $stmt->fetchAll();
    }

    public function startFetchRun(): int
    {
        $this->pdo->exec("INSERT INTO fetch_runs(started_at,status) VALUES(UTC_TIMESTAMP(),'running')");
        return (int)$this->pdo->lastInsertId();
    }
    public function finishFetchRun(int $id, string $status, int $received, int $inserted, int $updated, int $rejected, ?string $errorCode = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE fetch_runs SET finished_at=UTC_TIMESTAMP(),status=?,received_count=?,inserted_count=?,updated_count=?,rejected_count=?,error_code=? WHERE id=?');
        $stmt->execute([$status,$received,$inserted,$updated,$rejected,$errorCode,$id]);
    }
    public function startSourceRun(int $fetchRunId, int $sourceId): int
    {
        $stmt=$this->pdo->prepare("INSERT INTO source_fetch_runs(fetch_run_id,source_id,started_at,status) VALUES(?,?,UTC_TIMESTAMP(),'running')");
        $stmt->execute([$fetchRunId,$sourceId]); return (int)$this->pdo->lastInsertId();
    }
    public function finishSourceRun(int $id, string $status, int $received, int $inserted, int $updated, int $rejected, ?string $errorCode = null): void
    {
        $stmt=$this->pdo->prepare('UPDATE source_fetch_runs SET finished_at=UTC_TIMESTAMP(),status=?,received_count=?,inserted_count=?,updated_count=?,rejected_count=?,error_code=? WHERE id=?');
        $stmt->execute([$status,$received,$inserted,$updated,$rejected,$errorCode,$id]);
    }
}

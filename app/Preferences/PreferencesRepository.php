<?php
// Senast uppdaterad: 2026-09-20 18:12 | Anonymous preferences persistence

declare(strict_types=1);
namespace ThaiNews\Preferences;

use PDO;

final class PreferencesRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return array{language:string,sources:list<array<string,mixed>>} */
    public function get(string $visitorHash, string $defaultLanguage='en'): array
    {
        $stmt=$this->pdo->prepare('SELECT language FROM user_preferences WHERE visitor_hash=?'); $stmt->execute([$visitorHash]);
        $language=(string)($stmt->fetchColumn() ?: $defaultLanguage);
        $sql="SELECT s.id,s.slug,s.name,s.website_url,s.default_order,
                    COALESCE(p.position,s.default_order) AS position,
                    COALESCE(p.visible,1) AS visible
             FROM news_sources s LEFT JOIN user_source_preferences p ON p.source_id=s.id AND p.visitor_hash=?
             WHERE s.enabled=1 ORDER BY position,s.default_order,s.id";
        $stmt=$this->pdo->prepare($sql); $stmt->execute([$visitorHash]);
        return ['language'=>$language,'sources'=>$stmt->fetchAll()];
    }

    /** @param list<array{slug:string,position:int,visible:bool}> $sources */
    public function save(string $visitorHash, string $language, array $sources): void
    {
        $ownsTransaction=!$this->pdo->inTransaction();
        if($ownsTransaction)$this->pdo->beginTransaction();
        try {
            $stmt=$this->pdo->prepare("INSERT INTO user_preferences(visitor_hash,language,created_at,updated_at,last_seen_at)
                                       VALUES(?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())
                                       ON DUPLICATE KEY UPDATE language=VALUES(language),updated_at=UTC_TIMESTAMP(),last_seen_at=UTC_TIMESTAMP()");
            $stmt->execute([$visitorHash,$language]);
            $lookup=$this->pdo->prepare('SELECT id FROM news_sources WHERE enabled=1 AND slug=?');
            $up=$this->pdo->prepare("INSERT INTO user_source_preferences(visitor_hash,source_id,position,visible,created_at,updated_at)
                                     VALUES(?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())
                                     ON DUPLICATE KEY UPDATE position=VALUES(position),visible=VALUES(visible),updated_at=UTC_TIMESTAMP()");
            foreach ($sources as $s) { $lookup->execute([$s['slug']]); $id=$lookup->fetchColumn(); if ($id===false) throw new \InvalidArgumentException('Unknown source'); $up->execute([$visitorHash,(int)$id,(int)$s['position'],$s['visible']?1:0]); }
            if($ownsTransaction)$this->pdo->commit();
        } catch (\Throwable $e) { if ($ownsTransaction&&$this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; }
    }

    public function touchLanguage(string $visitorHash, string $language): void
    {
        $stmt=$this->pdo->prepare("INSERT INTO user_preferences(visitor_hash,language,created_at,updated_at,last_seen_at)
                                   VALUES(?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())
                                   ON DUPLICATE KEY UPDATE language=VALUES(language),updated_at=UTC_TIMESTAMP(),last_seen_at=UTC_TIMESTAMP()");
        $stmt->execute([$visitorHash,$language]);
    }
}

<?php
// Senast uppdaterad: 2026-09-20 18:18 | Subscriber persistence

declare(strict_types=1);
namespace ThaiNews\Subscription;

use PDO;

final class SubscriberRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findByEmailHash(string $hash): ?array
    {
        $stmt=$this->pdo->prepare('SELECT * FROM subscribers WHERE email_hash=? LIMIT 1'); $stmt->execute([$hash]);
        $row=$stmt->fetch(); return $row ?: null;
    }

    public function upsertPending(string $email, string $emailHash, string $language, string $tokenHash, string $expires): int
    {
        $sql="INSERT INTO subscribers(email,email_hash,language,status,confirmation_token_hash,confirmation_expires_at,confirmation_last_sent_at,created_at,updated_at)
              VALUES(?,?,?,'pending',?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())
              ON DUPLICATE KEY UPDATE
                email=VALUES(email),language=VALUES(language),status=IF(status='active','active','pending'),
                confirmation_token_hash=IF(status='active',confirmation_token_hash,VALUES(confirmation_token_hash)),
                confirmation_expires_at=IF(status='active',confirmation_expires_at,VALUES(confirmation_expires_at)),
                confirmation_last_sent_at=IF(status='active',confirmation_last_sent_at,UTC_TIMESTAMP()),updated_at=UTC_TIMESTAMP()";
        $this->pdo->prepare($sql)->execute([$email,$emailHash,$language,$tokenHash,$expires]);
        $stmt=$this->pdo->prepare('SELECT id FROM subscribers WHERE email_hash=?'); $stmt->execute([$emailHash]); return (int)$stmt->fetchColumn();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array { $s=$this->pdo->prepare('SELECT * FROM subscribers WHERE id=?'); $s->execute([$id]); $r=$s->fetch(); return $r ?: null; }

    public function confirm(int $id, string $tokenHash): bool
    {
        $stmt=$this->pdo->prepare("UPDATE subscribers SET status='active',confirmed_at=COALESCE(confirmed_at,UTC_TIMESTAMP()),confirmation_token_hash=NULL,confirmation_expires_at=NULL,updated_at=UTC_TIMESTAMP()
                                   WHERE id=? AND status='pending' AND confirmation_token_hash=? AND confirmation_expires_at>=UTC_TIMESTAMP()");
        $stmt->execute([$id,$tokenHash]); return $stmt->rowCount()===1;
    }

    public function unsubscribe(int $id, int $version): bool
    {
        $stmt=$this->pdo->prepare("UPDATE subscribers SET status='unsubscribed',unsubscribed_at=UTC_TIMESTAMP(),unsubscribe_token_version=unsubscribe_token_version+1,updated_at=UTC_TIMESTAMP() WHERE id=? AND unsubscribe_token_version=?");
        $stmt->execute([$id,$version]); return $stmt->rowCount()===1;
    }

    /** @return list<array<string,mixed>> */
    public function eligibleForSlot(string $slotUtc, int $afterId, int $limit): array
    {
        $limit=max(1,min(250,$limit));
        $stmt=$this->pdo->prepare("SELECT s.* FROM subscribers s
                                  LEFT JOIN digest_log d ON d.subscriber_id=s.id AND d.scheduled_slot=?
                                  WHERE s.status='active' AND s.id>?
                                    AND (d.id IS NULL OR (d.status IN ('claimed','failed') AND d.attempts<3))
                                  ORDER BY s.id LIMIT {$limit}");
        $stmt->execute([$slotUtc,$afterId]);
        return $stmt->fetchAll();
    }
}

<?php
// Senast uppdaterad: 2026-09-20 18:08

declare(strict_types=1);
namespace ThaiNews\News;

final readonly class ArticleCandidate
{
    public function __construct(
        public string $externalId,
        public string $title,
        public string $url,
        public string $excerpt,
        public ?string $imageUrl,
        public \DateTimeImmutable $publishedAt
    ) {}
}

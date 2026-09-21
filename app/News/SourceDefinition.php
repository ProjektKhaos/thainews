<?php
// Senast uppdaterad: 2026-09-20 18:08

declare(strict_types=1);
namespace ThaiNews\News;

final readonly class SourceDefinition
{
    /** @param array<string,mixed> $config */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $websiteUrl,
        public string $feedUrl,
        public string $adapterType,
        public string $sourceLanguage,
        public int $defaultOrder,
        public array $config = []
    ) {}
}

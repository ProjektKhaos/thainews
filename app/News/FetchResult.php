<?php
// Senast uppdaterad: 2026-09-20 18:08

declare(strict_types=1);
namespace ThaiNews\News;

final readonly class FetchResult
{
    /** @param list<ArticleCandidate> $articles @param list<string> $warnings */
    public function __construct(public array $articles, public array $warnings = []) {}
}

<?php
// Senast uppdaterad: 2026-09-20 18:08

declare(strict_types=1);
namespace ThaiNews\News;

interface SourceAdapterInterface
{
    public function fetch(SourceDefinition $source): FetchResult;
}

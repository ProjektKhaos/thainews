<?php
// Senast uppdaterad: 2026-09-20 18:08 | Bangkok Post RSS adapter

declare(strict_types=1);
namespace ThaiNews\News;

use ThaiNews\HttpClient;

final class BangkokPostAdapter implements SourceAdapterInterface
{
    public function __construct(private HttpClient $http, private RssAtomParser $parser) {}
    public function fetch(SourceDefinition $source): FetchResult
    {
        return $this->parser->parse($this->http->get($source->feedUrl));
    }
}

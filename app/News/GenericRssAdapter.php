<?php
// Senast uppdaterad: 2026-09-21 08:58 | Generic RSS/Atom adapter with fallback feeds

declare(strict_types=1);
namespace ThaiNews\News;

use ThaiNews\HttpClient;

final class GenericRssAdapter implements SourceAdapterInterface
{
    public function __construct(private HttpClient $http, private RssAtomParser $parser) {}

    public function fetch(SourceDefinition $source): FetchResult
    {
        $urls = [$source->feedUrl];
        $fallbacks = $source->config['fallback_feed_urls'] ?? [];
        if (is_array($fallbacks)) {
            foreach ($fallbacks as $url) {
                if (is_string($url) && $url !== '' && !in_array($url, $urls, true)) {
                    $urls[] = $url;
                }
            }
        }

        $warnings = [];
        foreach ($urls as $index => $url) {
            try {
                $result = $this->parser->parse($this->http->get($url));
                return new FetchResult(
                    $result->articles,
                    array_merge($warnings, $result->warnings, $index > 0 ? ['Fallback feed used'] : [])
                );
            } catch (\Throwable $e) {
                $warnings[] = 'Feed attempt failed';
            }
        }

        throw new \RuntimeException('All configured feeds failed for source: ' . $source->slug);
    }
}

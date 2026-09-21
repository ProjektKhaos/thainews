<?php
// Senast uppdaterad: 2026-09-20 18:08 | Canonical article URLs

declare(strict_types=1);
namespace ThaiNews\News;

final class UrlNormalizer
{
    private const TRACKING = ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid','mc_cid','mc_eid'];
    public static function normalize(string $url): string
    {
        $parts = parse_url(trim($url));
        if (!$parts || !isset($parts['scheme'],$parts['host'])) throw new \InvalidArgumentException('Invalid article URL');
        if (!in_array(strtolower($parts['scheme']), ['http','https'], true)) throw new \InvalidArgumentException('Unsupported URL scheme');
        $scheme = strtolower($parts['scheme']); $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '/';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (self::TRACKING as $key) unset($query[$key]);
            ksort($query);
        }
        $port = isset($parts['port']) && !in_array($parts['port'], [80,443], true) ? ':' . $parts['port'] : '';
        return $scheme . '://' . $host . $port . $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }
}

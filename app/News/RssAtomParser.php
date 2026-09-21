<?php
// Senast uppdaterad: 2026-09-20 18:08 | RSS/Atom parser with XXE/network protection

declare(strict_types=1);
namespace ThaiNews\News;

final class RssAtomParser
{
    public function parse(string $xml): FetchResult
    {
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            throw new \RuntimeException('Unsafe XML declaration rejected.');
        }
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_NOBLANKS | LIBXML_COMPACT);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if (!$ok) throw new \RuntimeException('Feed XML could not be parsed.');
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
        $xp->registerNamespace('media', 'http://search.yahoo.com/mrss/');
        $nodes = $xp->query('/rss/channel/item | /feed/entry | /atom:feed/atom:entry');
        $out = []; $warnings = [];
        if (!$nodes) return new FetchResult([], ['No feed entries found']);
        foreach ($nodes as $node) {
            try {
                $title = $this->text($xp, './title | ./atom:title', $node);
                $url = $this->text($xp, './link[not(@rel)]', $node);
                if ($url === '') $url = trim((string)$xp->evaluate('string(./atom:link[@rel="alternate"]/@href)', $node));
                if ($url === '') $url = trim((string)$xp->evaluate('string(./link/@href)', $node));
                if ($url === '') $url = $this->text($xp, './guid | ./id | ./atom:id', $node);
                if ($title === '' || $url === '') throw new \RuntimeException('Entry missing title or URL');
                $guid = $this->text($xp, './guid | ./id | ./atom:id', $node);
                $desc = $this->text($xp, './description | ./summary | ./content | ./atom:summary | ./atom:content', $node);
                $date = $this->text($xp, './pubDate | ./published | ./updated | ./atom:published | ./atom:updated', $node);
                $published = $date !== '' ? new \DateTimeImmutable($date) : new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
                $published = $published->setTimezone(new \DateTimeZone('UTC'));
                $image = trim((string)$xp->evaluate('string(./media:content[@url][1]/@url)', $node));
                if ($image === '') $image = trim((string)$xp->evaluate('string(./media:thumbnail[@url][1]/@url)', $node));
                if ($image === '') $image = trim((string)$xp->evaluate('string(./enclosure[starts-with(@type,"image/")][1]/@url)', $node));
                $url = UrlNormalizer::normalize(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($desc), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
                if ($image !== '' && !str_starts_with(strtolower($image), 'https://')) $image = '';
                $external = $guid !== '' ? trim($guid) : hash('sha256', $url);
                if (strlen($external) > 255) $external = hash('sha256', $external);
                $cleanTitle = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $out[] = new ArticleCandidate(
                    $external,
                    mb_strimwidth($cleanTitle, 0, 1000, '', 'UTF-8'),
                    $url,
                    mb_strimwidth($plain, 0, 10000, '', 'UTF-8'),
                    $image !== '' ? $image : null,
                    $published
                );
            } catch (\Throwable $e) {
                $warnings[] = 'Rejected malformed feed entry';
            }
        }
        usort($out, fn(ArticleCandidate $a, ArticleCandidate $b) => $b->publishedAt <=> $a->publishedAt);
        return new FetchResult($out, $warnings);
    }

    private function text(\DOMXPath $xp, string $expr, \DOMNode $node): string
    {
        return trim((string)$xp->evaluate('string((' . $expr . ')[1])', $node));
    }
}

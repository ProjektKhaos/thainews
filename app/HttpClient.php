<?php
// Senast uppdaterad: 2026-09-20 18:05 | Hardened HTTP GET

declare(strict_types=1);

namespace ThaiNews;

final class HttpClient
{
    public function __construct(private Config $config) {}

    public function get(string $url): string
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https') throw new \RuntimeException('Only HTTPS feeds are allowed.');
        $max = (int)$this->config->get('fetch.max_bytes', 2097152);
        $body = '';
        $tooLarge = false;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => (int)$this->config->get('fetch.connect_timeout', 5),
            CURLOPT_TIMEOUT => (int)$this->config->get('fetch.timeout', 20),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => (string)$this->config->get('fetch.user_agent', 'ThaiNewsAggregator/1.0'),
            CURLOPT_HTTPHEADER => ['Accept: application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.2'],
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, &$tooLarge, $max): int {
                if (strlen($body) + strlen($chunk) > $max) { $tooLarge = true; return 0; }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($ok === false) throw new \RuntimeException($tooLarge ? 'Feed response exceeded safety limit.' : 'HTTP fetch failed.');
        if ($status < 200 || $status >= 300) throw new \RuntimeException('Unexpected HTTP status ' . $status);
        return $body;
    }
}

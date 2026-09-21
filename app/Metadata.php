<?php
// Senast uppdaterad: 2026-09-20 18:05 | SEO/OpenGraph metadata

declare(strict_types=1);

namespace ThaiNews;

final class Metadata
{
    /** @return array<string,mixed> */
    public static function build(Config $config, string $title, string $description, string $path = '', string $type = 'website', ?string $image = null): array
    {
        $origin = rtrim((string)$config->get('public_origin'), '/');
        $base = trim((string)$config->get('base_url', ''), '/');
        $prefix = $base === '' ? '' : '/' . $base;
        $canonical = $origin . $prefix . '/' . ltrim($path, '/');
        $imageUrl = $image ?: $origin . $prefix . '/img/thainews_fb_og.png';
        return compact('title','description','type','canonical') + [
            'image' => $imageUrl,
            'image_width' => 1731,
            'image_height' => 909,
            'image_alt' => $title,
        ];
    }
}

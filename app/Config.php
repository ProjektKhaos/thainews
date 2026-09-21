<?php
// Senast uppdaterad: 2026-09-20 18:05 | Thai News initial build

declare(strict_types=1);

namespace ThaiNews;

final class Config
{
    /** @param array<string,mixed> $data */
    public function __construct(private array $data) {}

    public static function load(string $projectRoot): self
    {
        $file = getenv('THAI_NEWS_CONFIG_FILE') ?: '';
        if ($file === '' || !is_file($file)) {
            throw new \RuntimeException('THAI_NEWS_CONFIG_FILE is not configured or does not point to a readable file.');
        }
        $config = require $file;
        if (!is_array($config)) {
            throw new \RuntimeException('Production config must return an array.');
        }
        $config['project_root'] ??= $projectRoot;
        $config['storage'] = array_replace([
            'cache' => $projectRoot . '/storage/cache',
            'locks' => $projectRoot . '/storage/locks',
            'logs' => $projectRoot . '/storage/logs',
        ], is_array($config['storage'] ?? null) ? $config['storage'] : []);
        foreach (['app_secret','rate_limit_secret'] as $secretKey) {
            if (!is_string($config[$secretKey] ?? null) || strlen((string)$config[$secretKey]) < 32) {
                throw new \RuntimeException("{$secretKey} must contain at least 32 bytes.");
            }
        }
        if (($config['env'] ?? 'production') === 'production' && !str_starts_with((string)($config['public_origin'] ?? ''), 'https://')) {
            throw new \RuntimeException('Production public_origin must use HTTPS.');
        }
        return new self($config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return $default;
            $value = $value[$part];
        }
        return $value;
    }

    public function requireString(string $key): string
    {
        $value = $this->get($key);
        if (!is_string($value) || $value === '') throw new \RuntimeException("Missing config value: {$key}");
        return $value;
    }
}

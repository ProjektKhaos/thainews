<?php
// Senast uppdaterad: 2026-09-20 18:05 | Structured JSON logger

declare(strict_types=1);

namespace ThaiNews;

final class Logger
{
    public function __construct(private string $dir) {}

    /** @param array<string,mixed> $context */
    public function log(string $channel, string $level, string $message, array $context = []): void
    {
        if (!is_dir($this->dir)) @mkdir($this->dir, 0770, true);
        $row = [
            'ts' => gmdate('c'), 'level' => $level, 'message' => $message,
            'context' => $this->redact($context),
        ];
        @file_put_contents($this->dir . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $channel) . '.log', json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string,mixed> $data */
    private function redact(array $data): array
    {
        $blocked = ['password','pass','token','secret','authorization','email','smtp'];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string)$key), $blocked, true)) $data[$key] = '[REDACTED]';
            elseif (is_array($value)) $data[$key] = $this->redact($value);
        }
        return $data;
    }
}

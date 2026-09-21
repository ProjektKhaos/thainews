<?php
// Senast uppdaterad: 2026-09-20 18:05 | Minimal PHP view renderer

declare(strict_types=1);

namespace ThaiNews;

final class View
{
    public function __construct(private string $root) {}
    /** @param array<string,mixed> $data */
    public function render(string $name, array $data = []): string
    {
        $file = $this->root . '/includes/views/' . $name . '.php';
        if (!is_file($file)) throw new \RuntimeException('View not found: ' . $name);
        extract($data, EXTR_SKIP);
        ob_start(); require $file; return (string)ob_get_clean();
    }
}

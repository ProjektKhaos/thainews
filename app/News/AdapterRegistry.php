<?php
// Senast uppdaterad: 2026-09-20 18:08 | Adapter registry

declare(strict_types=1);
namespace ThaiNews\News;

final class AdapterRegistry
{
    /** @var array<string,SourceAdapterInterface> */ private array $adapters = [];
    public function register(string $type, SourceAdapterInterface $adapter): void { $this->adapters[$type] = $adapter; }
    public function get(string $type): SourceAdapterInterface
    {
        return $this->adapters[$type] ?? throw new \RuntimeException('Unknown adapter type: ' . $type);
    }
}

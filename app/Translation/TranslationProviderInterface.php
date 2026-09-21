<?php
// Senast uppdaterad: 2026-09-20 19:10 | Translation provider contract

declare(strict_types=1);

namespace ThaiNews\Translation;

interface TranslationProviderInterface
{
    public function name(): string;

    /**
     * @param list<string> $texts
     * @return list<string>
     */
    public function translate(array $texts, string $sourceLanguage, string $targetLanguage): array;
}

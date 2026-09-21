<?php
// Senast uppdaterad: 2026-09-20 18:05 | UI translator, article text remains original

declare(strict_types=1);

namespace ThaiNews;

final class Translator
{
    /** @var array<string,string> */ private array $messages;
    public function __construct(private string $language, private string $projectRoot)
    {
        $file = $projectRoot . '/app/lang/' . $language . '.php';
        $this->messages = is_file($file) ? require $file : require $projectRoot . '/app/lang/en.php';
    }
    public function language(): string { return $this->language; }
    /** @param array<string,string|int> $vars */
    public function get(string $key, array $vars = []): string
    {
        $text = $this->messages[$key] ?? $key;
        foreach ($vars as $k => $v) $text = str_replace('{' . $k . '}', (string)$v, $text);
        return $text;
    }
}

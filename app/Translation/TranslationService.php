<?php
// Senast uppdaterad: 2026-09-20 19:10 | Isolated title-translation worker

declare(strict_types=1);

namespace ThaiNews\Translation;

use ThaiNews\Logger;

final class TranslationService
{
    public function __construct(
        private TranslationRepository $repository,
        private TranslationProviderInterface $provider,
        private Logger $logger
    ) {}

    /** @return array{selected:int,translated:int,failed:int,dry_run:bool} */
    public function run(string $targetLanguage, int $limit = 50, bool $dryRun = false): array
    {
        $rows = $this->repository->pending($targetLanguage, $limit);
        $result = ['selected' => count($rows), 'translated' => 0, 'failed' => 0, 'dry_run' => $dryRun];
        if ($dryRun || $rows === []) {
            return $result;
        }

        foreach (array_chunk($rows, 25) as $chunk) {
            $bySource = [];
            foreach ($chunk as $row) {
                $bySource[(string) $row['source_language']][] = $row;
            }
            foreach ($bySource as $sourceLanguage => $sourceRows) {
                $pendingByHash = [];
                foreach ($sourceRows as $row) {
                    $title = (string) $row['title'];
                    $cached = $this->repository->findReusable($sourceLanguage, $targetLanguage, $title);
                    if ($cached !== null) {
                        $this->repository->saveSuccess((int) $row['id'], $sourceLanguage, $targetLanguage, $title, $cached['translated_title'], $cached['provider']);
                        $result['translated']++;
                        continue;
                    }
                    $hash = hash('sha256', $title);
                    $pendingByHash[$hash] ??= ['title' => $title, 'rows' => []];
                    $pendingByHash[$hash]['rows'][] = $row;
                }
                if ($pendingByHash === []) {
                    continue;
                }
                try {
                    $translations = $this->provider->translate(
                        array_column(array_values($pendingByHash), 'title'),
                        $sourceLanguage,
                        $targetLanguage
                    );
                    foreach (array_values($pendingByHash) as $index => $group) {
                        if (!isset($translations[$index])) {
                            throw new \RuntimeException('Translation provider returned an incomplete batch.');
                        }
                        foreach ($group['rows'] as $row) {
                            $this->repository->saveSuccess(
                                (int) $row['id'],
                                $sourceLanguage,
                                $targetLanguage,
                                (string) $row['title'],
                                $translations[$index],
                                $this->provider->name()
                            );
                            $result['translated']++;
                        }
                    }
                } catch (\Throwable $error) {
                    $code = strtoupper(substr(hash('sha256', get_class($error) . '|' . $error->getMessage()), 0, 12));
                    foreach ($pendingByHash as $group) {
                        foreach ($group['rows'] as $row) {
                            $this->repository->saveFailure((int) $row['id'], $targetLanguage, (string) $row['title'], $this->provider->name(), $code);
                            $result['failed']++;
                        }
                    }
                    $this->logger->log('translation', 'error', 'translation_batch_failed', [
                        'source_language' => $sourceLanguage,
                        'target_language' => $targetLanguage,
                        'count' => array_sum(array_map(static fn(array $group): int => count($group['rows']), $pendingByHash)),
                        'error_code' => $code,
                    ]);
                }
            }
        }
        return $result;
    }
}

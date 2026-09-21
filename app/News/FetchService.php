<?php
// Senast uppdaterad: 2026-09-20 18:12 | Resilient multi-source fetch orchestration

declare(strict_types=1);
namespace ThaiNews\News;

use ThaiNews\Logger;

final class FetchService
{
    public function __construct(private NewsRepository $repo, private AdapterRegistry $registry, private Logger $logger) {}

    /** @return array{sources:int,failed:int,received:int,inserted:int,updated:int,rejected:int} */
    public function run(?string $slug = null, bool $dryRun = false): array
    {
        $sources = $this->repo->enabledSources($slug);
        if ($slug !== null && !$sources) throw new \RuntimeException('Unknown or disabled source.');
        $runId = $dryRun ? null : $this->repo->startFetchRun();
        $totals = ['sources'=>count($sources),'failed'=>0,'received'=>0,'inserted'=>0,'updated'=>0,'rejected'=>0];
        foreach ($sources as $source) {
            $sourceRun = $dryRun ? null : $this->repo->startSourceRun((int)$runId, $source->id);
            $received=$inserted=$updated=$rejected=0;
            try {
                $result = $this->registry->get($source->adapterType)->fetch($source);
                $received = count($result->articles); $totals['received'] += $received;
                foreach ($result->articles as $candidate) {
                    try {
                        $stat = $this->repo->upsertArticle($source, $candidate, $dryRun);
                        $inserted += $stat['inserted']; $updated += $stat['updated'];
                    } catch (\Throwable $e) {
                        $rejected++;
                        $this->logger->log('fetch','warning','article_rejected',['source'=>$source->slug,'error_code'=>self::code($e)]);
                    }
                }
                if($sourceRun!==null)$this->repo->finishSourceRun($sourceRun, 'success', $received, $inserted, $updated, $rejected);
                $this->logger->log('fetch','info','source_complete',['source'=>$source->slug,'received'=>$received,'inserted'=>$inserted,'updated'=>$updated,'rejected'=>$rejected,'warnings'=>count($result->warnings),'dry_run'=>$dryRun]);
            } catch (\Throwable $e) {
                $totals['failed']++;
                if($sourceRun!==null)$this->repo->finishSourceRun($sourceRun, 'failed', $received, $inserted, $updated, $rejected, self::code($e));
                $this->logger->log('fetch','error','source_failed',['source'=>$source->slug,'error_code'=>self::code($e)]);
            }
            $totals['inserted'] += $inserted; $totals['updated'] += $updated; $totals['rejected'] += $rejected;
        }
        $status = $totals['sources'] > 0 && $totals['failed'] === $totals['sources'] ? 'failed' : ($totals['failed'] > 0 ? 'partial' : 'success');
        if($runId!==null)$this->repo->finishFetchRun($runId, $status, $totals['received'],$totals['inserted'],$totals['updated'],$totals['rejected'], $status === 'failed' ? 'ALL_SOURCES_FAILED' : null);
        return $totals;
    }
    private static function code(\Throwable $e): string { return strtoupper(substr(hash('sha256', get_class($e) . '|' . $e->getMessage()),0,12)); }
}

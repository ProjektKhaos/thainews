<?php
// Senast uppdaterad: 2026-09-20 18:18 | Bangkok-time digest slots

declare(strict_types=1);
namespace ThaiNews\Digest;

final class DigestScheduler
{
    /** @param list<string> $times */
    public function __construct(private array $times, private string $timezone='Asia/Bangkok', private int $windowMinutes=30) {}
    public function latestSlot(?\DateTimeImmutable $now=null): ?\DateTimeImmutable
    {
        $tz=new \DateTimeZone($this->timezone); $now=($now??new \DateTimeImmutable('now',$tz))->setTimezone($tz);
        $candidates=[];
        foreach ($this->times as $time) { [$h,$m]=array_map('intval',explode(':',$time)); $candidates[]=$now->setTime($h,$m,0); }
        usort($candidates, static fn(\DateTimeImmutable $a, \DateTimeImmutable $b): int => $b->getTimestamp() <=> $a->getTimestamp());
        foreach ($candidates as $slot) if ($slot <= $now && $slot >= $now->modify('-'.$this->windowMinutes.' minutes')) return $slot;
        return null;
    }
}

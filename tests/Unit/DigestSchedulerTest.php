<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\Digest\DigestScheduler;
final class DigestSchedulerTest extends TestCase{public function testFindsDueBangkokSlot():void{$s=new DigestScheduler(['00:00','06:00','12:00','18:00'],'Asia/Bangkok',30);$slot=$s->latestSlot(new DateTimeImmutable('2026-09-20T12:15:00+07:00'));$this->assertSame('12:00',$slot?->format('H:i'));}}

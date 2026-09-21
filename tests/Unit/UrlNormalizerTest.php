<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\News\UrlNormalizer;
final class UrlNormalizerTest extends TestCase{public function testTrackingAndFragmentAreRemoved():void{$this->assertSame('https://example.com/story?id=7',UrlNormalizer::normalize('https://Example.com/story?utm_source=x&id=7#frag'));}}

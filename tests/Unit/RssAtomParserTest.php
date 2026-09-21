<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\News\RssAtomParser;
final class RssAtomParserTest extends TestCase{
 public function testRssFixture():void{$r=(new RssAtomParser())->parse(file_get_contents(__DIR__.'/../fixtures/rss.xml'));$this->assertCount(1,$r->articles);$this->assertSame('First & best',$r->articles[0]->title);$this->assertSame('Hello world',$r->articles[0]->excerpt);$this->assertSame('https://example.com/a?id=7',$r->articles[0]->url);$this->assertNotEmpty($r->warnings);}
 public function testAtomFixture():void{$r=(new RssAtomParser())->parse(file_get_contents(__DIR__.'/../fixtures/atom.xml'));$this->assertSame('Atom story',$r->articles[0]->title);}
 public function testDoctypeIsRejected():void{$this->expectException(RuntimeException::class);(new RssAtomParser())->parse('<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><rss/>');}
}

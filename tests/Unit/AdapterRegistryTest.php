<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\News\{AdapterRegistry,SourceAdapterInterface,SourceDefinition,FetchResult};
final class AdapterRegistryTest extends TestCase{public function testRegistryReturnsRegisteredAdapter():void{$a=new class implements SourceAdapterInterface{public function fetch(SourceDefinition $source):FetchResult{return new FetchResult([]);}};$r=new AdapterRegistry();$r->register('x',$a);$this->assertSame($a,$r->get('x'));}}

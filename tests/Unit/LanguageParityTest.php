<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase;
final class LanguageParityTest extends TestCase{public function testLanguageKeysMatch():void{$root=dirname(__DIR__,2).'/app/lang/';$en=array_keys(require $root.'en.php');sort($en);foreach(['th','sv'] as $lang){$keys=array_keys(require $root.$lang.'.php');sort($keys);$this->assertSame($en,$keys,$lang.' language keys differ');}}}

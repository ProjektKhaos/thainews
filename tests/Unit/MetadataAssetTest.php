<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\Config; use ThaiNews\Metadata;
final class MetadataAssetTest extends TestCase{
 public function testMetadataFallback():void{$c=new Config(['public_origin'=>'https://thainews.aberg.online','base_url'=>'']);$m=Metadata::build($c,'T','D');$this->assertSame('https://thainews.aberg.online/',$m['canonical']);$this->assertSame('https://thainews.aberg.online/img/thainews_fb_og.png',$m['image']);$this->assertSame(1677,$m['image_width']);$this->assertSame(938,$m['image_height']);}
 public function testSuppliedAssetsRemainByteIdentical():void{$root=dirname(__DIR__,2);$this->assertSame('ab2562cbb4f143806d0151ab7eaae8595634456539b7af4fb438c623e5a12758',hash_file('sha256',$root.'/img/thainews_logo1.png'));$this->assertSame('dfc3e54c09329f95077f2214c8af99089d588f988cd8f6fb2a41e95d9a1f071a',hash_file('sha256',$root.'/img/thainews_fb_og.png'));}
}

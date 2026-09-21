<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\Config; use ThaiNews\Metadata;
final class MetadataAssetTest extends TestCase{
 public function testMetadataFallback():void{$c=new Config(['public_origin'=>'https://thainews.aberg.online','base_url'=>'']);$m=Metadata::build($c,'T','D');$this->assertSame('https://thainews.aberg.online/',$m['canonical']);$this->assertSame('https://thainews.aberg.online/img/thainews_fb_og.png',$m['image']);}
 public function testSuppliedAssetsRemainByteIdentical():void{$root=dirname(__DIR__,2);$this->assertSame('ab2562cbb4f143806d0151ab7eaae8595634456539b7af4fb438c623e5a12758',hash_file('sha256',$root.'/img/thainews_logo1.png'));$this->assertSame('603e9b15979804061d501e7349f0e3def0e2d475c1db05305deb606204af6b9e',hash_file('sha256',$root.'/img/thainews_fb_og.png'));}
}

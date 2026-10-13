<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\Config; use ThaiNews\Metadata;
final class MetadataAssetTest extends TestCase{
 public function testMetadataFallback():void{$c=new Config(['public_origin'=>'https://thainews.aberg.online','base_url'=>'']);$m=Metadata::build($c,'T','D');$this->assertSame('https://thainews.aberg.online/',$m['canonical']);$this->assertSame('https://thainews.aberg.online/img/fb_og.png',$m['image']);$this->assertSame(1731,$m['image_width']);$this->assertSame(909,$m['image_height']);}
 public function testSuppliedAssetsRemainByteIdentical():void{$root=dirname(__DIR__,2);$this->assertSame('ab2562cbb4f143806d0151ab7eaae8595634456539b7af4fb438c623e5a12758',hash_file('sha256',$root.'/img/thainews_logo1.png'));$this->assertSame('a4142fb25dc2909db81dfa45c70bc935c4e7f8285a0a4baf61fa9621ed2a48ef',hash_file('sha256',$root.'/img/fb_og.png'));}
}

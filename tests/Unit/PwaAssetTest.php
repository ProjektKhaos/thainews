<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PwaAssetTest extends TestCase
{
    public function testManifestMeetsCoreInstallabilityRequirements(): void
    {
        $root=dirname(__DIR__,2);
        $manifest=json_decode((string)file_get_contents($root.'/manifest.webmanifest'),true,512,JSON_THROW_ON_ERROR);
        self::assertSame('Thai News',$manifest['name']);
        self::assertSame('/',$manifest['start_url']);
        self::assertSame('/',$manifest['scope']);
        self::assertSame('standalone',$manifest['display']);
        self::assertFalse($manifest['prefer_related_applications']);
        $icons=[];
        foreach($manifest['icons'] as $icon)$icons[$icon['sizes'].'|'.$icon['purpose']]=$icon['src'];
        self::assertArrayHasKey('192x192|any',$icons);
        self::assertArrayHasKey('512x512|any',$icons);
        self::assertArrayHasKey('512x512|maskable',$icons);
    }

    public function testPwaIconsHaveDeclaredDimensions(): void
    {
        $root=dirname(__DIR__,2);
        foreach([
            'img/pwa/icon-192.png'=>[192,192],
            'img/pwa/icon-512.png'=>[512,512],
            'img/pwa/icon-maskable-512.png'=>[512,512],
            'img/pwa/apple-touch-icon.png'=>[180,180],
        ] as $file=>$expected){
            $actual=getimagesize($root.'/'.$file);
            self::assertIsArray($actual,$file);
            self::assertSame($expected[0],$actual[0],$file);
            self::assertSame($expected[1],$actual[1],$file);
        }
    }
}

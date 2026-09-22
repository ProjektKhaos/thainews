<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(77);
}

$root=dirname(__DIR__);
$source=imagecreatefrompng($root.'/img/thainews_logo1.png');
if ($source===false) {
    throw new RuntimeException('Unable to read the source logo.');
}

$targetDirectory=$root.'/img/pwa';
if (!is_dir($targetDirectory) && !mkdir($targetDirectory,0755,true) && !is_dir($targetDirectory)) {
    throw new RuntimeException('Unable to create the PWA icon directory.');
}

$sourceWidth=imagesx($source);
$sourceHeight=imagesy($source);

$writeIcon=static function(string $filename,int $canvasSize,int $logoSize,bool $solidBackground=false) use($source,$sourceWidth,$sourceHeight,$targetDirectory):void {
    $canvas=imagecreatetruecolor($canvasSize,$canvasSize);
    if ($canvas===false) throw new RuntimeException('Unable to allocate icon canvas.');
    imagealphablending($canvas,false);
    imagesavealpha($canvas,true);
    $background=$solidBackground
        ? imagecolorallocate($canvas,245,247,250)
        : imagecolorallocatealpha($canvas,0,0,0,127);
    imagefill($canvas,0,0,$background);
    imagealphablending($canvas,true);
    $offset=(int)(($canvasSize-$logoSize)/2);
    imagecopyresampled($canvas,$source,$offset,$offset,0,0,$logoSize,$logoSize,$sourceWidth,$sourceHeight);
    if (!imagepng($canvas,$targetDirectory.'/'.$filename,9)) {
        throw new RuntimeException('Unable to write '.$filename.'.');
    }
    imagedestroy($canvas);
};

$writeIcon('icon-192.png',192,192);
$writeIcon('icon-512.png',512,512);
$writeIcon('icon-maskable-512.png',512,384,true);
$writeIcon('apple-touch-icon.png',180,164,true);
imagedestroy($source);

fwrite(STDOUT,"Generated PWA icons in img/pwa.\n");

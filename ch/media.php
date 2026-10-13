<?php
declare(strict_types=1);
require __DIR__.'/app/Booking.php';
try {
    $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
    if(!$id || $id<1) throw new RuntimeException();
    $app=\Cosmos\Booking::connect();$q=$app->db->prepare('SELECT filename,mime FROM media WHERE id=?');$q->execute([$id]);$photo=$q->fetch();
    if(!$photo || !preg_match('/^[a-f0-9]{40}\.webp$/',$photo['filename']))throw new RuntimeException();
    $key='upload-'.$id;
    $q=$app->db->prepare("SELECT 1 FROM cms_content WHERE scope='landing' AND key LIKE 'image_%' AND published=? UNION SELECT 1 FROM gallery_photos WHERE photo_key=? AND visible=1");$q->execute([$key,$key]);$public=(bool)$q->fetchColumn();
    if(!$public){
        require __DIR__.'/access.php';cosmos_access_session(1);require __DIR__.'/app/Auth.php';
        if(!(new \Cosmos\Auth($app))->current())throw new RuntimeException();
    }
    $path=dirname((string)getenv('COSMOS_DB_PATH')).'/media/'.$photo['filename'];
    if(!is_file($path))throw new RuntimeException();
    header('Content-Type: image/webp');header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'");
    header('Cache-Control: '.($public?'public, max-age=86400':'private, no-store'));header('Content-Length: '.filesize($path));readfile($path);
}catch(Throwable $e){http_response_code(404);}

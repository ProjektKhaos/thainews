<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/Booking.php';require dirname(__DIR__).'/app/Backup.php';
try {
    $app=\Cosmos\Booking::connect();$backup=new \Cosmos\Backup($app);$name=$backup->create('system');
    $retention=max(7,min(365,(int)$app->setting('backup_retention_days','30')));
    foreach($backup->files()as $file)if(basename($file)!==$name&&!is_link($file)&&filemtime($file)<time()-$retention*86400)unlink($file);
    echo "Verified full backup: ".$name."\n";
}catch(Throwable $e){fwrite(STDERR,'Backend backup failed: '.get_class($e)."\n");exit(1);}

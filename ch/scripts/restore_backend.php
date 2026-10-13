<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/Runtime.php';
$archive=realpath($argv[1]??'');$mode=$argv[2]??'--check-only';$temp=null;$lock=null;$flag=null;$exitCode=0;
try {
    if(!$archive||!is_file($archive)||!in_array($mode,['--check-only','--restore'],true))throw new RuntimeException('Usage: php restore_backend.php backup.tar --check-only|--restore');
    $private=realpath(dirname((string)getenv('COSMOS_DB_PATH')));
    if(!$private||$private===realpath(dirname(__DIR__))||str_starts_with($private,realpath(dirname(__DIR__)).'/'))throw new RuntimeException('Private database path required');
    $temp=$private.'/.restore-'.bin2hex(random_bytes(10));mkdir($temp,0700);
    $tar=new PharData($archive);$manifest=json_decode($tar['manifest.json']->getContent(),true,512,JSON_THROW_ON_ERROR);
    if(!isset($manifest['database'],$manifest['media'])||!is_array($manifest['media']))throw new RuntimeException('Invalid backup manifest');
    $allowed=['database.sqlite'=>true,'manifest.json'=>true];foreach($manifest['media']as $name=>$hash){if(!preg_match('/^[a-f0-9]{40}\.webp$/',$name)||!preg_match('/^[a-f0-9]{64}$/',$hash))throw new RuntimeException('Invalid photo manifest');$allowed['media/'.$name]=true;}
    foreach(new RecursiveIteratorIterator($tar)as $entry){$relative=substr($entry->getPathname(),strlen('phar://'.$archive.'/'));if(!isset($allowed[$relative])||!$entry->isFile()||$entry->isLink())throw new RuntimeException('Unexpected archive entry');}
    $tar->extractTo($temp,array_keys($allowed));
    if(!hash_equals($manifest['database'],hash_file('sha256',$temp.'/database.sqlite')))throw new RuntimeException('Database checksum mismatch');
    $db=new PDO('sqlite:'.$temp.'/database.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    if($db->query('PRAGMA integrity_check')->fetchColumn()!=='ok'||count($db->query('PRAGMA foreign_key_check')->fetchAll())!==0)throw new RuntimeException('Database integrity check failed');
    foreach(['bookings','settings','admin_users','cms_content','media','gallery_photos','audit_log','notification_outbox']as $table)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    $dbMedia=$db->query('SELECT filename FROM media ORDER BY filename')->fetchAll(PDO::FETCH_COLUMN);$manifestMedia=array_keys($manifest['media']);sort($manifestMedia);if($dbMedia!==$manifestMedia)throw new RuntimeException('Photo manifest and database disagree');
    foreach($manifest['media']as $name=>$hash)if(!hash_equals($hash,hash_file('sha256',$temp.'/media/'.$name)))throw new RuntimeException('Photo checksum mismatch');
    $db=null;
    if($mode==='--restore') {
        if(getenv('COSMOS_ADMIN_MAINTENANCE')!=='1')throw new RuntimeException('COSMOS_ADMIN_MAINTENANCE=1 is required to restore');
        $recovered=new PDO('sqlite:'.$temp.'/database.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $recovered->beginTransaction();
        $q=$recovered->prepare('UPDATE admin_users SET session_version=? WHERE id=?');
        foreach($recovered->query('SELECT id FROM admin_users')->fetchAll(PDO::FETCH_COLUMN)as $id)$q->execute([random_int(100000,PHP_INT_MAX-1000000),$id]);
        $q=$recovered->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');
        foreach(['booking_public_enabled'=>'0','mail_enabled'=>'0','mail_mode'=>'log']as $key=>$value)$q->execute([$key,$value]);
        $recovered->exec("INSERT INTO audit_log(actor,action) VALUES('system','backup_restored_booking_and_email_closed')");
        $recovered->commit();$recovered=null;
        $target=(string)getenv('COSMOS_DB_PATH');$owner=fileowner($target);$group=filegroup($target);$suffix='.before-restore-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
        $maintenancePath=$private.'/.maintenance';$maintenance=@fopen($maintenancePath,'x');
        if(!$maintenance)throw new RuntimeException('Maintenance is already active');
        $flag=$maintenancePath;fwrite($maintenance,'Restoring verified backup');fclose($maintenance);chmod($flag,0600);
        $lock=fopen($private.'/.runtime.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Cannot acquire maintenance lock');
        if(!is_dir($temp.'/media'))mkdir($temp.'/media',0700);
        chmod($temp.'/database.sqlite',0600);chown($temp.'/database.sqlite',$owner);chgrp($temp.'/database.sqlite',$group);
        chmod($temp.'/media',0700);chown($temp.'/media',$owner);chgrp($temp.'/media',$group);
        foreach(glob($temp.'/media/*.webp')as $photo){chmod($photo,0600);chown($photo,$owner);chgrp($photo,$group);}
        if(!rename($target,$target.$suffix))throw new RuntimeException('Cannot preserve current database');
        $oldMedia=$private.'/media'.$suffix;
        try {
            if(is_dir($private.'/media')&&!rename($private.'/media',$oldMedia))throw new RuntimeException('Cannot preserve current photos');
            if(!rename($temp.'/database.sqlite',$target)||!rename($temp.'/media',$private.'/media'))throw new RuntimeException('Cannot install recovered data');
        }catch(Throwable $error){if(is_file($target))unlink($target);rename($target.$suffix,$target);if(is_dir($oldMedia)&&!is_dir($private.'/media'))rename($oldMedia,$private.'/media');throw $error;}
        echo "PASS restored verified database and uploaded photos; previous data preserved.\n";
    }else echo "PASS backup checksums, database integrity, schema and all uploaded photos.\n";
}catch(Throwable $e){fwrite(STDERR,'Recovery failed: '.$e->getMessage()."\n");$exitCode=1;}
finally {
    if($flag&&is_file($flag))unlink($flag);if($lock){flock($lock,LOCK_UN);fclose($lock);}
    if($temp&&is_dir($temp)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST)as $file){$file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname());}rmdir($temp);}
}

exit($exitCode);

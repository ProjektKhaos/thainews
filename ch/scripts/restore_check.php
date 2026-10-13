<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$path=$argv[1]??'';
if ($path==='' || !is_file($path)) { fwrite(STDERR,"Använd: php restore_check.php /privat/backup.sqlite\n"); exit(2); }
try {
    $db=new PDO('sqlite:file:'.$path.'?mode=ro',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    if ($db->query('PRAGMA integrity_check')->fetchColumn()!=='ok') throw new RuntimeException('Integrity failed');
    $tables=['bookings','rate_rules','discount_rules','availability_blocks','audit_log','notification_outbox'];
    $counts=[];
    foreach ($tables as $table) $counts[$table]=(int)$db->query('SELECT count(*) FROM '.$table)->fetchColumn();
    echo 'RESTORE CHECK OK '.json_encode($counts,JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $error) { fwrite(STDERR,"Backup kan inte återställas: ".get_class($error)."\n"); exit(1); }

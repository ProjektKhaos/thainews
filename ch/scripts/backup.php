<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Booking.php';
$backupDir=getenv('COSMOS_BACKUP_DIR')?:'';
$real=$backupDir!==''?realpath($backupDir):false;
$project=realpath(dirname(__DIR__));
$root=realpath(getenv('COSMOS_DOCUMENT_ROOT')?:'')?:$project;
if ($real===false || !is_dir($real) || !is_writable($real) || $real===$project || str_starts_with($real,$project.'/') || $real===$root || str_starts_with($real,$root.'/')) {
    fwrite(STDERR,"Privat COSMOS_BACKUP_DIR saknas eller är felplacerad.\n"); exit(2);
}
try {
    $app=\Cosmos\Booking::connect();
    $name='cosmos-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sqlite';
    $target=$real.'/'.$name;
    $app->db->exec('VACUUM main INTO '.$app->db->quote($target));
    chmod($target,0600);
    $check=new PDO('sqlite:'.$target,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $integrity=$check->query('PRAGMA integrity_check')->fetchColumn();
    if ($integrity!=='ok') throw new RuntimeException('Backup integrity check failed');
    $counts=[];
    foreach (['bookings','rate_rules','discount_rules','availability_blocks','audit_log','notification_outbox'] as $table) $counts[$table]=(int)$check->query('SELECT count(*) FROM '.$table)->fetchColumn();
    $retention=getenv('COSMOS_BACKUP_RETENTION_DAYS')?:'';
    if ($retention!=='' && ctype_digit($retention) && (int)$retention>=1) {
        foreach (glob($real.'/cosmos-????????-??????-??????.sqlite')?:[] as $old) {
            if ($old!==$target && !is_link($old) && is_file($old) && filemtime($old)<time()-(int)$retention*86400) unlink($old);
        }
    }
    echo $name.' '.json_encode($counts,JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $error) { fwrite(STDERR,"Backup misslyckades: ".get_class($error)."\n"); exit(1); }

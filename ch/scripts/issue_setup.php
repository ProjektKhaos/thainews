<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/Booking.php';
try {
    $app=\Cosmos\Booking::connect();
    if((int)$app->db->query('SELECT COUNT(*) FROM admin_users WHERE active=1')->fetchColumn()>0)throw new RuntimeException('An account already exists; use the account recovery tool instead.');
    $token=bin2hex(random_bytes(32));$db=$app->db;$db->exec('BEGIN IMMEDIATE');
    $db->exec('DELETE FROM admin_setup WHERE used_at IS NULL');
    $q=$db->prepare("INSERT INTO admin_setup(token_hash,expires_at) VALUES(?,datetime('now','+48 hours'))");$q->execute([hash('sha256',$token)]);$db->exec('COMMIT');
    echo 'https://cosmoshouse.online/admin.php?setup='.$token."\n";
}catch(Throwable $e){fwrite(STDERR,'Cannot issue setup link: '.$e->getMessage()."\n");exit(1);}

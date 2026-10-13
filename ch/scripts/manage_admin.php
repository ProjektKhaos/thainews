<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Booking.php';
$action=$argv[1]??''; $username=$argv[2]??'';
if (!in_array($action,['list','deactivate','reset-password'],true) || ($action!=='list' && !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/',$username))) { fwrite(STDERR,"Använd: php manage_admin.php list|deactivate|reset-password [användarnamn]\n"); exit(2); }
try {
    $app=\Cosmos\Booking::connect();
    if ($action==='list') { foreach($app->db->query('SELECT username,active FROM admin_users ORDER BY username') as $row) echo $row['username'].' '.($row['active']?'active':'inactive')."\n"; exit; }
    if (getenv('COSMOS_ADMIN_MAINTENANCE')!=='1') throw new RuntimeException('Maintenance flag required');
    if ($action==='deactivate') {
        $app->db->exec('BEGIN IMMEDIATE');
        try {
            if ((int)$app->db->query('SELECT count(*) FROM admin_users WHERE active=1')->fetchColumn()<2) throw new RuntimeException('Cannot deactivate last admin');
            $stmt=$app->db->prepare('UPDATE admin_users SET active=0 WHERE username=? AND active=1');$stmt->execute([$username]);
            if ($stmt->rowCount()!==1) throw new RuntimeException('User not found');
            $app->db->exec('COMMIT');echo "Administratör inaktiverad.\n";
        } catch(Throwable $error){try{$app->db->exec('ROLLBACK');}catch(Throwable $ignored){}throw $error;}
        exit;
    }
    fwrite(STDOUT,"Nytt lösenord: ");$tty=stream_isatty(STDIN);if($tty)system('stty -echo');
    try{$password=trim((string)fgets(STDIN));}finally{if($tty){system('stty echo');fwrite(STDOUT,"\n");}}
    if(strlen($password)<16)throw new RuntimeException('Password too short');
    $stmt=$app->db->prepare('UPDATE admin_users SET password_hash=?,failed_count=0,locked_until=NULL,session_version=session_version+1 WHERE username=? AND active=1');
    $stmt->execute([password_hash($password,PASSWORD_DEFAULT),$username]);
    if($stmt->rowCount()!==1)throw new RuntimeException('User not found');
    echo "Lösenord uppdaterat.\n";
} catch(Throwable $error){fwrite(STDERR,"Adminåtgärd misslyckades: ".get_class($error)."\n");exit(1);}

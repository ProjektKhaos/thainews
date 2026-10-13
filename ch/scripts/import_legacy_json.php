<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Booking.php';
$path=getenv('COSMOS_LEGACY_BOOKINGS_PATH')?:'';
$apply=in_array('--apply',$argv,true);
if ($path==='' || $path[0]!=='/' || !is_file($path)) { fwrite(STDERR,"Ange privat COSMOS_LEGACY_BOOKINGS_PATH.\n"); exit(2); }
$real=realpath($path); $project=realpath(dirname(__DIR__)); $root=realpath(getenv('COSMOS_DOCUMENT_ROOT')?:'')?:$project;
if ($real===false || $real===$project || str_starts_with($real,$project.'/') || $real===$root || str_starts_with($real,$root.'/')) { fwrite(STDERR,"Legacyfilen måste vara privat.\n"); exit(2); }
if ($apply && getenv('COSMOS_IMPORT_APPROVED')!=='1') { fwrite(STDERR,"Import kräver separat COSMOS_IMPORT_APPROVED=1.\n"); exit(2); }
try {
    $records=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
    if (!is_array($records) || !array_is_list($records)) throw new RuntimeException('Invalid legacy JSON');
    $valid=[]; $seen=[];
    foreach ($records as $record) {
        if (!is_array($record) || !isset($record['id'],$record['checkin'],$record['checkout'],$record['guests'],$record['name'],$record['email'],$record['total'])) throw new RuntimeException('Incomplete legacy booking');
        $id=(string)$record['id']; $start=(string)$record['checkin']; $end=(string)$record['checkout'];
        $guests=(int)$record['guests']; $name=(string)$record['name']; $email=(string)$record['email'];
        $status=['pending'=>'inquiry','confirmed'=>'confirmed','rejected'=>'rejected'][$record['status']??'pending']??null;
        if ($id==='' || isset($seen[$id]) || !preg_match('/^\d{4}-\d\d-\d\d$/',$start) || !preg_match('/^\d{4}-\d\d-\d\d$/',$end) || $start>=$end || $guests<1 || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$status || !is_int($record['total']) || $record['total']<0) throw new RuntimeException('Invalid legacy booking');
        $seen[$id]=true; $valid[]=['reference'=>'LEGACY-'.strtoupper(substr(hash('sha256',$id),0,16)),'key'=>hash('sha256','legacy:'.$id),'start'=>$start,'end'=>$end,'guests'=>$guests,'name'=>$name,'email'=>$email,'status'=>$status,'total'=>$record['total']*100,'message'=>(string)($record['message']??'')];
    }
    echo count($valid).' legacy record(s) validated. '.($apply?'Applying':'Dry run only').".\n";
    if (!$apply) exit(0);
    $backupDir=getenv('COSMOS_BACKUP_DIR')?:'';
    if ($backupDir==='' || !is_dir($backupDir)) throw new RuntimeException('Backup directory required');
    $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/backup.php');
    exec($command,$output,$code); if ($code!==0) throw new RuntimeException('Backup failed');
    $app=\Cosmos\Booking::connect(); $app->db->exec('BEGIN IMMEDIATE');
    try {
        $stmt=$app->db->prepare("INSERT OR IGNORE INTO bookings(reference,idempotency_key,name,email,message,language,check_in,check_out,guests,status,total_satang,price_snapshot_json,internal_note) VALUES(?,?,?, ?,?,'sv',?,?,?,?,?,?,'Importerad äldre bokning; historiskt exempelpris')");
        $exists=$app->db->prepare('SELECT 1 FROM bookings WHERE reference=?');
        foreach($valid as $row) {
            $exists->execute([$row['reference']]); $already=$exists->fetchColumn(); $exists->closeCursor();
            if ($already) continue;
            if ($row['status']==='confirmed' && $app->conflicts($row['start'],$row['end'])) throw new RuntimeException('Conflicting confirmed legacy booking');
            $stmt->execute([$row['reference'],$row['key'],$row['name'],$row['email'],$row['message'],$row['start'],$row['end'],$row['guests'],$row['status'],$row['total'],json_encode(['legacyTotalSatang'=>$row['total']],JSON_THROW_ON_ERROR)]);
        }
        $app->db->exec('COMMIT');
    } catch(Throwable $error) { try{$app->db->exec('ROLLBACK');}catch(Throwable $ignored){} throw $error; }
    echo "Import committed. Backup: ".($output[0]??'created')."\n";
} catch(Throwable $error) { fwrite(STDERR,"Import stopped: ".get_class($error)."\n"); exit(1); }

<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Booking.php';
function check(bool $truth,string $message): void { if (!$truth) throw new RuntimeException($message); }
$path=sys_get_temp_dir().'/cosmos-test-'.bin2hex(random_bytes(8)).'.sqlite';
putenv('COSMOS_DB_PATH='.$path); putenv('COSMOS_DOCUMENT_ROOT='.dirname(__DIR__));
try {
    $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/migrate.php');
    exec($command,$out,$code); check($code===0,'migration failed');
    exec($command,$out,$code); check($code===0,'repeat migration failed');
    $app=\Cosmos\Booking::connect(); check(!$app->publicOpen(),'public gate default');
    $app->db->exec("INSERT INTO settings(key,value) VALUES('base_rate_satang','280000'),('approved_prices','1')");
    $app->updateSetting('max_guests','4','test');
    $app->db->exec("INSERT INTO discount_rules(label,family,min_nights,kind,value,priority,active) VALUES('seven','length',7,'percent',1000,10,1)");
    $start=(new DateTimeImmutable('today',new DateTimeZone('Asia/Bangkok')))->modify('+5 days')->format('Y-m-d');
    $end=(new DateTimeImmutable($start))->modify('+7 days')->format('Y-m-d');
    $later=(new DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d');
    $price=$app->price($start,$end,2); check($price['nightSubtotalSatang']===1960000,'night subtotal'); check($price['totalSatang']===1764000,'long stay discount');
    $far=(new DateTimeImmutable($start))->modify('+100 days');
    $app->db->exec("INSERT INTO discount_rules(label,family,min_nights,kind,value,priority,active) VALUES('twenty-eight','length',28,'percent',2000,20,1)");
    foreach([1,6,7,14,28,29] as $n) {
        $p=$app->price($far->format('Y-m-d'),$far->modify('+'.$n.' days')->format('Y-m-d'),2);
        $expected=280000*$n*($n>=28?80:($n>=7?90:100))/100;
        check($p['totalSatang']===$expected,'discount boundary '.$n);
    }
    $app->db->exec("INSERT INTO discount_rules(label,family,min_nights,max_nights,kind,value,priority,active) VALUES('zero-floor','promo',1,1,'fixed',9999999,30,1)");
    check($app->price($far->format('Y-m-d'),$far->modify('+1 day')->format('Y-m-d'),2)['totalSatang']===0,'fixed discount floor');
    $app->db->exec("UPDATE discount_rules SET active=0 WHERE label='zero-floor'");
    $app->db->exec("INSERT INTO discount_rules(label,family,min_nights,kind,value,priority,active) VALUES('early','early',1,'percent',500,15,1)");
    check(count($app->price($far->format('Y-m-d'),$far->modify('+7 days')->format('Y-m-d'),2)['discounts'])===1,'stacking disabled');
    $app->updateSetting('discount_stacking','1','test');
    check(count($app->price($far->format('Y-m-d'),$far->modify('+7 days')->format('Y-m-d'),2)['discounts'])===2,'approved stacking configuration');
    $app->updateSetting('discount_stacking','0','test');
    $app->db->exec("UPDATE discount_rules SET active=0 WHERE label='early'");
    for($i=0;$i<3;$i++) { $d=$far->modify('+'.$i.' days')->format('Y-m-d'); $next=$far->modify('+'.($i+1).' days')->format('Y-m-d'); $q=$app->db->prepare('INSERT INTO rate_rules(label,start_date,end_date,amount_satang,priority,active) VALUES(?,?,?,?,50,1)'); $q->execute(['day'.$i,$d,$next,($i+1)*10000]); }
    check($app->price($far->format('Y-m-d'),$far->modify('+3 days')->format('Y-m-d'),2)['nightSubtotalSatang']===60000,'three daily rates');
    $app->db->exec('UPDATE rate_rules SET active=0');
    $app->db->exec("INSERT INTO fee_rules(label,kind,amount_satang,active) VALUES('cleaning','fixed',60000,1)");
    $app->db->exec("INSERT INTO fee_rules(label,kind,percent_bps,basis,active) VALUES('tax','percent',1000,'subtotal_plus_fees',1)");
    check($app->price($far->format('Y-m-d'),$far->modify('+1 day')->format('Y-m-d'),2)['totalSatang']===374000,'fixed fee plus configured percent base');
    $app->db->exec('UPDATE fee_rules SET active=0');
    $quote=$app->quote($start,$end,2); check(strlen($quote['quoteToken'])===48,'quote token');
    $app->block($end,$later,'test','test'); check(!$app->conflicts($start,$end),'adjacent block'); check($app->conflicts($start,$later),'overlap');
    $app->db->exec('UPDATE availability_blocks SET active=0');
    $manualStart=(new DateTimeImmutable($later))->modify('+2 days')->format('Y-m-d');
    $manualEnd=(new DateTimeImmutable($manualStart))->modify('+1 day')->format('Y-m-d');
    $manualRef=$app->createManual($manualStart,$manualEnd,1,'Internal Test','internal@example.invalid','test');
    check(str_starts_with($manualRef,'TEST-'),'manual booking');
    foreach (['house','capacity','stay','prices','discounts','fees','checkin','rules','contact','privacy','mail','backup','release'] as $approval) {
        $q=$app->db->prepare('INSERT OR REPLACE INTO settings(key,value) VALUES(?,?)'); $q->execute(['approved_'.$approval,'1']);
    }
    foreach (['en','sv','th'] as $lang) { $q=$app->db->prepare('INSERT INTO settings(key,value) VALUES(?,?)'); $q->execute(['privacy_text_'.$lang,'Test policy']); }
    $app->db->exec("INSERT INTO settings(key,value) VALUES('policy_version','test-1')");
    $app->db->exec("INSERT OR REPLACE INTO settings(key,value) VALUES('booking_public_enabled','1')");
    putenv('COSMOS_BOOKING_PUBLIC_ENABLED=1'); putenv('COSMOS_MAIL_MODE=smtp'); putenv('COSMOS_MAIL_APPROVED=1');
    putenv('COSMOS_SMTP_HOST=mail.example.invalid'); putenv('COSMOS_SMTP_USER=test'); putenv('COSMOS_SMTP_PASSWORD=fixture-only');
    putenv('COSMOS_ADMIN_EMAIL=admin@example.invalid'); putenv('COSMOS_MAIL_FROM=from@example.invalid');
    check($app->publicOpen(),'gate enabled in fixture');
    $data=['quoteToken'=>$quote['quoteToken'],'idempotencyKey'=>bin2hex(random_bytes(16)),'name'=>'Test Guest','email'=>'test@example.invalid','language'=>'en','privacy'=>true,'totalSatang'=>1];
    $firstData=$data;
    $a=$app->inquire($data); $again=$app->inquire($data); check($a['reference']===$again['reference'],'idempotency');
    $changedRetry=$data;$changedRetry['email']='other@example.invalid';
    try { $app->inquire($changedRetry); throw new RuntimeException('changed idempotent request accepted'); }
    catch(\Cosmos\BookingError $error) { check($error->httpStatus===409,'changed idempotent request conflict'); }
    $savedTotal=(int)$app->db->query("SELECT total_satang FROM bookings WHERE reference='".$a['reference']."'")->fetchColumn();
    check($savedTotal===1764000,'client total ignored');
    $quote2=$app->quote($start,$end,2);
    $data['quoteToken']=$quote2['quoteToken']; $data['idempotencyKey']=bin2hex(random_bytes(16));
    $b=$app->inquire($data); check($a['reference']!==$b['reference'],'independent inquiries');
    $invalid=$data;$invalid['idempotencyKey']=bin2hex(random_bytes(16));$invalid['email']='invalid';
    try{$app->inquire($invalid);throw new RuntimeException('invalid email accepted');}catch(\Cosmos\BookingError $error){check($error->httpStatus===422,'invalid email');}
    $invalid=$data;$invalid['idempotencyKey']=bin2hex(random_bytes(16));$invalid['privacy']=false;
    try{$app->inquire($invalid);throw new RuntimeException('missing privacy accepted');}catch(\Cosmos\BookingError $error){check($error->httpStatus===422,'privacy required');}
    $expired=$app->quote($far->format('Y-m-d'),$far->modify('+2 days')->format('Y-m-d'),2);
    $stmt=$app->db->prepare("UPDATE quotes SET expires_at='2000-01-01 00:00:00' WHERE token=?"); $stmt->execute([$expired['quoteToken']]);
    try { $app->inquire(['quoteToken'=>$expired['quoteToken'],'idempotencyKey'=>bin2hex(random_bytes(16)),'name'=>'Test Guest','email'=>'test@example.invalid','language'=>'en','privacy'=>true]); throw new RuntimeException('expired quote accepted'); }
    catch(\Cosmos\BookingError $error) { check($error->httpStatus===410,'expired quote status'); }
    $changed=$app->quote($far->format('Y-m-d'),$far->modify('+2 days')->format('Y-m-d'),2);
    $app->db->exec("UPDATE settings SET value=CAST(value AS INTEGER)+1 WHERE key='rule_version'");
    try { $app->inquire(['quoteToken'=>$changed['quoteToken'],'idempotencyKey'=>bin2hex(random_bytes(16)),'name'=>'Test Guest','email'=>'test@example.invalid','language'=>'en','privacy'=>true]); throw new RuntimeException('changed quote accepted'); }
    catch(\Cosmos\BookingError $error) { check($error->httpStatus===409,'changed quote status'); }
    try { $app->quote($start,$end,0); throw new RuntimeException('invalid guest count accepted'); }
    catch(\Cosmos\BookingError $error) { check($error->httpStatus===422,'guest validation'); }
    $ids=$app->db->query("SELECT id FROM bookings WHERE reference NOT LIKE 'TEST-%' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    if (function_exists('pcntl_fork')) {
        $children=[];
        foreach ($ids as $id) {
            $pid=pcntl_fork();
            if ($pid===0) {
                try { $child=\Cosmos\Booking::connect(); $child->transition((int)$id,'confirmed','test'); exit(0); }
                catch (\Cosmos\BookingError $e) { exit($e->httpStatus===409?2:3); }
            }
            check($pid>0,'fork'); $children[]=$pid;
        }
        $codes=[]; foreach ($children as $pid) { pcntl_waitpid($pid,$status); $codes[]=pcntl_wexitstatus($status); }
        sort($codes); check($codes===[0,2],'parallel confirm conflict');
    } else {
        $app->transition((int)$ids[0],'confirmed','test');
        try { $app->transition((int)$ids[1],'confirmed','test'); throw new RuntimeException('double booking'); }
        catch (\Cosmos\BookingError $e) { check($e->httpStatus===409,'conflict response'); }
    }
    $confirmed=(int)$app->db->query("SELECT count(*) FROM bookings WHERE status='confirmed'")->fetchColumn(); check($confirmed===1,'one confirmed');
    $stmt=$app->db->prepare('SELECT status FROM bookings WHERE reference=?');$stmt->execute([$a['reference']]);
    check($app->inquire($firstData)['status']===$stmt->fetchColumn(),'idempotent retry returns current status');
    $loserId=(int)$app->db->query("SELECT id FROM bookings WHERE status='inquiry' AND reference NOT LIKE 'TEST-%' LIMIT 1")->fetchColumn();
    $app->transition($loserId,'rejected','test');
    check($app->db->query('SELECT status FROM bookings WHERE id='.$loserId)->fetchColumn()==='rejected','rejection transition');
    $confirmedId=(int)$app->db->query("SELECT id FROM bookings WHERE status='confirmed'")->fetchColumn();
    $prior=(new DateTimeImmutable($start))->modify('-1 day')->format('Y-m-d');
    $adjacentRef=$app->createManual($prior,$start,1,'Adjacent Test','adjacent@example.invalid','test');
    $stmt=$app->db->prepare('SELECT id FROM bookings WHERE reference=?');$stmt->execute([$adjacentRef]);$adjacentId=(int)$stmt->fetchColumn();$stmt->closeCursor();
    $app->transition($adjacentId,'confirmed','test');
    $stmt=$app->db->prepare("SELECT count(*) FROM notification_outbox WHERE event_key LIKE ?");$stmt->execute(['booking:'.$adjacentId.':%']);
    check((int)$stmt->fetchColumn()===0,'internal test confirmation has no guest mail');
    check((int)$app->db->query("SELECT count(*) FROM bookings WHERE status='confirmed'")->fetchColumn()===2,'adjacent confirmations');
    $app->reschedule($confirmedId,$end,$later,2,'test');
    $app->reschedule($adjacentId,$prior,$start,1,'test');
    $stmt=$app->db->prepare("SELECT count(*) FROM notification_outbox WHERE event_key LIKE ?");$stmt->execute(['booking:'.$adjacentId.':%']);
    check((int)$stmt->fetchColumn()===0,'internal test reschedule has no guest mail');
    check(!$app->conflicts($start,$end),'old dates released'); check($app->conflicts($end,$later),'new dates blocked');
    check((int)$app->db->query('SELECT count(*) FROM booking_revisions')->fetchColumn()===2,'revisions saved');
    $app->paymentStatus($confirmedId,'paid_manual','test');
    $app->transition($confirmedId,'cancelled','test');
    $app->anonymize($confirmedId,'test');
    $q=$app->db->prepare('SELECT email,phone,payment_status FROM bookings WHERE id=?');$q->execute([$confirmedId]);$row=$q->fetch();$q->closeCursor();
    check($row['email']==='anonymous@example.invalid' && $row['payment_status']==='paid_manual','anonymization and manual payment');
    $holdStart=(new DateTimeImmutable($far->format('Y-m-d')))->modify('+40 days')->format('Y-m-d');
    $holdEnd=(new DateTimeImmutable($holdStart))->modify('+1 day')->format('Y-m-d');
    $holdRef=$app->createManual($holdStart,$holdEnd,1,'Hold Test','hold@example.invalid','test');
    $stmt=$app->db->prepare('SELECT id FROM bookings WHERE reference=?');$stmt->execute([$holdRef]);$holdId=(int)$stmt->fetchColumn();$stmt->closeCursor();
    $app->transition($holdId,'hold','test',gmdate('Y-m-d H:i:s',time()+3600)); check($app->conflicts($holdStart,$holdEnd),'active hold blocks');
    $app->db->exec("UPDATE bookings SET expires_at='2000-01-01 00:00:00' WHERE id=".$holdId);
    check(!$app->conflicts($holdStart,$holdEnd),'expired hold releases');
    try { $app->transition($holdId,'confirmed','test'); throw new RuntimeException('expired hold confirmed'); }
    catch(\Cosmos\BookingError $error) { check($error->httpStatus===409,'expired hold cannot confirm'); }
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/expire_holds.php'),$expireOutput,$expireCode);
    check($expireCode===0,'hold expiry worker');
    $app->db->exec("CREATE TRIGGER fail_status_audit BEFORE INSERT ON audit_log WHEN NEW.action='status_changed' BEGIN SELECT RAISE(ABORT,'injected'); END");
    $manualId=(int)$app->db->query("SELECT id FROM bookings WHERE reference='".$manualRef."'")->fetchColumn();
    try { $app->transition($manualId,'confirmed','test'); throw new RuntimeException('injected audit failure did not roll back'); }
    catch(PDOException $error) { check(true,'injected rollback'); }
    $app->db->exec('DROP TRIGGER fail_status_audit');
    check($app->db->query('SELECT status FROM bookings WHERE id='.$manualId)->fetchColumn()==='inquiry','status rolled back');
    if (function_exists('pcntl_fork')) {
        $raceStart=(new DateTimeImmutable($far->format('Y-m-d')))->modify('+70 days')->format('Y-m-d');
        $raceEnd=(new DateTimeImmutable($raceStart))->modify('+1 day')->format('Y-m-d');
        $raceRef=$app->createManual($raceStart,$raceEnd,1,'Race Test','race@example.invalid','test');
        $stmt=$app->db->prepare('SELECT id FROM bookings WHERE reference=?');$stmt->execute([$raceRef]);$raceId=(int)$stmt->fetchColumn();$stmt->closeCursor();
        $workers=[];
        foreach(['confirm','block'] as $work) {
            $pid=pcntl_fork();
            if ($pid===0) {
                try { $child=\Cosmos\Booking::connect(); if($work==='confirm')$child->transition($raceId,'confirmed','test'); else $child->block($raceStart,$raceEnd,'race','test'); exit(0); }
                catch(\Cosmos\BookingError $error) { exit($error->httpStatus===409?2:3); }
            }
            check($pid>0,'race fork'); $workers[]=$pid;
        }
        $results=[];foreach($workers as $pid){pcntl_waitpid($pid,$status);$results[]=pcntl_wexitstatus($status);}sort($results);
        check($results===[0,2],'parallel block-confirm conflict');
    }
    check((int)$app->db->query('SELECT count(*) FROM notification_outbox')->fetchColumn()>=3,'outbox events');
    putenv('COSMOS_MAIL_MODE=log'); putenv('COSMOS_ADMIN_EMAIL=admin@example.invalid');
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/mail_worker.php'),$mailOutput,$mailCode);
    check($mailCode===0,'mail worker');
    check((int)$app->db->query("SELECT count(*) FROM notification_outbox WHERE status='simulated'")->fetchColumn()>=3,'simulated mail');
    putenv('COSMOS_MAIL_MODE=smtp'); putenv('COSMOS_SMTP_HOST=127.0.0.1'); putenv('COSMOS_SMTP_PORT=1');
    $failureStart=(new DateTimeImmutable($far->format('Y-m-d')))->modify('+55 days')->format('Y-m-d');
    $failureEnd=(new DateTimeImmutable($failureStart))->modify('+1 day')->format('Y-m-d');
    $failureQuote=$app->quote($failureStart,$failureEnd,1);
    $failureData=['quoteToken'=>$failureQuote['quoteToken'],'idempotencyKey'=>bin2hex(random_bytes(16)),'name'=>'Mail Test','email'=>'mail@example.invalid','language'=>'en','privacy'=>true];
    $failureBooking=$app->inquire($failureData);
    $stmt=$app->db->prepare("INSERT INTO notification_outbox(event_key,kind,language,payload_json) VALUES(?,?,?,?)");
    $stmt->execute(['legacy-internal-test','status_confirmed','en',json_encode(['reference'=>'TEST-OLD','email'=>'internal@example.invalid','name'=>'Internal Test'],JSON_THROW_ON_ERROR)]);
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/mail_worker.php'),$failedOutput,$failedCode);
    check($failedCode===0,'worker handles SMTP failure');
    check($app->db->query("SELECT status FROM notification_outbox WHERE event_key='legacy-internal-test'")->fetchColumn()==='simulated','old internal test mail never sent');
    check((int)$app->db->query("SELECT count(*) FROM notification_outbox WHERE status='failed'")->fetchColumn()===2,'two failed notifications');
    $stmt=$app->db->prepare('SELECT status FROM bookings WHERE reference=?');$stmt->execute([$failureBooking['reference']]);$failureStatus=$stmt->fetchColumn();$stmt->closeCursor();
    check($failureStatus==='inquiry','SMTP failure retains booking');
    $app->db->exec("UPDATE notification_outbox SET next_attempt_at=CURRENT_TIMESTAMP WHERE status='failed'");
    putenv('COSMOS_MAIL_MODE=log');
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/mail_worker.php'),$retryOutput,$retryCode);
    check($retryCode===0 && (int)$app->db->query("SELECT count(*) FROM notification_outbox WHERE status='failed'")->fetchColumn()===0,'retry no duplicate');
    putenv('COSMOS_BACKUP_DIR='.sys_get_temp_dir());
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/backup.php'),$backupOutput,$backupCode);
    check($backupCode===0,'backup');
    $backupPath=sys_get_temp_dir().'/'.explode(' ',$backupOutput[0])[0];
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/restore_check.php').' '.escapeshellarg($backupPath),$restoreOutput,$restoreCode);
    check($restoreCode===0 && str_starts_with($restoreOutput[0]??'','RESTORE CHECK OK'),'restore check');
    $restorePath=$path.'.restored'; check(copy($backupPath,$restorePath),'restore copy');
    $restored=new PDO('sqlite:'.$restorePath);
    check((int)$restored->query('SELECT count(*) FROM bookings')->fetchColumn()===(int)$app->db->query('SELECT count(*) FROM bookings')->fetchColumn(),'restored booking count');
    check((int)$restored->query('SELECT count(*) FROM audit_log')->fetchColumn()===(int)$app->db->query('SELECT count(*) FROM audit_log')->fetchColumn(),'restored audit count');
    $restored=null; unlink($restorePath);
    unlink($backupPath);
    echo "PASS migration, gate, price, quote, interval, idempotency, parallel confirmation, outbox, backup/restore check\n";
} finally { if (is_file($path)) unlink($path); }

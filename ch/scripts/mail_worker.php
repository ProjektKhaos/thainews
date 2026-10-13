<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Booking.php';
require dirname(__DIR__).'/app/Mail.php';

function message_for(array $event,array $config): array {
    $lang=in_array($event['language'],['en','sv','th'],true)?$event['language']:'en';
    $p=json_decode($event['payload_json'],true,512,JSON_THROW_ON_ERROR);
    $reference=(string)($p['reference']??'');
    $kind=$event['kind'];
    $texts=[
        'en'=>['inquiry_guest'=>['Enquiry received','We received your enquiry %s. It is not confirmed and no payment has been taken.'],'inquiry_admin'=>['New enquiry','A new enquiry %s needs review in Cosmos House admin.'],'status_confirmed'=>['Booking confirmed','Your booking %s has been confirmed. Please refer to the agreed terms.'],'status_rejected'=>['Enquiry declined','Your enquiry %s was declined.'],'status_cancelled'=>['Booking cancelled','Booking %s has been cancelled.'],'status_rescheduled'=>['Stay dates changed','The dates of booking %s have changed. Please review the agreed details.']],
        'sv'=>['inquiry_guest'=>['Förfrågan mottagen','Vi har mottagit din förfrågan %s. Den är inte bekräftad och ingen betalning har skett.'],'inquiry_admin'=>['Ny förfrågan','En ny förfrågan %s behöver granskas i Cosmos House admin.'],'status_confirmed'=>['Bokning bekräftad','Din bokning %s har bekräftats. Se överenskomna villkor.'],'status_rejected'=>['Förfrågan avböjd','Din förfrågan %s har avböjts.'],'status_cancelled'=>['Bokning avbokad','Bokning %s har avbokats.'],'status_rescheduled'=>['Vistelsedatum ändrade','Datumen för bokning %s har ändrats. Kontrollera de överenskomna uppgifterna.']],
        'th'=>['inquiry_guest'=>['ได้รับคำขอแล้ว','เราได้รับคำขอ %s แล้ว ยังไม่ได้ยืนยันการจองและไม่มีการชำระเงิน'],'inquiry_admin'=>['คำขอใหม่','คำขอใหม่ %s รอการตรวจสอบในระบบผู้ดูแล'],'status_confirmed'=>['ยืนยันการจองแล้ว','การจอง %s ได้รับการยืนยันแล้ว โปรดดูเงื่อนไขที่ตกลงกัน'],'status_rejected'=>['คำขอถูกปฏิเสธ','คำขอ %s ถูกปฏิเสธ'],'status_cancelled'=>['ยกเลิกการจอง','การจอง %s ถูกยกเลิก'],'status_rescheduled'=>['เปลี่ยนวันเข้าพัก','วันเข้าพักของการจอง %s เปลี่ยนแล้ว โปรดตรวจสอบรายละเอียดที่ตกลงกัน']]
    ];
    if ($kind==='smtp_test') return [(string)($p['email']??''),'Cosmos House email test',"SMTP delivery from Cosmos House administration is working.\n"];
    if (!isset($texts[$lang][$kind])) return ['', '', ''];
    [$subject,$template]=$texts[$lang][$kind];
    $to=$kind==='inquiry_admin'?$config['admin']:(string)($p['email']??'');
    return [$to,$subject,"Cosmos House\n\n".sprintf($template,$reference)."\n"];
}


try {
    $app=\Cosmos\Booking::connect(); $config=$app->mailConfig(); $mode=$config['mode'];
    if (!in_array($mode,['log','sandbox','smtp'],true)) throw new RuntimeException('Unknown mail mode');
    if ($mode==='smtp' && !$config['approved']) throw new RuntimeException('SMTP is not approved for public operation');
    $processed=0;
    while ($processed<25) {
        $app->db->exec('BEGIN IMMEDIATE');
        try {
            $event=$app->db->query("SELECT * FROM notification_outbox WHERE status IN ('pending','failed') AND attempts<8 AND next_attempt_at<=CURRENT_TIMESTAMP ORDER BY id LIMIT 1")->fetch();
            if (!$event) { $app->db->exec('COMMIT'); break; }
            $stmt=$app->db->prepare("UPDATE notification_outbox SET attempts=attempts+1,next_attempt_at=datetime('now','+5 minutes') WHERE id=?"); $stmt->execute([$event['id']]);
            $app->db->exec('COMMIT');
        } catch (Throwable $error) { try { $app->db->exec('ROLLBACK'); } catch (Throwable $ignored) {} throw $error; }
        try {
            [$to,$subject,$body]=message_for($event,$config);
            if ($to==='' || $subject==='') throw new RuntimeException('No mail recipient or template');
            $payload=json_decode($event['payload_json'],true,512,JSON_THROW_ON_ERROR);
            // Äldre köposter för interna testärenden får inte skickas efter en senare SMTP-aktivering.
            $internalTest=str_starts_with((string)($payload['reference']??''),'TEST-');
            if ($mode==='smtp' && !$internalTest) \Cosmos\Mail::send($config,$to,$subject,$body);
            $status=$mode==='smtp' && !$internalTest?'sent':'simulated';
            $stmt=$app->db->prepare("UPDATE notification_outbox SET status=?,sent_at=CASE WHEN ?='sent' THEN CURRENT_TIMESTAMP ELSE NULL END,last_error=NULL WHERE id=?");
            $stmt->execute([$status,$status,$event['id']]);
        } catch (Throwable $error) {
            $backoff=min(3600,60*(2**min(6,(int)$event['attempts'])));
            $stmt=$app->db->prepare("UPDATE notification_outbox SET status='failed',next_attempt_at=datetime('now', ?),last_error=? WHERE id=?");
            $stmt->execute(['+'.$backoff.' seconds',get_class($error),$event['id']]);
        }
        $processed++;
    }
    $health=$app->db->prepare("INSERT INTO settings(key,value) VALUES('last_mail_worker_at',?) ON CONFLICT(key) DO UPDATE SET value=excluded.value");$health->execute([gmdate('Y-m-d H:i:s')]);
    echo "Processed ".$processed." notification event(s) in ".$mode." mode.\n";
} catch (Throwable $error) { fwrite(STDERR,"Mail worker stopped: ".get_class($error)."\n"); exit(1); }

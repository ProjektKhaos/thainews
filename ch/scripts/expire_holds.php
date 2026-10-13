<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Booking.php';
try {
    $app=\Cosmos\Booking::connect(); $app->db->exec('BEGIN IMMEDIATE');
    try {
        $ids=$app->db->query("SELECT id FROM bookings WHERE status='hold' AND expires_at<=CURRENT_TIMESTAMP")->fetchAll(PDO::FETCH_COLUMN);
        foreach($ids as $id) {
            $stmt=$app->db->prepare("UPDATE bookings SET status='expired',updated_at=CURRENT_TIMESTAMP WHERE id=?"); $stmt->execute([$id]);
            $stmt=$app->db->prepare("INSERT INTO audit_log(booking_id,actor,action,status_from,status_to) VALUES(?,'system','hold_expired','hold','expired')"); $stmt->execute([$id]);
        }
        $health=$app->db->prepare("INSERT INTO settings(key,value) VALUES('last_hold_worker_at',?) ON CONFLICT(key) DO UPDATE SET value=excluded.value");$health->execute([gmdate('Y-m-d H:i:s')]);
        $app->db->exec('COMMIT'); echo 'Expired '.count($ids)." hold(s).\n";
    } catch(Throwable $error) { try{$app->db->exec('ROLLBACK');}catch(Throwable $ignored){} throw $error; }
} catch(Throwable $error) { fwrite(STDERR,"Hold expiry failed: ".get_class($error)."\n"); exit(1); }

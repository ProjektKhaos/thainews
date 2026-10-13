<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Booking.php';
$username = $argv[1] ?? '';
if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) { fwrite(STDERR,"Använd: php create_admin.php användarnamn\n"); exit(2); }
fwrite(STDOUT,"Nytt lösenord: ");
$tty=stream_isatty(STDIN);
if ($tty) system('stty -echo');
try { $password = trim((string)fgets(STDIN)); }
finally { if ($tty) { system('stty echo'); fwrite(STDOUT,"\n"); } }
if (strlen($password) < 16) { fwrite(STDERR,"Minst 16 tecken krävs.\n"); exit(2); }
try {
    $booking = \Cosmos\Booking::connect();
    $stmt = $booking->db->prepare('INSERT INTO admin_users(username,password_hash) VALUES(?,?)');
    $stmt->execute([$username,password_hash($password,PASSWORD_DEFAULT)]);
    echo "Administratör skapad.\n";
} catch (Throwable $error) { fwrite(STDERR,"Kunde inte skapa administratör.\n"); exit(1); }

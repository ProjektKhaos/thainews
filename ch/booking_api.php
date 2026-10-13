<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
require __DIR__ . '/access.php';
cosmos_require_access(1, true);
require __DIR__ . '/app/Booking.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
function booking_reply(array $data, int $status=200): never { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function booking_fail(string $message, int $status): never { booking_reply(['error'=>$message],$status); }
try {
    $service = \Cosmos\Booking::connect();
    $action = (string)($_GET['action'] ?? 'status');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($action === 'status' && $method === 'GET') booking_reply(['bookingOpen'=>$service->publicOpen()]);
    if (!$service->publicOpen()) booking_fail('Bokningsförfrågningar är inte öppna ännu.',503);
    if ($action === 'availability' && $method === 'GET') {
        $from = (string)($_GET['from'] ?? ''); $to = (string)($_GET['to'] ?? '');
        booking_reply($service->availability($from,$to));
    }
    if (!in_array($action,['quote','inquire'],true) || $method !== 'POST') booking_fail('Metoden stöds inte.',405);
    if (!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json')) booking_fail('Content-Type måste vara application/json.',415);
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 12000) booking_fail('För stort innehåll.',413);
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '') {
        $host = parse_url($origin, PHP_URL_HOST);
        $port = parse_url($origin, PHP_URL_PORT);
        $target = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $source = strtolower((string)$host).($port===null?'':':'.$port);
        if (!is_string($host) || !hash_equals($target,$source)) booking_fail('Otillåtet ursprung.',403);
    }
    $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['cosmos_booking_csrf']) || !hash_equals($_SESSION['cosmos_booking_csrf'],$csrf)) booking_fail('Säkerhetstoken saknas.',403);
    $raw = file_get_contents('php://input', false, null, 0, 12001);
    if ($raw === false || strlen($raw)>12000) booking_fail('För stort innehåll.',413);
    $data = json_decode($raw,true);
    if (!is_array($data)) booking_fail('Ogiltig JSON.',400);
    // Begränsning per anonym session. Ingen IP-adress eller PII lagras i öppna loggar.
    $now=time(); $bucket=&$_SESSION['cosmos_booking_rate'];
    if (!is_array($bucket ?? null) || ($bucket['start']??0)<$now-600) $bucket=['start'=>$now,'count'=>0];
    if (++$bucket['count']>30) booking_fail('För många försök. Försök senare.',429);
    if ($action === 'quote') {
        $result=$service->quote((string)($data['checkIn']??''),(string)($data['checkOut']??''),(int)($data['guests']??0),isset($data['code'])?(string)$data['code']:null);
        booking_reply($result,201);
    }
    if (!empty($data['website'])) booking_reply(['ok'=>true,'message'=>'Förfrågan mottagen.'],200);
    $result=$service->inquire($data);
    booking_reply(['ok'=>true]+$result,201);
} catch (\Cosmos\BookingError $error) {
    booking_fail($error->getMessage(),$error->httpStatus);
} catch (Throwable $error) {
    error_log('Cosmos booking request failed: '.get_class($error));
    booking_fail('Bokningssystemet är tillfälligt otillgängligt.',503);
}

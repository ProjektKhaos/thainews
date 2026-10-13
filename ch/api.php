<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
require __DIR__.'/access.php';
cosmos_require_access(1,true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
// Äldre JSON-filbaserade bokningar är permanent spärrade. Nytt API finns i booking_api.php.
http_response_code(($_SERVER['REQUEST_METHOD']??'GET')==='GET'?200:503);
echo json_encode(($_SERVER['REQUEST_METHOD']??'GET')==='GET'
    ? ['bookingOpen'=>false,'blocked'=>[],'replacement'=>'booking_api.php']
    : ['error'=>'Det äldre boknings-API:t är stängt.'],JSON_UNESCAPED_UNICODE);

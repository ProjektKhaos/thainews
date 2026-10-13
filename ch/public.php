<?php
declare(strict_types=1);
require __DIR__.'/app/Cms.php';
$draft=false;
if (isset($_GET['preview'])) {
    require __DIR__.'/access.php';cosmos_access_session(1);require __DIR__.'/app/Auth.php';
    try {$draft=(new \Cosmos\Auth(\Cosmos\Booking::connect()))->current()!==null;}catch(Throwable $e){}
    if(!$draft){http_response_code(403);exit('Sign in to preview unpublished changes.');}
    header('X-Robots-Tag: noindex, nofollow');
}
header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo \Cosmos\Cms::apply((string)file_get_contents(__DIR__.'/index.html'),'landing',$draft);

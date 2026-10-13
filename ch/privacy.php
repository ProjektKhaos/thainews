<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
require __DIR__.'/access.php';
cosmos_require_access(1);
require __DIR__.'/config.php';
require __DIR__.'/app/Booking.php';
header('Cache-Control: private, no-store');
$language=(string)($_GET['lang']??'en');
if (!in_array($language,['en','sv','th'],true)) $language='en';
$title=['en'=>'Privacy information','sv'=>'Integritetsinformation','th'=>'ข้อมูลความเป็นส่วนตัว'][$language];
$message=['en'=>'The privacy information has not been approved yet. Booking enquiries are closed.','sv'=>'Integritetsinformationen är ännu inte godkänd. Bokningsförfrågningar är stängda.','th'=>'ข้อมูลความเป็นส่วนตัวยังไม่ได้รับการอนุมัติ ขณะนี้ยังไม่เปิดรับคำขอจอง'][$language];
try { $app=\Cosmos\Booking::connect(); $body=$app->setting('approved_privacy')==='1'?$app->setting('privacy_text_'.$language):null; $version=$app->setting('policy_version'); }
catch (Throwable $error) { $body=null; $version=null; }
function pe(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
?><!doctype html><html lang="<?=pe($language)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=pe($title)?> | Cosmos House</title><link rel="stylesheet" href="<?=pe(asset_url('style.css'))?>"></head><body><main class="container section"><a href="<?=pe(url($language.'/book/'))?>">← Cosmos House</a><h1><?=pe($title)?></h1><?php if($body):?><p><?=nl2br(pe($body))?></p><small><?=pe($version)?></small><?php else:?><p><?=pe($message)?></p><?php endif;?></main></body></html>

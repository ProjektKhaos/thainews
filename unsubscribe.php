<?php
// Senast uppdaterad: 2026-09-20 18:35 | Scanner-safe unsubscribe flow

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow'); header('Referrer-Policy: no-referrer');
use ThaiNews\Metadata; use ThaiNews\Security\Csrf; use ThaiNews\Security\Tokens; use ThaiNews\Subscription\SubscriberRepository;
$repo=new SubscriberRepository(db()); $sid=(int)($_REQUEST['sid']??0); $v=(int)($_REQUEST['v']??0); $sig=(string)($_REQUEST['sig']??''); $valid=$sid>0&&$sig!==''&&Tokens::verifyUnsubscribe($sid,$v,$sig,(string)config()->get('app_secret')); $done=false;
if($_SERVER['REQUEST_METHOD']==='POST'&&$valid&&Csrf::validate($_POST['csrf']??null)){$done=$repo->unsubscribe($sid,$v); if(!$done)$valid=false;}
$meta=Metadata::build(config(),t('unsubscribe.title').' — Thai News',t('unsubscribe.text'),'unsubscribe.php'); require __DIR__.'/includes/views/header.php';
?><section class="simple-card"><h1><?= e(t('unsubscribe.title')) ?></h1><?php if($done):?><p><?= e(t('unsubscribe.done')) ?></p><?php elseif(!$valid):?><p><?= e(t('unsubscribe.invalid')) ?></p><?php else:?><p><?= e(t('unsubscribe.text')) ?></p><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="sid" value="<?= $sid ?>"><input type="hidden" name="v" value="<?= $v ?>"><input type="hidden" name="sig" value="<?= e($sig) ?>"><button class="primary"><?= e(t('unsubscribe.button')) ?></button></form><?php endif;?></section><?php require __DIR__.'/includes/views/footer.php';

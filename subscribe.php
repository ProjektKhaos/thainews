<?php
// Senast uppdaterad: 2026-09-20 18:35 | Double opt-in subscription endpoint

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');

use ThaiNews\Digest\Mailer; use ThaiNews\Metadata; use ThaiNews\Security\Csrf; use ThaiNews\Security\RateLimiter; use ThaiNews\Subscription\SubscriberRepository; use ThaiNews\Subscription\SubscriptionService;
$repo=new SubscriberRepository(db()); $limiter=new RateLimiter(db(),(string)config()->get('rate_limit_secret')); $mailer=new Mailer(config()); $service=new SubscriptionService($repo,$limiter,$mailer,config());
$state=(bool)config()->get('smtp.enabled',false)?'form':'unavailable';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['confirm_sid'])){
    if(!Csrf::validate($_POST['csrf']??null)){$state='invalid';} else {
        $sid=(int)($_POST['confirm_sid']??0); $token=(string)($_POST['confirm_token']??'');
        $state=$sid>0&&$token!==''&&$repo->confirm($sid,hash('sha256',$token))?'confirmed':'invalid';
    }
}elseif($_SERVER['REQUEST_METHOD']==='POST' && (bool)config()->get('smtp.enabled',false)){
    $ok=Csrf::validate($_POST['csrf']??null) && empty($_POST['website']) && isset($_POST['form_started']) && (time()-(int)$_POST['form_started'])>=2;
    if($ok){try{$service->request((string)($_POST['email']??''),translator()->language(),request_client_fingerprint());}catch(Throwable){/* neutral response */}}
    $state='neutral';
}elseif(isset($_GET['sid'],$_GET['token'])){$state='confirm';}
$meta=Metadata::build(config(),t('subscribe.title').' — Thai News',t('subscribe.text'),'subscribe.php'); require __DIR__.'/includes/views/header.php';
?>
<section class="simple-card"><h1><?= e($state==='confirm'?t('subscribe.confirm_title'):t('subscribe.title')) ?></h1>
<?php if($state==='neutral'):?><p><?= e(t('subscribe.neutral')) ?></p>
<?php elseif($state==='unavailable'):?><p><?= e(t('subscribe.unavailable')) ?></p>
<?php elseif($state==='confirmed'):?><p><?= e(t('subscribe.confirmed')) ?></p>
<?php elseif($state==='invalid'):?><p><?= e(t('subscribe.invalid')) ?></p>
<?php elseif($state==='confirm'):?><p><?= e(t('subscribe.confirm_text')) ?></p><form method="post" action="<?= e(url('subscribe.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="confirm_sid" value="<?= (int)$_GET['sid'] ?>"><input type="hidden" name="confirm_token" value="<?= e((string)$_GET['token']) ?>"><button class="primary"><?= e(t('subscribe.confirm_button')) ?></button></form>
<?php else:?><p><?= e(t('subscribe.text')) ?></p><?php endif;?></section>
<?php require __DIR__.'/includes/views/footer.php';

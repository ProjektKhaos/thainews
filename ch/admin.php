<?php
declare(strict_types=1);
require __DIR__.'/access.php';cosmos_access_session(1);
require __DIR__.'/config.php';require __DIR__.'/app/Management.php';
header('Cache-Control: private, no-store');header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'none'; form-action 'self'");
require __DIR__.'/app/AdminForms.php';
$views=['dashboard'=>'Overview','bookings'=>'Bookings','calendar'=>'Calendar','rates'=>'Prices & discounts','content'=>'Website content','media'=>'Photos','mail'=>'Email','settings'=>'House settings','users'=>'Staff accounts','launch'=>'Launch checklist','backup'=>'Backups','audit'=>'Activity','account'=>'My account','guide'=>'Staff guide'];
$view=(string)($_GET['view']??'dashboard');if(!isset($views[$view]))$view='dashboard';
$error='';$notice=(string)($_SESSION['admin_notice']??'');unset($_SESSION['admin_notice']);$app=null;$auth=null;$user=null;$manager=null;
if(empty($_SESSION['cosmos_admin_login_csrf']))$_SESSION['cosmos_admin_login_csrf']=bin2hex(random_bytes(24));
try{$app=\Cosmos\Booking::connect();$auth=new \Cosmos\Auth($app);$user=$auth->current();}catch(Throwable $e){http_response_code(503);$error='Administration is temporarily unavailable. Please contact the site administrator.';error_log('Cosmos admin DB unavailable: '.get_class($e));}
if($auth&&isset($_GET['setup'])){
    if($auth->setupValid((string)$_GET['setup'])){$_SESSION['cosmos_setup_token']=(string)$_GET['setup'];header('Location: '.url('admin.php'),true,303);exit;}
    $error='The setup link has expired or has already been used.';
}
$setup=$auth&&!$user&&$auth->setupValid((string)($_SESSION['cosmos_setup_token']??''));
$csrf=$user?(string)($_SESSION['cosmos_admin_csrf']??''):(string)$_SESSION['cosmos_admin_login_csrf'];
if($_SERVER['REQUEST_METHOD']==='POST'&&$auth){
    try{
        if(!hash_equals($csrf,(string)($_POST['csrf']??'')))throw new \Cosmos\BookingError('Your session token is invalid. Reload the page and try again.',403);
        $action=(string)($_POST['action']??(isset($_POST['username'],$_POST['password'])?'login':''));
        if($action==='login'){$auth->login((string)($_POST['username']??''),(string)($_POST['password']??''));$notice='';}
        elseif($action==='setup'){
            if(($_POST['password']??'')!==($_POST['password_confirm']??''))throw new \Cosmos\BookingError('The passwords do not match.');
            $auth->setup((string)($_SESSION['cosmos_setup_token']??''),trim((string)($_POST['username']??'')),(string)($_POST['password']??''));$_SESSION['admin_notice']='Your administrator account is ready. Add staff accounts and review the house settings.';
        }elseif($action==='logout'){$_SESSION=[];session_regenerate_id(true);}
        else{
            if(!$user)throw new \Cosmos\BookingError('Sign in to continue.',401);
            $manager=new \Cosmos\Management($app,$user);$_SESSION['admin_notice']=$manager->action($action,$_POST,$_FILES);
        }
        $return=(string)($_POST['return_view']??'dashboard');if(!isset($views[$return]))$return='dashboard';$params=[];
        if(isset($_POST['return_id']))$params['id']=(int)$_POST['return_id'];if(isset($_POST['scope']))$params['scope']=(string)$_POST['scope'];
        header('Location: '.au($return,$params),true,303);exit;
    }catch(\Cosmos\BookingError $e){$error=friendly_error($e->getMessage());http_response_code($e->httpStatus);}catch(Throwable $e){$error='The change could not be saved. Please try again.';http_response_code(500);error_log('Cosmos admin failed: '.get_class($e));}
}
if($user){
    $manager=new \Cosmos\Management($app,$user);
    if($user['role']!=='admin'&&in_array($view,['settings','users','launch','backup'],true)){http_response_code(403);$error='Only administrators can access this page.';$view='dashboard';}
    if(isset($_GET['download'])){
        if($user['role']!=='admin'){http_response_code(403);exit('Access denied.');}
        $name=(string)$_GET['download'];if(!preg_match('/^cosmos-full-[0-9]{8}-[0-9]{6}-[a-f0-9]{6}\.tar$/',$name)){http_response_code(404);exit;}
        $path=(new \Cosmos\Backup($app))->directory().'/'.$name;if(!is_file($path)){http_response_code(404);exit;}
        header('Content-Type: application/x-tar');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($path));readfile($path);exit;
    }
    if(isset($_GET['export'])){
        $rows=$manager->query('SELECT reference,name,email,phone,check_in,check_out,guests,status,payment_status,total_satang,created_at FROM bookings ORDER BY check_in DESC');
        header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="cosmos-bookings.csv"');$out=fopen('php://output','w');fputcsv($out,array_keys($rows[0]??array_fill_keys(['reference','name','email','phone','check_in','check_out','guests','status','payment_status','total_satang','created_at'],'')),',','"','');
        foreach($rows as $row){foreach($row as &$value)if(preg_match('/^\s*[=+\-@]/u',(string)$value))$value="'".$value;unset($value);fputcsv($out,$row,',','"','');}fclose($out);exit;
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=ae($user?$views[$view]:'Sign in')?> · Cosmos House admin</title><link rel="stylesheet" href="<?=ae(asset_url('admin.css'))?>"><script src="<?=ae(asset_url('admin.js'))?>" defer></script></head><body>
<?php if(!$user):?><main class="auth"><a class="brand" href="<?=ae(url(''))?>">✳ COSMOS HOUSE</a><h1><?=$setup?'Create your administrator account':'Staff sign in'?></h1><p><?=$setup?'Choose your own username and password. This setup link can be used once.':'Manage bookings, content and the house calendar.'?></p><?php if($error):?><p class="alert error" role="alert"><?=ae($error)?></p><?php endif;?>
<?php if($app):form_start($setup?'setup':'login','dashboard');input('username','Username','','text',true,'autocomplete="username" minlength="3" maxlength="50"');input('password',$setup?'Password (at least 16 characters)':'Password','','password',true,'autocomplete="'.($setup?'new-password':'current-password').'"'.($setup?' minlength="16" maxlength="200"':''));if($setup)input('password_confirm','Repeat password','','password',true,'autocomplete="new-password" minlength="16"');?><button type="submit"><?=$setup?'Create account':'Sign in'?></button></form><?php endif;?><p class="muted">Need access? Ask your administrator to create a staff account.</p></main>
<?php else:?>
<div class="layout"><aside class="sidebar"><a class="brand" href="<?=ae(au())?>">✳ COSMOS HOUSE<small>Administration</small></a><nav aria-label="Administration"><?php foreach($views as $key=>$label):if(in_array($key,['account','guide'],true)||($user['role']!=='admin'&&in_array($key,['settings','users','launch','backup'],true)))continue;?><a href="<?=ae(au($key))?>" <?=$view===$key?'aria-current="page"':''?>><?=ae($label)?></a><?php endforeach;?></nav><div class="sidebar-footer"><a href="<?=ae(url('public.php'))?>" target="_blank" rel="noopener">View website ↗</a><a href="<?=ae(url('en/'))?>" target="_blank" rel="noopener">Full site preview ↗</a><a href="<?=ae(au('guide'))?>">Staff guide</a><a href="<?=ae(au('account'))?>"><?=ae($user['username'])?> · <?=ae($user['role'])?></a><?php form_start('logout','dashboard');?><button class="quiet">Sign out</button></form></div></aside>
<main class="workspace"><header class="page-head"><div><p class="eyebrow">COSMOS HOUSE · CHIANG MAI</p><h1><?=ae($views[$view])?></h1></div><span class="badge <?=$app->publicOpen()?'confirmed':'hold'?>">Public bookings <?=$app->publicOpen()?'open':'closed'?></span></header>
<?php if($notice):?><p class="alert success" role="status"><?=ae($notice)?></p><?php endif;?><?php if($error):?><p class="alert error" role="alert"><?=ae($error)?></p><?php endif;?>
<?php require __DIR__.'/app/views/'.$view.'.php';?>
<footer class="workspace-footer">Dates: Asia/Bangkok · Currency: THB · <?=ae(date('j M Y H:i'))?></footer></main></div>
<?php endif;?></body></html>

<?php
// Senast uppdaterad: 2026-09-20 18:25 | Application bootstrap

declare(strict_types=1);

$root=dirname(__DIR__);
$autoload=$root.'/vendor/autoload.php';
if (!is_file($autoload)) {
    // Tiny PSR-4 fallback permits lint/dev before composer install. Symfony Mailer still requires Composer.
    spl_autoload_register(static function(string $class) use($root): void {
        if (!str_starts_with($class,'ThaiNews\\')) return;
        $path=$root.'/app/'.str_replace('\\','/',substr($class,9)).'.php'; if(is_file($path)) require $path;
    });
} else require $autoload;
require_once $root.'/includes/helpers.php';

use ThaiNews\Config; use ThaiNews\Database; use ThaiNews\Logger; use ThaiNews\Translator; use ThaiNews\Preferences\VisitorIdentity; use ThaiNews\Preferences\PreferencesRepository; use ThaiNews\Security\Headers;

try {
    $config=Config::load($root); $GLOBALS['tn_config']=$config;
    date_default_timezone_set((string)$config->get('timezone','Asia/Bangkok'));
    $logger=new Logger((string)$config->get('storage.logs',$root.'/storage/logs')); $GLOBALS['tn_logger']=$logger;
    $dbObj=new Database($config); $GLOBALS['tn_db']=$dbObj;
    if (PHP_SAPI !== 'cli') {
        $basePath='/' . trim((string)$config->get('base_url',''),'/'); if($basePath==='')$basePath='/';
        $secureCookies=str_starts_with((string)$config->get('public_origin',''),'https://');
        ini_set('session.use_strict_mode','1');
        session_set_cookie_params(['secure'=>$secureCookies,'httponly'=>true,'samesite'=>'Lax','path'=>$basePath]);
        session_name('tn_session'); session_start();
        $visitorRaw=VisitorIdentity::raw($basePath,$secureCookies); $visitorHash=VisitorIdentity::hash($visitorRaw,(string)$config->get('app_secret'));
        $GLOBALS['tn_visitor_hash']=$visitorHash;
        $prefsRepo=new PreferencesRepository($dbObj->pdo()); $GLOBALS['tn_prefs_repo']=$prefsRepo;
        $prefs=$prefsRepo->get($visitorHash,(string)$config->get('default_language','en'));
        $requested=$_GET['lang']??null; $supported=$config->get('supported_languages',['en','th','sv']);
        $language=is_string($requested)&&in_array($requested,$supported,true)?$requested:(string)$prefs['language'];
        if(!in_array($language,$supported,true))$language='en';
        if(is_string($requested)&&in_array($requested,$supported,true))$prefsRepo->touchLanguage($visitorHash,$language);
        $GLOBALS['tn_language']=$language; $GLOBALS['tn_translator']=new Translator($language,$root);
        $nonce=base64_encode(random_bytes(18)); $GLOBALS['tn_csp_nonce']=$nonce; Headers::send($nonce,false);
    } else { $GLOBALS['tn_language']='en'; $GLOBALS['tn_translator']=new Translator('en',$root); }
} catch(Throwable $e) {
    if(isset($logger))$logger->log('app','error','bootstrap_failed',['error_code'=>substr(hash('sha256',$e->getMessage()),0,12)]);
    if(PHP_SAPI==='cli'){fwrite(STDERR,"Thai News bootstrap failed.\n"); exit(70);} http_response_code(500); echo '<!doctype html><meta charset="utf-8"><title>Thai News</title><p>Thai News could not start. Please try again later.</p>'; exit;
}
set_exception_handler(static function(Throwable $e): void { logger()->log('app','error','unhandled_exception',['error_code'=>substr(hash('sha256',get_class($e).'|'.$e->getMessage()),0,12)]); if(PHP_SAPI==='cli'){fwrite(STDERR,"Unhandled Thai News error.\n");exit(70);} http_response_code(500); echo '<!doctype html><meta charset="utf-8"><title>'.e(t('error.title')).'</title><p>'.e(t('error.message')).'</p>'; });

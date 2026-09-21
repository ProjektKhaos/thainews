<?php
// Senast uppdaterad: 2026-09-20 18:25 | Shared helpers

declare(strict_types=1);

use ThaiNews\Config;
use ThaiNews\Translator;
use ThaiNews\Security\Csrf;

function config(): Config { return $GLOBALS['tn_config']; }
function translator(): Translator { return $GLOBALS['tn_translator']; }
function db(): PDO { return $GLOBALS['tn_db']->pdo(); }
function logger(): ThaiNews\Logger { return $GLOBALS['tn_logger']; }
function url(string $path=''): string {
    $base='/' . trim((string)config()->get('base_url',''),'/'); $base=$base==='/'?'':$base;
    return $base . ($path===''?'/':'/'.ltrim($path,'/'));
}
function absolute_url(string $path=''): string { return rtrim((string)config()->get('public_origin'),'/') . url($path); }
function asset_url(string $path): string {
    $version=(string)config()->get('asset_version','1.0.0');
    return url(ltrim($path,'/')) . (str_contains($path,'?')?'&':'?') . 'v=' . rawurlencode($version);
}
function e(?string $value): string { return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function t(string $key,array $vars=[]): string { return translator()->get($key,$vars); }
function csrf_token(): string { return Csrf::token(); }
function format_local_time(string $utc): string {
    $lang=translator()->language(); $locale=['en'=>'en_US','th'=>'th_TH','sv'=>'sv_SE'][$lang]??'en_US';
    $dt=new DateTimeImmutable($utc,new DateTimeZone('UTC')); $dt=$dt->setTimezone(new DateTimeZone((string)config()->get('timezone','Asia/Bangkok')));
    if (class_exists(IntlDateFormatter::class)) { $fmt=new IntlDateFormatter($locale,IntlDateFormatter::MEDIUM,IntlDateFormatter::SHORT,$dt->getTimezone()); return (string)$fmt->format($dt); }
    return $dt->format('Y-m-d H:i');
}
function request_client_fingerprint(): string { return (string)($_SERVER['REMOTE_ADDR']??'unknown').'|'.substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,160); }

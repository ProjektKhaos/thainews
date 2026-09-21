<?php
// Senast uppdaterad: 2026-09-20 18:35 | Preferences JSON API

declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8'); header('X-Robots-Tag: noindex, nofollow');
use ThaiNews\Security\Csrf;use ThaiNews\Security\RateLimiter;
$repo=$GLOBALS['tn_prefs_repo'];$visitor=$GLOBALS['tn_visitor_hash'];
if($_SERVER['REQUEST_METHOD']==='GET'){echo json_encode($repo->get($visitor,translator()->language()),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'method_not_allowed']);exit;}
$limiter=new RateLimiter(db(),config()->requireString('rate_limit_secret'));$key=$limiter->clientKey('preferences',request_client_fingerprint());if(!$limiter->allow('preferences',$key,30,600)){http_response_code(429);echo json_encode(['error'=>'rate_limited']);exit;}
$data=json_decode((string)file_get_contents('php://input'),true);$csrf=$_SERVER['HTTP_X_CSRF_TOKEN']??($data['csrf']??null);if(!Csrf::validate(is_string($csrf)?$csrf:null)){http_response_code(403);echo json_encode(['error'=>'csrf']);exit;}
$lang=$data['language']??'';$sources=$data['sources']??null;if(!in_array($lang,config()->get('supported_languages',['en','th','sv']),true)||!is_array($sources)){http_response_code(422);echo json_encode(['error'=>'invalid_payload']);exit;}
$active=$repo->get($visitor,(string)$lang)['sources'];$known=array_column($active,'slug');$normalized=[];$seen=[];foreach($sources as $s){if(!is_array($s)||!in_array($s['slug']??'', $known,true)){http_response_code(422);echo json_encode(['error'=>'unknown_source']);exit;}$slug=(string)$s['slug'];if(isset($seen[$slug]))continue;$seen[$slug]=true;$normalized[]=['slug'=>$slug,'position'=>max(1,(int)($s['position']??count($normalized)+1)),'visible'=>(bool)($s['visible']??true)];}foreach($active as $a)if(!isset($seen[$a['slug']]))$normalized[]=['slug'=>$a['slug'],'position'=>count($normalized)+1,'visible'=>true];usort($normalized,fn($a,$b)=>$a['position']<=>$b['position']);foreach($normalized as $i=>&$s)$s['position']=$i+1;unset($s);$repo->save($visitor,(string)$lang,$normalized);echo json_encode($repo->get($visitor,(string)$lang),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

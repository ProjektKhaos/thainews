<?php
// Senast uppdaterad: 2026-09-20 18:35 | Send due email digests

declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/bootstrap.php';
use ThaiNews\Digest\{DigestScheduler,DigestService,Mailer};use ThaiNews\Subscription\SubscriberRepository;
$options=getopt('',['slot:','dry-run','limit:']);$dry=array_key_exists('dry-run',$options);$limit=max(1,min(10000,(int)($options['limit']??1000)));$lockPath=(string)config()->get('storage.locks',dirname(__DIR__).'/storage/locks').'/send_digest.lock';$lock=fopen($lockPath,'c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){logger()->log('digest','info','digest_skipped_locked');exit(0);}try{$tz=new DateTimeZone((string)config()->get('timezone','Asia/Bangkok'));$slot=isset($options['slot'])?new DateTimeImmutable((string)$options['slot'],$tz):(new DigestScheduler(config()->get('digest.times',['00:00','06:00','12:00','18:00']),(string)config()->get('timezone','Asia/Bangkok'),(int)config()->get('digest.slot_window_minutes',30)))->latestSlot();if(!$slot){echo "No due digest slot.\n";exit(0);}$svc=new DigestService(db(),new SubscriberRepository(db()),new Mailer(config()),config(),logger());echo json_encode($svc->run($slot,$dry,$limit),JSON_PRETTY_PRINT).PHP_EOL;}finally{flock($lock,LOCK_UN);fclose($lock);}

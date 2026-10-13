<?php
declare(strict_types=1);
namespace Cosmos;
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
final class Readiness {
    public static function setup(Booking $app): array {
        $config=$app->configuration();$mail=$app->mailConfig();
        return [
            'capacity'=>['label'=>'Guest capacity','ready'=>$config['maxGuests']>0 && (int)$app->setting('included_guests','1')<=$config['maxGuests'] && $config['minNights']>0 && $config['maxNights']>=$config['minNights'],'view'=>'settings','adminOnly'=>true],
            'price'=>['label'=>'Reviewed house price','ready'=>(int)$app->setting('base_rate_satang','0')>0 && $app->setting('approved_prices')==='1','view'=>'rates','adminOnly'=>false],
            'contact'=>['label'=>'Contact details','ready'=>trim((string)$app->setting('contact_name',''))!=='' && (bool)filter_var($app->setting('contact_email',''),FILTER_VALIDATE_EMAIL),'view'=>'settings','adminOnly'=>true],
            'policy'=>['label'=>'Privacy information and policy version','ready'=>trim((string)$app->setting('privacy_text_en',''))!=='' && trim((string)$app->setting('policy_version',''))!=='','view'=>'settings','adminOnly'=>true],
            'email'=>['label'=>'Email delivery','ready'=>$mail['mode']==='smtp' && $mail['approved'] && $mail['host']!=='' && $mail['user']!=='' && $mail['password']!=='' && (bool)filter_var($mail['from'],FILTER_VALIDATE_EMAIL) && (bool)filter_var($mail['admin'],FILTER_VALIDATE_EMAIL),'view'=>'mail','adminOnly'=>true,'deferred'=>$app->setting('mail_setup_deferred')==='1' && $mail['mode']!=='smtp'],
        ];
    }
    public static function health(Booking $app): array {
        $rows=[];
        foreach(['worker'=>['label'=>'Background processing','key'=>'last_mail_worker_at','maxAge'=>180],'backup'=>['label'=>'Verified backup','key'=>'last_backup_at','maxAge'=>26*3600]]as $key=>$spec){
            $value=$app->setting($spec['key'],'');$time=false;
            try{if($value!=='')$time=new \DateTimeImmutable($value,new \DateTimeZone('UTC'));}catch(\Throwable $e){}
            $ready=$time!==false && time()-$time->getTimestamp()<=$spec['maxAge'] && $time->getTimestamp()<=time()+60;
            $rows[$key]=['label'=>$spec['label'],'ready'=>$ready,'last'=>$time?$time->setTimezone(new \DateTimeZone('Asia/Bangkok'))->format('j M Y H:i'):'No successful run yet'];
        }
        return $rows;
    }
}

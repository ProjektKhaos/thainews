<?php
// Senast uppdaterad: 2026-09-20 19:20 | Idempotent, translated digest sender

declare(strict_types=1);

namespace ThaiNews\Digest;

use PDO;
use ThaiNews\Config;
use ThaiNews\Logger;
use ThaiNews\Security\Tokens;
use ThaiNews\Subscription\SubscriberRepository;

final class DigestService
{
    public function __construct(private PDO $pdo, private SubscriberRepository $subs, private Mailer $mailer, private Config $config, private Logger $logger) {}

    /** @return array{sent:int,would_send:int,skipped:int,failed:int} */
    public function run(\DateTimeImmutable $slot, bool $dryRun=false, int $limit=1000): array
    {
        $counts=['sent'=>0,'would_send'=>0,'skipped'=>0,'failed'=>0];
        $slotUtc=$slot->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $afterId=0;
        $processed=0;
        while ($processed<$limit) {
            $batch=$this->subs->eligibleForSlot($slotUtc,$afterId,min(100,$limit-$processed));
            if($batch===[])break;
            foreach($batch as $sub){
                $afterId=(int)$sub['id'];$processed++;
                try{$state=$this->sendOne($sub,$slot,$dryRun);$counts[$state]++;}
                catch(\Throwable $e){$counts['failed']++;$this->logger->log('digest','error','subscriber_digest_failed',['subscriber_id'=>(int)$sub['id'],'error_code'=>self::code($e)]);}
            }
        }
        return $counts;
    }

    /** @param array<string,mixed> $sub */
    private function sendOne(array $sub, \DateTimeImmutable $slot, bool $dryRun): string
    {
        $slotUtc=$slot->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $log=null;
        if(!$dryRun){
            $claim=$this->pdo->prepare("INSERT IGNORE INTO digest_log(subscriber_id,scheduled_slot,status,article_ids,attempts,created_at,updated_at) VALUES(?,?,'claimed','[]',0,UTC_TIMESTAMP(),UTC_TIMESTAMP())");
            $claim->execute([(int)$sub['id'],$slotUtc]);
            $stmt=$this->pdo->prepare('SELECT * FROM digest_log WHERE subscriber_id=? AND scheduled_slot=?');$stmt->execute([(int)$sub['id'],$slotUtc]);$log=$stmt->fetch();
            if(!$log||in_array($log['status'],['sent','skipped_empty'],true)||(int)$log['attempts']>=3)return 'skipped';
        }

        $chosen=[];
        $savedIds=is_array($log)?$this->decodeIds((string)$log['article_ids']):[];
        if($savedIds!==[])$chosen=$this->articlesByIds($savedIds,(string)$sub['language']);
        else $chosen=$this->selectArticles($sub,$slotUtc);

        if($chosen===[]){
            if(!$dryRun&&is_array($log))$this->pdo->prepare("UPDATE digest_log SET status='skipped_empty',updated_at=UTC_TIMESTAMP() WHERE id=?")->execute([(int)$log['id']]);
            return 'skipped';
        }
        if($dryRun)return 'would_send';

        $ids=array_map(static fn(array $row):int=>(int)$row['id'],$chosen);
        $this->pdo->prepare("UPDATE digest_log SET article_ids=?,attempts=attempts+1,updated_at=UTC_TIMESTAMP() WHERE id=?")->execute([json_encode($ids),(int)$log['id']]);
        [$subject,$html,$text]=$this->render($sub,$chosen);
        try{
            $messageId=$this->mailer->sendDigest((string)$sub['email'],$subject,$html,$text);
            $this->pdo->prepare("UPDATE digest_log SET status='sent',message_id=?,sent_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=?")->execute([$messageId,(int)$log['id']]);
            return 'sent';
        }catch(\Throwable $e){
            $this->pdo->prepare("UPDATE digest_log SET status='failed',error_code=?,updated_at=UTC_TIMESTAMP() WHERE id=?")->execute([self::code($e),(int)$log['id']]);
            return 'failed';
        }
    }

    /** @param array<string,mixed> $sub @return list<array<string,mixed>> */
    private function selectArticles(array $sub,string $slotUtc):array
    {
        $since=(string)($sub['confirmed_at']?:$sub['created_at']);
        $prev=$this->pdo->prepare("SELECT scheduled_slot FROM digest_log WHERE subscriber_id=? AND status IN ('sent','skipped_empty') AND scheduled_slot<? ORDER BY scheduled_slot DESC LIMIT 1");
        $prev->execute([(int)$sub['id'],$slotUtc]);$cursor=$prev->fetchColumn();if($cursor)$since=(string)$cursor;
        $q=$this->pdo->prepare("SELECT a.*,s.name source_name,s.default_order,COALESCE(t.translated_title,a.title) display_title,CASE WHEN t.translated_title IS NULL THEN 0 ELSE 1 END title_is_translated
                                FROM articles a JOIN news_sources s ON s.id=a.source_id
                                LEFT JOIN article_translations t ON t.article_id=a.id AND t.language=? AND t.status='success' AND t.source_text_hash=SHA2(a.title,256)
                                WHERE s.enabled=1 AND a.first_seen_at>? AND a.first_seen_at<=?
                                ORDER BY s.default_order,a.published_at DESC LIMIT 250");
        $q->execute([(string)$sub['language'],$since,$slotUtc]);
        return $this->cap($q->fetchAll());
    }

    /** @param list<int> $ids @return list<array<string,mixed>> */
    private function articlesByIds(array $ids,string $language):array
    {
        if($ids===[])return[];$marks=implode(',',array_fill(0,count($ids),'?'));
        $stmt=$this->pdo->prepare("SELECT a.*,s.name source_name,s.default_order,COALESCE(t.translated_title,a.title) display_title,CASE WHEN t.translated_title IS NULL THEN 0 ELSE 1 END title_is_translated
                                  FROM articles a JOIN news_sources s ON s.id=a.source_id
                                  LEFT JOIN article_translations t ON t.article_id=a.id AND t.language=? AND t.status='success' AND t.source_text_hash=SHA2(a.title,256)
                                  WHERE a.id IN ({$marks})");
        $stmt->execute(array_merge([$language],$ids));$rows=$stmt->fetchAll();$order=array_flip($ids);
        usort($rows,static fn(array $a,array $b):int=>$order[(int)$a['id']]<=>$order[(int)$b['id']]);return $rows;
    }

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    private function cap(array $rows):array
    {
        $maxPer=(int)$this->config->get('digest.max_per_source',10);$maxTotal=(int)$this->config->get('digest.max_total',40);$counts=[];$chosen=[];
        foreach($rows as $row){$sid=(int)$row['source_id'];if(($counts[$sid]??0)>=$maxPer)continue;$chosen[]=$row;$counts[$sid]=($counts[$sid]??0)+1;if(count($chosen)>=$maxTotal)break;}
        return $chosen;
    }

    /** @return list<int> */
    private function decodeIds(string $json):array
    {
        $ids=json_decode($json,true);if(!is_array($ids))return[];return array_values(array_filter(array_map('intval',$ids),static fn(int $id):bool=>$id>0));
    }

    /** @param array<string,mixed> $sub @param list<array<string,mixed>> $rows @return array{string,string,string} */
    private function render(array $sub,array $rows):array
    {
        $lang=(string)$sub['language'];$subject=['en'=>'Thai News digest','th'=>'สรุปข่าว Thai News','sv'=>'Thai News – nyhetssammanfattning'][$lang]??'Thai News digest';
        $groups=[];foreach($rows as $row)$groups[(string)$row['source_name']][]=$row;
        $logo=$this->publicUrl('img/thainews_logo1.png');
        $html='<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;background:#f5f7fa"><div style="max-width:640px;margin:auto;padding:20px;font-family:Arial,sans-serif;color:#142236"><div style="background:#fff;border:1px solid #e4e9ef;border-radius:16px;padding:24px"><img src="'.self::h($logo).'" alt="Thai News" width="110" style="display:block;width:110px;height:auto;margin:0 0 18px"><h1 style="font-size:24px;margin:0 0 22px">'.self::h($subject).'</h1>';
        $text="Thai News\n\n";
        foreach($groups as $name=>$items){$html.='<h2 style="font-size:19px;border-bottom:2px solid #0b4a8b;padding-bottom:6px">'.self::h($name).'</h2>';$text.=$name."\n";foreach($items as $row){$title=(string)($row['display_title']??$row['title']);$html.='<p style="margin:0 0 18px"><a href="'.self::h((string)$row['canonical_url']).'" style="font-weight:700;color:#0b4a8b;text-decoration:none">'.self::h($title).'</a><br><span style="font-size:12px;color:#66758a">'.self::h($this->formatTime((string)$row['published_at'],$lang)).'</span>';if((string)$row['excerpt']!=='')$html.='<br><span style="color:#52606d">'.self::h(mb_strimwidth((string)$row['excerpt'],0,300,'…','UTF-8')).'</span>';$html.='</p>';$text.='- '.$title.' '.$row['canonical_url']."\n";} $text.="\n";}
        $version=(int)$sub['unsubscribe_token_version'];$sig=Tokens::unsubscribeSignature((int)$sub['id'],$version,$this->config->requireString('app_secret'));$url=$this->publicUrl('unsubscribe.php').'?sid='.(int)$sub['id'].'&v='.$version.'&sig='.rawurlencode($sig);$label=['en'=>'Unsubscribe','th'=>'ยกเลิกการสมัคร','sv'=>'Avsluta prenumeration'][$lang]??'Unsubscribe';
        $html.='<hr style="border:0;border-top:1px solid #e4e9ef"><p style="font-size:13px"><a href="'.self::h($url).'">'.self::h($label).'</a></p></div></div></body></html>';$text.=$label.': '.$url;
        return[$subject,$html,$text];
    }

    private function formatTime(string $utc,string $lang):string
    {
        $locale=['en'=>'en_US','th'=>'th_TH','sv'=>'sv_SE'][$lang]??'en_US';$dt=(new \DateTimeImmutable($utc,new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone((string)$this->config->get('timezone','Asia/Bangkok')));
        if(class_exists(\IntlDateFormatter::class)){$fmt=new \IntlDateFormatter($locale,\IntlDateFormatter::MEDIUM,\IntlDateFormatter::SHORT,$dt->getTimezone());return(string)$fmt->format($dt);}return$dt->format('Y-m-d H:i');
    }
    private function publicUrl(string $path):string{$origin=rtrim($this->config->requireString('public_origin'),'/');$base=trim((string)$this->config->get('base_url',''),'/');return$origin.($base===''?'':'/'.$base).'/'.ltrim($path,'/');}
    private static function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private static function code(\Throwable $e):string{return strtoupper(substr(hash('sha256',get_class($e).'|'.$e->getMessage()),0,12));}
}

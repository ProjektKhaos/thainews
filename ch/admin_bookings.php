<?php
declare(strict_types=1);
if (!isset($user,$app) || !$user) { http_response_code(404); exit; }
$filterValues=['booking_q'=>$bookingQuery,'booking_status'=>$bookingStatus,'booking_from'=>$bookingFrom,'booking_to'=>$bookingTo];
$pageLink=static function(int $page) use($filterValues): string {return url('admin.php').'?'.http_build_query(array_filter($filterValues,static fn($value)=>$value!=='' )+['booking_page'=>$page]).'#bookings';};
?>
<form method="get" action="<?=ae(url('admin.php'))?>#bookings" class="booking-filters">
<label>Sök referens, namn eller e-post <input name="booking_q" value="<?=ae($bookingQuery)?>" maxlength="100"></label>
<label>Status <select name="booking_status"><option value="">Alla</option><?php foreach(['inquiry'=>'Förfrågan','hold'=>'Reservation','confirmed'=>'Bekräftad','rejected'=>'Avböjd','cancelled'=>'Avbokad','expired'=>'Utgången'] as $value=>$label):?><option value="<?=ae($value)?>" <?=$bookingStatus===$value?'selected':''?>><?=ae($label)?></option><?php endforeach;?></select></label>
<label>Från <input type="date" name="booking_from" value="<?=ae($bookingFrom)?>"></label>
<label>Till <input type="date" name="booking_to" value="<?=ae($bookingTo)?>"></label>
<button>Sök</button><a href="<?=ae(url('admin.php'))?>#bookings">Rensa</a>
</form>
<p><?=number_format($bookingCount,0,',',' ')?> bokningar matchar. Visar högst 25 per sida.</p>
<?php if($bookingDetail): $snapshot=json_decode((string)$bookingDetail['price_snapshot_json'],true);$snapshot=is_array($snapshot)?$snapshot:[];
$stmt=$app->db->prepare('SELECT kind,status,attempts,last_error,created_at FROM notification_outbox WHERE event_key LIKE ? ORDER BY id DESC LIMIT 25');$stmt->execute(['booking:'.$bookingId.':%']);$detailMail=$stmt->fetchAll();
$stmt=$app->db->prepare('SELECT actor,before_json,after_json,created_at FROM booking_revisions WHERE booking_id=? ORDER BY id DESC LIMIT 25');$stmt->execute([$bookingId]);$detailRevisions=$stmt->fetchAll(); ?>
<details open><summary>Detalj: <?=ae($bookingDetail['reference'])?></summary>
<p>Status: <?=ae($bookingDetail['status'])?> · Betalning: <?=ae($bookingDetail['payment_status'])?> · Skapad: <?=ae($bookingDetail['created_at'])?></p>
<p>Gäst: <?=ae($bookingDetail['name'])?> · <?=ae($bookingDetail['email'])?> · <?=ae($bookingDetail['phone'])?></p>
<p>Vistelse: <?=ae($bookingDetail['check_in'])?>–<?=ae($bookingDetail['check_out'])?> · <?=(int)$bookingDetail['guests']?> gäster · <?=number_format($bookingDetail['total_satang']/100,2,',',' ')?> THB</p>
<?php if($bookingDetail['expires_at']):?><p>Reservation upphör: <?=ae($bookingDetail['expires_at'])?> UTC</p><?php endif;?>
<?php if($bookingDetail['message']):?><p>Gästmeddelande: <?=nl2br(ae($bookingDetail['message']))?></p><?php endif;?>
<?php if($bookingDetail['internal_note']):?><p>Intern notering: <?=nl2br(ae($bookingDetail['internal_note']))?></p><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=ae($csrf)?>"><input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?=$bookingId?>"><label>Intern notering <textarea name="note" maxlength="2000" rows="3"><?=ae($bookingDetail['internal_note'])?></textarea></label><button>Spara notering</button></form>
<details><summary>Prissnapshot</summary><pre><?=ae(json_encode($snapshot,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE))?></pre></details>
<h3>Historik</h3><ul><?php foreach($bookingHistory as $event):?><li><?=ae($event['created_at'])?> · <?=ae($event['actor'])?> · <?=ae($event['action'])?> · <?=ae($event['status_from'])?> → <?=ae($event['status_to'])?></li><?php endforeach;?></ul>
<?php if($detailRevisions):?><details><summary>Ombokningsrevisioner (<?=count($detailRevisions)?>)</summary><ul><?php foreach($detailRevisions as $revision):?><li><?=ae($revision['created_at'])?> · <?=ae($revision['actor'])?></li><?php endforeach;?></ul></details><?php endif;?>
<h3>Mejl för ärendet</h3><ul><?php foreach($detailMail as $event):?><li><?=ae($event['created_at'])?> · <?=ae($event['kind'])?> · <?=ae($event['status'])?> · försök <?=(int)$event['attempts']?> <?=ae($event['last_error'])?></li><?php endforeach;?></ul>
</details><?php elseif($bookingId):?><p>Det begärda ärendet saknas.</p><?php endif;?>
<nav aria-label="Bokningssidor"><?php if($bookingPage>1):?><a href="<?=ae($pageLink($bookingPage-1))?>">← Föregående</a><?php endif;?><span>Sida <?=$bookingPage?> av <?=max(1,(int)ceil($bookingCount/25))?></span><?php if($bookingPage*25<$bookingCount):?><a href="<?=ae($pageLink($bookingPage+1))?>">Nästa →</a><?php endif;?></nav>

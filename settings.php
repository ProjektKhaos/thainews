<?php
// Senast uppdaterad: 2026-09-20 18:30 | Anonymous settings

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
header('X-Robots-Tag: noindex, follow');
use ThaiNews\Metadata; use ThaiNews\Preferences\PreferencesRepository; use ThaiNews\Security\Csrf;
$repo=$GLOBALS['tn_prefs_repo']; $visitor=$GLOBALS['tn_visitor_hash']; $supported=config()->get('supported_languages',['en','th','sv']);
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Csrf::validate($_POST['csrf']??null)){http_response_code(400);} else {
  $lang=in_array($_POST['language']??'', $supported,true)?(string)$_POST['language']:'en';
  $fontFamily=PreferencesRepository::normalizeFontFamily($_POST['font_family']??null); $fontSize=PreferencesRepository::normalizeFontSize($_POST['font_size']??null);
  $slugs=array_values($_POST['source_slug']??[]);$visible=$_POST['visible']??[];$sources=[];
  if(isset($_POST['move'])&&is_string($_POST['move'])&&preg_match('/^(up|down):([a-z0-9-]+)$/',$_POST['move'],$move)){
   $index=array_search($move[2],$slugs,true);$target=$move[1]==='up'?$index-1:$index+1;
   if($index!==false&&isset($slugs[$target]))[$slugs[$index],$slugs[$target]]=[$slugs[$target],$slugs[$index]];
  }
  foreach(array_values($slugs) as $i=>$slug)$sources[]=['slug'=>(string)$slug,'position'=>$i+1,'visible'=>isset($visible[$slug])];
  try{$repo->save($visitor,$lang,$sources,$fontFamily,$fontSize); header('Location: '.url('settings.php?lang='.$lang.'&saved=1')); exit;}catch(Throwable){http_response_code(422);}
 }
}
$prefs=$repo->get($visitor,translator()->language()); $meta=Metadata::build(config(),t('settings.title').' — Thai News',t('settings.intro'),'settings.php'); require __DIR__.'/includes/views/header.php';
?>
<section class="settings-panel">
<h1><?= e(t('settings.title')) ?></h1>
<p><?= e(t('settings.intro')) ?></p>
<a class="tour-restart" id="tour-restart" href="#tour-dialog" hidden><?= e(t('tour.restart')) ?></a>
<div class="theme-setting"><button class="theme-toggle" type="button" aria-label="<?= e(t('settings.theme')) ?>" aria-pressed="false"><span class="theme-sun" aria-hidden="true">☀</span><span class="theme-moon" aria-hidden="true">☾</span><span><?= e(t('settings.theme')) ?></span></button></div>
<?php if(isset($_GET['saved'])):?><p class="notice" role="status"><?= e(t('settings.saved')) ?></p><?php endif; ?>
<form method="post" id="settings-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<fieldset><legend><?= e(t('settings.language')) ?></legend><label><input type="radio" name="language" value="en" aria-label="English"<?= $prefs['language']==='en'?' checked':'' ?>> <img class="flag-icon" src="<?= e(asset_url('img/flags/gb.svg')) ?>" alt="" width="24" height="12"></label><label><input type="radio" name="language" value="th" aria-label="ไทย"<?= $prefs['language']==='th'?' checked':'' ?>> <img class="flag-icon" src="<?= e(asset_url('img/flags/th.svg')) ?>" alt="" width="24" height="16"></label><label><input type="radio" name="language" value="sv" aria-label="Svenska"<?= $prefs['language']==='sv'?' checked':'' ?>> <img class="flag-icon" src="<?= e(asset_url('img/flags/se.svg')) ?>" alt="" width="24" height="15"></label></fieldset>
<fieldset class="typography-settings"><legend><?= e(t('settings.typography')) ?></legend><div class="typography-grid">
<label for="font-family"><?= e(t('settings.font_family')) ?><select id="font-family" name="font_family"><option value="inter"<?= $prefs['font_family']==='inter'?' selected':'' ?>><?= e(t('settings.font_inter')) ?></option><option value="system"<?= $prefs['font_family']==='system'?' selected':'' ?>><?= e(t('settings.font_system')) ?></option><option value="serif"<?= $prefs['font_family']==='serif'?' selected':'' ?>><?= e(t('settings.font_serif')) ?></option><option value="mono"<?= $prefs['font_family']==='mono'?' selected':'' ?>><?= e(t('settings.font_mono')) ?></option></select></label>
<label for="font-size"><?= e(t('settings.font_size')) ?><select id="font-size" name="font_size"><option value="small"<?= $prefs['font_size']==='small'?' selected':'' ?>><?= e(t('settings.size_small')) ?></option><option value="medium"<?= $prefs['font_size']==='medium'?' selected':'' ?>><?= e(t('settings.size_medium')) ?></option><option value="large"<?= $prefs['font_size']==='large'?' selected':'' ?>><?= e(t('settings.size_large')) ?></option><option value="xlarge"<?= $prefs['font_size']==='xlarge'?' selected':'' ?>><?= e(t('settings.size_xlarge')) ?></option></select></label>
</div></fieldset>
<h2><?= e(t('settings.sources')) ?></h2><p><?= e(t('settings.drag')) ?></p>
<ol id="source-sort" class="source-sort"><?php foreach($prefs['sources'] as $s):?><li data-source="<?= e((string)$s['slug']) ?>"><span class="drag-handle" aria-hidden="true">⋮⋮</span><input type="hidden" name="source_slug[]" value="<?= e((string)$s['slug']) ?>"><strong><?= e((string)$s['name']) ?></strong><label><input type="checkbox" name="visible[<?= e((string)$s['slug']) ?>]"<?= (bool)$s['visible']?' checked':'' ?>> <?= e(t('settings.visible')) ?></label><button type="submit" name="move" value="up:<?= e((string)$s['slug']) ?>" class="move-up" aria-label="<?= e(t('settings.move_up')) ?>">↑</button><button type="submit" name="move" value="down:<?= e((string)$s['slug']) ?>" class="move-down" aria-label="<?= e(t('settings.move_down')) ?>">↓</button></li><?php endforeach;?></ol>
<button class="primary" type="submit"><?= e(t('settings.save')) ?></button></form>
</section>
<script src="<?= e(asset_url('assets/js/vendor/sortable.min.js')) ?>" defer></script><script src="<?= e(asset_url('assets/js/settings.js')) ?>" defer></script>
<?php require __DIR__.'/includes/views/footer.php';

<?php
if(!isset($manager,$user)){http_response_code(404);exit;}
$cms=new \Cosmos\Cms($app);$library=[];foreach($cms->gallery(true)as $item)$library[$item['key']]=$item;
$controls=static function(string $key)use($library):void {
    $item=$library[$key];
    echo '<details><summary>Full gallery settings</summary>';
    form_start('gallery_photo','media',['photo_key'=>$key]);
    input('label','Caption',$item['label'],'text',true,'maxlength="120"');
    select('category','Category',['living'=>'Living spaces','bedroom'=>'Bedrooms','terrace'=>'Terrace','details'=>'Details'],$item['category']);
    input('position','Position',$item['position'],'number',true,'min="1" max="10000"');
    checkbox('visible','Show in the full site gallery',(bool)$item['visible']);
    echo '<button data-confirm="Publish these full gallery photo settings?">Publish photo settings</button></form></details>';
};
$uploaded=$manager->query('SELECT * FROM media ORDER BY id DESC');
?>
<section class="card"><h2>Upload a photo</h2><p class="help">JPEG, PNG or WebP, up to 8 MB and 24 megapixels. Photos are converted to WebP and stored privately. Choose them in Website content to publish them.</p><?php form_start('upload','media',[],true);input('label','Photo name','','text',true,'maxlength="120"');?><label>Photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label><button>Upload photo</button></form></section>
<?php if($uploaded):?><section class="card"><h2>Uploaded photos</h2><div class="photos"><?php foreach($uploaded as $photo):?><div class="photo"><img src="<?=ae(url('media.php?id='.$photo['id']))?>" alt="<?=ae($photo['label'])?>" loading="lazy"><p><?=ae($photo['label'])?><br><?=(int)$photo['width']?> × <?=(int)$photo['height']?></p><?php $controls('upload-'.$photo['id']);?></div><?php endforeach;?></div></section><?php endif;?>
<section class="card"><h2>Existing house photos</h2><p class="help">All 38 original house photos are available in the landing page photo selector.</p><div class="photos"><?php for($i=1;$i<=38;$i++):$image=$cms->image('photo-'.sprintf('%02d',$i));?><div class="photo"><img src="<?=ae(url($image['src']))?>" alt="House photo <?=sprintf('%02d',$i)?>" loading="lazy"><p>House photo <?=sprintf('%02d',$i)?></p><?php $controls('photo-'.sprintf('%02d',$i));?></div><?php endfor;?></div></section>

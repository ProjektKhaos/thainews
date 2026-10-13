<?php
declare(strict_types=1);
namespace Cosmos;
require_once __DIR__.'/Booking.php';
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME'])===__FILE__) { http_response_code(404); exit; }
final class Cms {
    public const SCOPES=['landing'=>'Landing page','page-home'=>'Home','page-house'=>'The house','page-gallery'=>'Gallery','page-experience'=>'The experience','page-location'=>'Chiang Mai','page-book'=>'Booking introduction'];
    public function __construct(private Booking $app) {}
    public static function template(string $scope): string {
        if (!isset(self::SCOPES[$scope])) throw new BookingError('Unknown content page.');
        $root=dirname(__DIR__);
        return $scope==='landing'?$root.'/index.html':$root.'/en/'.($scope==='page-home'?'':substr($scope,5).'/').'index.php';
    }
    public function fields(string $scope): array {
        $html=(string)file_get_contents(self::template($scope));
        $html=preg_replace('/<\?php.*?\?>/s','',$html);
        $fields=[];
        preg_match_all('/<([a-z][a-z0-9]*)\b[^>]*data-cms="([a-z0-9_-]+)"[^>]*>(.*?)<\/\1>/si',$html,$matches,PREG_SET_ORDER);
        foreach ($matches as $match) {
            $value=html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i',"\n",$match[3])),ENT_QUOTES|ENT_HTML5,'UTF-8');
            $fields[$match[2]]=['label'=>ucfirst(str_replace(['_','-'],' ',$match[2])),'default'=>$value,'type'=>'text'];
        }
        if ($scope==='landing') {
            $fields['seo_title']=['label'=>'Search title','default'=>'Cosmos House · Chiang Mai | Coming Soon','type'=>'text'];
            $fields['seo_description']=['label'=>'Search description','default'=>'Cosmos House in Chiang Mai, Thailand. Take a first look at our home. Our new website is coming soon.','type'=>'text'];
            $fields['hero_slot']=['label'=>'Main photo (gallery position)','default'=>'1','type'=>'slot'];
            $fields['inset_slot']=['label'=>'Small photo (gallery position)','default'=>'0','type'=>'slot'];
            $fields['hero_radius']=['label'=>'Main photo corner radius (px)','default'=>'18','type'=>'number'];
            for($i=0;$i<8;$i++) $fields['image_'.$i]=['label'=>'Photo '.($i+1),'default'=>'photo-'.sprintf('%02d',$i+1),'type'=>'image'];
        }
        $q=$this->app->db->prepare('SELECT key,draft,published,updated_at,published_at FROM cms_content WHERE scope=?');$q->execute([$scope]);
        foreach($fields as &$field) {$field['draft']=$field['default'];$field['published']=$field['default'];}unset($field);
        foreach($q as $row) if(isset($fields[$row['key']])) {$fields[$row['key']]['draft']=$row['draft'];$fields[$row['key']]['published']=$row['published']??$fields[$row['key']]['default'];}
        return $fields;
    }
    public function save(string $scope,array $values,string $actor,bool $publish): void {
        $fields=$this->fields($scope);$clean=[];
        foreach($values as $key=>$value) {
            if(!isset($fields[$key]) || !is_string($value)) throw new BookingError('Unknown content field.');
            $value=trim($value);
            if(mb_strlen($value)>5000 || str_contains($value,"\0")) throw new BookingError('Content is too long.');
            if($fields[$key]['type']==='image') $this->image($value);
            if($fields[$key]['type']==='slot' && !in_array($value,array_map('strval',range(0,7)),true)) throw new BookingError('Choose a gallery position.');
            if($fields[$key]['type']==='number' && (!ctype_digit($value)||(int)$value>100)) throw new BookingError('Use a radius between 0 and 100 pixels.');
            $clean[$key]=$value;
        }
        $db=$this->app->db;$db->exec('BEGIN IMMEDIATE');
        try {
            $q=$db->prepare('INSERT INTO cms_content(scope,key,draft) VALUES(?,?,?) ON CONFLICT(scope,key) DO UPDATE SET draft=excluded.draft,updated_at=CURRENT_TIMESTAMP');
            foreach($clean as $key=>$value) $q->execute([$scope,$key,$value]);
            if($publish) {$q=$db->prepare('UPDATE cms_content SET published=draft,published_at=CURRENT_TIMESTAMP WHERE scope=?');$q->execute([$scope]);}
            $q=$db->prepare('INSERT INTO audit_log(actor,action) VALUES(?,?)');$q->execute([$actor,($publish?'content_published:':'content_draft_saved:').$scope]);
            $db->exec('COMMIT');
        } catch(\Throwable $error) { $db->exec('ROLLBACK');throw $error; }
    }
    public function image(string $value): array {
        if(preg_match('/^photo-(0[1-9]|[12][0-9]|3[0-8])$/',$value,$m)) {
            $file=dirname(__DIR__).'/assets/photos/'.$value.'.webp';$size=getimagesize($file);
            return ['src'=>(int)$m[1]<=8?'photos/'.$value.'.webp':'assets/photos/'.$value.'.webp','width'=>$size[0],'height'=>$size[1],'label'=>$value];
        }
        if(preg_match('/^upload-([1-9][0-9]*)$/',$value,$m)) {
            $q=$this->app->db->prepare('SELECT * FROM media WHERE id=?');$q->execute([(int)$m[1]]);$image=$q->fetch();
            if($image) return ['src'=>'media.php?id='.$image['id'],'width'=>$image['width'],'height'=>$image['height'],'label'=>$image['label']];
        }
        throw new BookingError('The selected photo is unavailable.');
    }
    public function render(string $html,string $scope,bool $draft=false): string {
        $fields=$this->fields($scope);$values=[];
        foreach($fields as $key=>$field) $values[$key]=$field[$draft?'draft':'published'];
        $escape=static fn(string $value):string=>htmlspecialchars($value,ENT_QUOTES,'UTF-8');
        $html=preg_replace_callback('/(<([a-z][a-z0-9]*)\b[^>]*data-cms="([a-z0-9_-]+)"[^>]*>)(.*?)(<\/\2>)/si',static function(array $m)use($values,$fields,$escape):string {
            if(!isset($values[$m[3]]) || $values[$m[3]]===$fields[$m[3]]['default']) return $m[0];
            return $m[1].nl2br($escape($values[$m[3]]),false).$m[5];
        },$html);
        if($scope!=='landing') {
            $html=preg_replace('/<div class="language-switch"[^>]*>.*?<\/div>/s','',$html);
            $gallery=$this->gallery();
            $html=preg_replace_callback('/window\.COSMOS=\{gallery:\[.*?\],base:/s',static fn()=>'window.COSMOS={gallery:'.json_encode(array_values($gallery),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE).',base:',$html);
            $html=preg_replace_callback('/(<div class="gallery-actions"><span><b>)[0-9]+(<\/b>)/',static fn($m)=>$m[1].count($gallery).$m[2],$html);
            $html=preg_replace_callback('/<button\b[^>]*data-open="[0-9]+"[^>]*>.*?<\/button>/s',static function($m)use($gallery):string{
                foreach($gallery as $index=>$photo)if(str_contains($m[0],$photo['src']))return preg_replace('/data-open="[0-9]+"/','data-open="'.$index.'"',$m[0]);
                return '';
            },$html);
            return $html;
        }
        $html=preg_replace_callback('/<title>.*?<\/title>/s',static fn()=>'<title>'.$escape($values['seo_title']).'</title>',$html);
        $description=$escape($values['seo_description']);
        $html=preg_replace_callback('/(<meta\s+(?:name="description"|property="og:description")\s+content=")[^"]*(")/i',static fn($m)=>$m[1].$description.$m[2],$html);
        $html=preg_replace_callback('/border-radius: 18px; background: #6d7867/',static fn()=> 'border-radius: '.(int)$values['hero_radius'].'px; background: #6d7867',$html);
        $html=preg_replace_callback('/(<a\b[^>]*data-cms-photo="(hero|inset|slot-[0-7])"[^>]*>)(.*?)(<\/a>)/s',function($m)use($values,$escape):string {
            $slot=$m[2]==='hero'?(int)$values['hero_slot']:($m[2]==='inset'?(int)$values['inset_slot']:(int)substr($m[2],5));
            $image=$this->image($values['image_'.$slot]);$label=$values['caption_'.$slot]??$image['label'];
            $open=preg_replace_callback('/href="[^"]*"/',static fn()=> 'href="'.$escape($image['src']).'"',$m[1]);
            $open=preg_replace('/data-open-photo="[0-7]"/','data-open-photo="'.$slot.'"',$open);
            $open=preg_replace_callback('/aria-label="[^"]*"/',static fn()=> 'aria-label="View '.$escape($label).' photo"',$open);
            $inner=preg_replace_callback('/<img\b[^>]*>/',static function($img)use($image,$label,$escape):string {
                $tag=$img[0];
                foreach(['src'=>$image['src'],'alt'=>$label.' — Cosmos House','width'=>(string)$image['width'],'height'=>(string)$image['height']] as $attribute=>$value) $tag=preg_replace_callback('/'.$attribute.'="[^"]*"/',static fn()=> $attribute.'="'.$escape($value).'"',$tag);
                return $tag;
            },$m[3]);
            return $open.$inner.$m[4];
        },$html);
        return $html;
    }
    public static function apply(string $html,string $scope,bool $draft=false): string {
        try{
            $app=Booking::connect();
            if(isset($_GET['cms_preview'])) {
                require_once __DIR__.'/Auth.php';
                if(!(new Auth($app))->current()){http_response_code(403);return 'Sign in to preview unpublished content.';}
                $draft=true;
            }
            return (new self($app))->render($html,$scope,$draft);
        }catch(\Throwable $e){error_log('Cosmos CMS unavailable: '.get_class($e));return $html;}
    }
    public function gallery(bool $includeHidden=false): array {
        $html=(string)file_get_contents(dirname(__DIR__).'/en/gallery/index.php');
        preg_match('/window\.COSMOS=\{gallery:(\[.*?\]),base:/s',$html,$match);
        $original=json_decode($match[1]??'[]',true)?:[];$result=[];
        foreach($original as $i=>$photo){$key='photo-'.sprintf('%02d',$i+1);$result[$key]=$photo+['key'=>$key,'position'=>$i+1,'visible'=>1];}
        foreach($this->app->db->query('SELECT * FROM media ORDER BY id')as $photo){$key='upload-'.$photo['id'];$result[$key]=['src'=>'media.php?id='.$photo['id'],'label'=>$photo['label'],'category'=>'details','key'=>$key,'position'=>38+(int)$photo['id'],'visible'=>0];}
        foreach($this->app->db->query('SELECT * FROM gallery_photos')as $row)if(isset($result[$row['photo_key']]))$result[$row['photo_key']]=array_merge($result[$row['photo_key']],['label'=>$row['label'],'category'=>$row['category'],'position'=>(int)$row['position'],'visible'=>(int)$row['visible']]);
        uasort($result,static fn($a,$b)=>($a['position']<=>$b['position'])?:strcmp($a['key'],$b['key']));
        if(!$includeHidden)$result=array_filter($result,static fn($photo)=>$photo['visible']===1);
        return array_values($result);
    }
    public function upload(array $file,string $label,string $actor): void {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || ($file['size']??0)>8*1024*1024 || !is_uploaded_file($file['tmp_name']??'')) throw new BookingError('Choose a JPEG, PNG or WebP photo smaller than 8 MB.');
        $info=@getimagesize($file['tmp_name']);
        if(!$info || !in_array($info['mime'],['image/jpeg','image/png','image/webp'],true) || $info[0]*$info[1]>24000000) throw new BookingError('Use a valid photo up to 24 megapixels.');
        $image=match($info['mime']) {'image/jpeg'=>@imagecreatefromjpeg($file['tmp_name']),'image/png'=>@imagecreatefrompng($file['tmp_name']),'image/webp'=>@imagecreatefromwebp($file['tmp_name'])};
        if(!$image) throw new BookingError('The photo could not be read.');
        $dir=dirname((string)getenv('COSMOS_DB_PATH')).'/media';
        if(!is_dir($dir) || !is_writable($dir)) throw new BookingError('Photo storage is unavailable.',503);
        $filename=bin2hex(random_bytes(20)).'.webp';$path=$dir.'/'.$filename;
        try {
            if(!imagewebp($image,$path,85) || !chmod($path,0600)) throw new BookingError('The photo could not be saved.',503);
            $q=$this->app->db->prepare('INSERT INTO media(filename,mime,width,height,label) VALUES(?,?,?,?,?)');$q->execute([$filename,'image/webp',$info[0],$info[1],mb_substr(trim($label)?:'Uploaded photo',0,120)]);
            $q=$this->app->db->prepare("INSERT INTO audit_log(actor,action) VALUES(?,'photo_uploaded')");$q->execute([$actor]);
        }catch(\Throwable $e){if(is_file($path))unlink($path);throw $e;}finally{imagedestroy($image);}
    }
}

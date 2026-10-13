<?php
declare(strict_types=1);
namespace Cosmos;
if(isset($_SERVER['SCRIPT_FILENAME'])&&realpath((string)$_SERVER['SCRIPT_FILENAME'])===__FILE__){http_response_code(404);exit;}
final class Backup {
    public function __construct(private Booking $app){}
    public function directory(): string {
        $dir=realpath((string)getenv('COSMOS_BACKUP_DIR'));$project=realpath(dirname(__DIR__));$root=realpath($_SERVER['DOCUMENT_ROOT']??'')?:$project;
        if(!$dir||!is_writable($dir)||$dir===$project||str_starts_with($dir,$project.'/')||$dir===$root||str_starts_with($dir,$root.'/'))throw new BookingError('Private backup storage is unavailable.',503);
        return $dir;
    }
    public function create(string $actor): string {
        $dir=$this->directory();$name='cosmos-full-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));$dbfile=$dir.'/'.$name.'.sqlite';$archive=$dir.'/'.$name.'.tar';
        try {
            $this->app->db->exec('VACUUM main INTO '.$this->app->db->quote($dbfile));chmod($dbfile,0600);
            $db=new \PDO('sqlite:'.$dbfile);if($db->query('PRAGMA integrity_check')->fetchColumn()!=='ok')throw new BookingError('Backup verification failed.',503);
            $tar=new \PharData($archive);$tar->addFile($dbfile,'database.sqlite');
            $manifest=['createdAt'=>gmdate('c'),'database'=>hash_file('sha256',$dbfile),'media'=>[]];
            $mediaDir=dirname((string)getenv('COSMOS_DB_PATH')).'/media';
            foreach($db->query('SELECT filename FROM media') as $photo){$file=$mediaDir.'/'.$photo['filename'];if(!is_file($file))throw new BookingError('A photo is missing from backup.',503);$tar->addFile($file,'media/'.$photo['filename']);$manifest['media'][$photo['filename']]=hash_file('sha256',$file);}
            $tar->addFromString('manifest.json',json_encode($manifest,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));unset($tar);chmod($archive,0600);
            $q=$this->app->db->prepare("INSERT INTO settings(key,value) VALUES('last_backup_at',?) ON CONFLICT(key) DO UPDATE SET value=excluded.value");$q->execute([gmdate('Y-m-d H:i:s')]);
            $q=$this->app->db->prepare("INSERT INTO audit_log(actor,action) VALUES(?,'full_backup_created')");$q->execute([$actor]);
            return basename($archive);
        }catch(\Throwable $error){if(is_file($archive))unlink($archive);throw $error;}finally{if(is_file($dbfile))unlink($dbfile);}
    }
    public function files(): array {
        $files=glob($this->directory().'/cosmos-full-????????-??????-??????.tar')?:[];rsort($files);return $files;
    }
}

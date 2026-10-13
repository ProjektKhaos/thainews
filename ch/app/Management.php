<?php
declare(strict_types=1);
namespace Cosmos;
require_once __DIR__.'/Cms.php';require_once __DIR__.'/Auth.php';require_once __DIR__.'/Backup.php';
if(isset($_SERVER['SCRIPT_FILENAME'])&&realpath((string)$_SERVER['SCRIPT_FILENAME'])===__FILE__){http_response_code(404);exit;}
final class Management {
    public const APPROVALS=['house'=>'House information and photos','capacity'=>'Guest capacity','stay'=>'Minimum and maximum stay','prices'=>'Nightly and seasonal prices','discounts'=>'Discounts and combinations','fees'=>'Cleaning and other fees','checkin'=>'Check-in and check-out','rules'=>'House and cancellation rules','contact'=>'Address and contact details','privacy'=>'Privacy and retention','mail'=>'Email sender and delivery','backup'=>'Backup and recovery','release'=>'Public booking launch'];
    public function __construct(public Booking $app,public array $user){}
    public static function money(string $amount): int {
        $amount=trim($amount);
        if(!preg_match('/^(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?$/',$amount,$m))throw new BookingError('Enter an amount in THB, using a dot for decimals.');
        $value=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');if($value>1000000000)throw new BookingError('The amount is too large.');return $value;
    }
    public static function date(string $date): string {
        $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if(!$d||$d->format('Y-m-d')!==$date)throw new BookingError('Enter a valid date.');return $date;
    }
    public function query(string $sql,array $params=[]): array {$q=$this->app->db->prepare($sql);$q->execute($params);return $q->fetchAll();}
    private function audit(string $action): void {$q=$this->app->db->prepare('INSERT INTO audit_log(actor,action) VALUES(?,?)');$q->execute(['admin:'.$this->user['id'],$action]);}
    private function settings(array $values,string $audit): void {
        $db=$this->app->db;$db->exec('BEGIN IMMEDIATE');
        try{$q=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');foreach($values as $key=>$value)$q->execute([$key,(string)$value]);$this->audit($audit);$db->exec('COMMIT');}catch(\Throwable $e){$db->exec('ROLLBACK');throw $e;}
    }
    public function action(string $action,array $data,array $files): string {
        $actor='admin:'.$this->user['id'];$app=$this->app;$db=$app->db;
        $restricted=['settings','approvals','mail_settings','mail_test','user_save','backup','booking_gate'];
        if(in_array($action,$restricted,true)&&$this->user['role']!=='admin')throw new BookingError('Only an administrator can make this change.',403);
        switch($action) {
            case 'transition':
                $status=(string)($data['status']??'');$hours=max(1,min(168,(int)($data['hold_hours']??24)));
                $app->transition((int)($data['id']??0),$status,$actor,$status==='hold'?gmdate('Y-m-d H:i:s',time()+$hours*3600):null);break;
            case 'manual_booking':
                if(($data['amount_thb']??'')!=='')$data['amount_satang']=(string)self::money((string)$data['amount_thb']);
                return 'Booking '.$app->createBooking($data,$actor).' created.';
            case 'manual':$app->createManual((string)($data['check_in']??''),(string)($data['check_out']??''),(int)($data['guests']??0),(string)($data['name']??''),(string)($data['email']??''),$actor);break;
            case 'reschedule':$app->reschedule((int)($data['id']??0),(string)($data['check_in']??''),(string)($data['check_out']??''),(int)($data['guests']??0),$actor,($data['amount_thb']??'')!==''?self::money((string)$data['amount_thb']):null,(string)($data['price_reason']??''));break;
            case 'guest_details':$app->guestDetails((int)($data['id']??0),(string)($data['name']??''),(string)($data['email']??''),(string)($data['phone']??''),$actor);break;
            case 'block':$app->block((string)($data['check_in']??''),(string)($data['check_out']??''),(string)($data['reason']??''),$actor);break;
            case 'unblock':$app->unblock((int)($data['id']??0),$actor);break;
            case 'payment':$app->paymentStatus((int)($data['id']??0),(string)($data['status']??''),$actor);break;
            case 'note':$app->updateNote((int)($data['id']??0),(string)($data['note']??''),$actor);break;
            case 'anonymize':$app->anonymize((int)($data['id']??0),$actor);break;
            case 'setting':
                $key=(string)($data['key']??'');if(!in_array($key,['base_rate_satang','discount_stacking'],true)&&$this->user['role']!=='admin')throw new BookingError('Only an administrator can change house settings.',403);
                $app->updateSetting($key,(string)($data['value']??''),$actor);break;
            case 'base_rate':$app->updateSetting('base_rate_satang',(string)self::money((string)($data['amount_thb']??'')),$actor);$this->settings(['approved_prices'=>'1'],'base_price_reviewed');break;
            case 'monthly_rate':$app->setMonthlyRate(self::money((string)($data['amount_thb']??'')),(int)($data['reference_days']??30),$actor);break;
            case 'toggle_rule':$app->toggleRule((string)($data['type']??''),(int)($data['id']??0),($data['active']??'')==='1',$actor);break;
            case 'update_rule':
                $value=isset($data['amount_thb'])?self::money((string)$data['amount_thb']):(int)($data['value']??-1);
                $app->updateRuleValue((string)($data['type']??''),(int)($data['id']??0),(string)($data['label']??''),$value,(int)($data['priority']??0),$actor);break;
            case 'rate':case 'discount':case 'fee':$this->createRule($action,$data);break;
            case 'content':
                $scope=(string)($data['scope']??'landing');$fields=$data['content']??[];if(!is_array($fields))throw new BookingError('Invalid content.');
                (new Cms($app))->save($scope,$fields,$actor,($data['publish']??'')==='1');return ($data['publish']??'')==='1'?'Content published.':'Draft saved.';
            case 'upload':(new Cms($app))->upload($files['photo']??[],(string)($data['label']??''),$actor);return 'Photo uploaded. Select it in the landing page gallery to publish it.';
            case 'gallery_photo':
                $key=(string)($data['photo_key']??'');(new Cms($app))->image($key);$label=trim((string)($data['label']??''));$category=(string)($data['category']??'details');$position=(int)($data['position']??0);
                if(!$label||mb_strlen($label)>120||!in_array($category,['living','bedroom','terrace','details'],true)||$position<1||$position>10000)throw new BookingError('Check the photo caption, category and position.');
                $db->exec('BEGIN IMMEDIATE');try{$q=$db->prepare('INSERT INTO gallery_photos(photo_key,label,category,position,visible) VALUES(?,?,?,?,?) ON CONFLICT(photo_key) DO UPDATE SET label=excluded.label,category=excluded.category,position=excluded.position,visible=excluded.visible');$q->execute([$key,$label,$category,$position,isset($data['visible'])?1:0]);$this->audit('gallery_photo_published:'.$key);$db->exec('COMMIT');}catch(\Throwable $e){$db->exec('ROLLBACK');throw $e;}return 'Full gallery photo settings published.';
            case 'settings':
                $values=[];
                foreach(['max_guests'=>100,'included_guests'=>100,'min_nights'=>365,'max_nights'=>365]as $key=>$max){$value=(string)($data[$key]??'');if(!ctype_digit($value)||(int)$value<1||(int)$value>$max)throw new BookingError('Enter valid guest and stay limits.');$values[$key]=$value;}
                if((int)$values['included_guests']>(int)$values['max_guests']||(int)$values['min_nights']>(int)$values['max_nights'])throw new BookingError('Check the guest and stay limits.');
                foreach(['contact_name','contact_email','contact_phone','address','checkin_time','checkout_time','house_rules','cancellation_terms','payment_terms','privacy_text_en','privacy_text_sv','privacy_text_th','policy_version']as $key){$value=trim((string)($data[$key]??''));if(mb_strlen($value)>6000)throw new BookingError('The setting is too long.');$values[$key]=$value;}
                if($values['contact_email']!==''&&!filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL))throw new BookingError('Enter a valid contact email.');
                $days=(string)($data['backup_retention_days']??'30');if(!ctype_digit($days)||(int)$days<7||(int)$days>365)throw new BookingError('Choose backup retention between 7 and 365 days.');$values['backup_retention_days']=$days;
                $values['rule_version']=(string)((int)$app->setting('rule_version','1')+1);$this->settings($values,'house_settings_updated');break;
            case 'approvals':
                $values=[];foreach(self::APPROVALS as $key=>$label)$values['approved_'.$key]=isset($data['approved'][$key])?'1':'0';$this->settings($values,'launch_approvals_updated');break;
            case 'booking_gate':
                if(($data['enabled']??'')!=='1'){$this->settings(['booking_public_enabled'=>'0'],'public_booking_closed');break;}
                if(getenv('COSMOS_BOOKING_PUBLIC_ENABLED')!=='1')throw new BookingError('The server launch gate is still closed. Complete the launch review before opening public bookings.',409);
                $db->exec('BEGIN IMMEDIATE');try{$db->exec("UPDATE settings SET value='1' WHERE key='booking_public_enabled'");if(!$app->publicOpen())throw new BookingError('Complete all launch approvals, prices, privacy and email settings first.',409);$this->audit('public_booking_opened');$db->exec('COMMIT');}catch(\Throwable $e){$db->exec('ROLLBACK');throw $e;}break;
            case 'mail_settings':
                $mode=(string)($data['mail_mode']??'log');$host=trim((string)($data['smtp_host']??''));$port=(string)($data['smtp_port']??'587');$security=(string)($data['smtp_security']??'starttls');
                if(!in_array($mode,['log','smtp'],true)||($host!==''&&!preg_match('/^[a-zA-Z0-9.-]+$/',$host))||!ctype_digit($port)||(int)$port<1||(int)$port>65535||!in_array($security,['starttls','tls'],true))throw new BookingError('Check the mail server settings.');
                $values=['mail_mode'=>$mode,'smtp_host'=>$host,'smtp_port'=>$port,'smtp_security'=>$security,'mail_enabled'=>isset($data['mail_enabled'])?'1':'0'];
                foreach(['smtp_user','mail_from','admin_email']as $key){$value=trim((string)($data[$key]??''));if(strlen($value)>254||preg_match('/[\r\n\x00]/',$value))throw new BookingError('Invalid email settings.');if($key!=='smtp_user'&&$value!==''&&!filter_var($value,FILTER_VALIDATE_EMAIL))throw new BookingError('Enter valid sender and notification email addresses.');$values[$key]=$value;}
                if(($data['smtp_password']??'')!==''){$password=(string)$data['smtp_password'];if(strlen($password)>500||str_contains($password,"\0"))throw new BookingError('Invalid mail password.');$values['smtp_password']=$password;}
                if($values['mail_enabled']==='1'&&($mode!=='smtp'||!$host||!$values['smtp_user']||!($values['smtp_password']??$app->setting('smtp_password'))||!$values['mail_from']||!$values['admin_email']))throw new BookingError('Complete the SMTP settings before enabling real emails.');
                $this->settings($values,'mail_settings_updated');break;
            case 'mail_test':
                $mail=$app->mailConfig();if($mail['mode']!=='smtp'||!$mail['approved'])throw new BookingError('Configure SMTP and enable real emails before sending a test.');
                $q=$db->prepare("INSERT INTO notification_outbox(event_key,kind,language,payload_json) VALUES(?,'smtp_test','en',?)");$q->execute(['mail-test:'.bin2hex(random_bytes(12)),json_encode(['reference'=>'SMTP test','email'=>$mail['admin']],JSON_THROW_ON_ERROR)]);$this->audit('smtp_test_requested');return 'Test email queued. Check its delivery status after the next worker run.';
            case 'retry_mail':
                $q=$db->prepare("UPDATE notification_outbox SET status='pending',attempts=0,next_attempt_at=CURRENT_TIMESTAMP,last_error=NULL WHERE id=? AND status='failed'");$q->execute([(int)($data['id']??0)]);if($q->rowCount()!==1)throw new BookingError('Only failed emails can be retried.',409);$this->audit('mail_retry_requested');break;
            case 'user_save':$this->saveUser($data);break;
            case 'password':
                $q=$db->prepare('SELECT password_hash FROM admin_users WHERE id=?');$q->execute([$this->user['id']]);if(!password_verify((string)($data['current_password']??''),(string)$q->fetchColumn()))throw new BookingError('Your current password is incorrect.');
                $password=(string)($data['new_password']??'');Auth::validateCredentials($this->user['username'],$password);
                $q=$db->prepare('UPDATE admin_users SET password_hash=?,session_version=session_version+1 WHERE id=?');$q->execute([password_hash($password,PASSWORD_DEFAULT),$this->user['id']]);$_SESSION['cosmos_admin_version']++;session_regenerate_id(true);$this->audit('own_password_changed');break;
            case 'backup':$name=(new Backup($app))->create($actor);return 'Backup created and verified: '.$name;
            default:throw new BookingError('Unknown action.');
        }
        return 'Changes saved.';
    }
    private function saveUser(array $data): void {
        $id=(int)($data['id']??0);$username=trim((string)($data['username']??''));$role=(string)($data['role']??'staff');$active=isset($data['active'])?1:0;$password=(string)($data['password_new']??'');
        if(!in_array($role,['admin','staff'],true)||!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/',$username))throw new BookingError('Check the username and role.');
        if($password!==''||$id===0)Auth::validateCredentials($username,$password);
        $db=$this->app->db;$db->exec('BEGIN IMMEDIATE');
        try {
            if($id){$q=$db->prepare('SELECT * FROM admin_users WHERE id=?');$q->execute([$id]);$old=$q->fetch();if(!$old)throw new BookingError('Account not found.',404);
                if($old['role']==='admin'&&$old['active']&&(!$active||$role!=='admin')&&(int)$db->query("SELECT COUNT(*) FROM admin_users WHERE role='admin' AND active=1")->fetchColumn()<=1)throw new BookingError('Keep at least one active administrator.');
                if($id===(int)$this->user['id']&&(!$active||$role!=='admin'||$password!==''))throw new BookingError('Use My account to change your own password. Keep your current account active.');
                $q=$db->prepare('UPDATE admin_users SET username=?,role=?,active=?,session_version=session_version+1,password_hash=?,failed_count=0,locked_until=NULL WHERE id=?');$q->execute([$username,$role,$active,$password!==''?password_hash($password,PASSWORD_DEFAULT):$old['password_hash'],$id]);
                if($id===(int)$this->user['id'])$_SESSION['cosmos_admin_version']++;
            }else{$q=$db->prepare('INSERT INTO admin_users(username,password_hash,role,active,session_version) VALUES(?,?,?,?,?)');$q->execute([$username,password_hash($password,PASSWORD_DEFAULT),$role,$active,random_int(100000,PHP_INT_MAX-1000000)]);}
            $this->audit('staff_account_updated');$db->exec('COMMIT');
        }catch(\Throwable $e){$db->exec('ROLLBACK');if($e instanceof \PDOException&&str_contains($e->getMessage(),'UNIQUE'))throw new BookingError('That username is already in use.');throw $e;}
    }
    private function createRule(string $type,array $data): void {
        $label=trim((string)($data['label']??''));$priority=(int)($data['priority']??0);
        if(!$label||mb_strlen($label)>100||$priority< -1000||$priority>1000)throw new BookingError('Enter a name and valid priority.');
        $db=$this->app->db;$db->exec('BEGIN IMMEDIATE');
        try {
            $id=max(0,(int)($data['id']??0));
            $write=static function(string $table,array $columns,array $values)use($db,$id):void {
                if($id){$q=$db->prepare('UPDATE '.$table.' SET '.implode(',',array_map(static fn($column)=>$column.'=?',$columns)).' WHERE id=?');$q->execute([...$values,$id]);if($q->rowCount()!==1)throw new BookingError('Rule not found.',404);}
                else{$q=$db->prepare('INSERT INTO '.$table.'('.implode(',',$columns).',active) VALUES('.implode(',',array_fill(0,count($values),'?')).',1)');$q->execute($values);}
            };
            if($type==='rate') {
                $start=self::date((string)($data['start_date']??''));$end=self::date((string)($data['end_date']??''));$weekday=($data['weekday']??'')===''?null:(int)$data['weekday'];$amount=isset($data['amount_thb'])?self::money((string)$data['amount_thb']):(int)($data['amount_satang']??-1);
                if($end<=$start||($weekday!==null&&($weekday<1||$weekday>7))||$amount<0||$amount>1000000000)throw new BookingError('Check the rate period, weekday and amount.');
                $write('rate_rules',['label','start_date','end_date','weekday','amount_satang','priority'],[$label,$start,$end,$weekday,$amount,$priority]);
            }elseif($type==='discount') {
                $family=(string)($data['family']??'length');$kind=(string)($data['kind']??'percent');$min=(int)($data['min_nights']??1);$max=($data['max_nights']??'')===''?null:(int)$data['max_nights'];$value=isset($data['display_value'])?self::money((string)$data['display_value']):(int)($data['value']??-1);
                $code=strtoupper(trim((string)($data['code']??'')));$start=trim((string)($data['start_date']??''));$end=trim((string)($data['end_date']??''));$early=($data['min_advance_days']??'')===''?null:(int)$data['min_advance_days'];$last=($data['max_advance_days']??'')===''?null:(int)$data['max_advance_days'];
                if(!in_array($family,['length','early','last','code','promo'],true)||!in_array($kind,['percent','fixed'],true)||$min<1||$min>365||($max!==null&&($max<$min||$max>365))||$value<0||$value>1000000000||($kind==='percent'&&$value>10000)||($code!==''&&!preg_match('/^[A-Z0-9_-]{3,40}$/',$code))||($family==='code'&&$code==='')||($early!==null&&($early<0||$early>3650))||($last!==null&&($last<0||$last>3650))||($family==='early'&&$early===null)||($family==='last'&&$last===null)||($family==='promo'&&(!$start||!$end)))throw new BookingError('Check the discount amount, dates and booking window.');
                if($start!=='')self::date($start);if($end!=='')self::date($end);if($start&&$end&&$end<=$start)throw new BookingError('The discount end date must be after its start date.');
                $write('discount_rules',['label','family','min_nights','max_nights','kind','value','priority','code','min_advance_days','max_advance_days','start_date','end_date'],[$label,$family,$min,$max,$kind,$value,$priority,$code?:null,$early,$last,$start?:null,$end?:null]);
            }else {
                $kind=(string)($data['kind']??'fixed');$basis=(string)($data['basis']??'nights_after_discount');$amount=isset($data['amount_thb'])?self::money((string)$data['amount_thb']):(int)($data['amount_satang']??0);$percent=isset($data['percent'])?self::money((string)$data['percent']):(int)($data['percent_bps']??0);
                if(!in_array($kind,['fixed','percent'],true)||!in_array($basis,['nights_after_discount','subtotal_plus_fees'],true)||$amount<0||$amount>1000000000||$percent<0||$percent>10000||($kind==='percent'&&$percent===0))throw new BookingError('Check the fee amount or percentage.');
                $write('fee_rules',['label','kind','amount_satang','percent_bps','basis','per_night','per_extra_guest'],[$label,$kind,$kind==='fixed'?$amount:0,$kind==='percent'?$percent:0,$basis,$kind==='fixed'&&isset($data['per_night'])?1:0,$kind==='fixed'&&isset($data['per_extra_guest'])?1:0]);
            }
            $db->exec("UPDATE settings SET value=CAST(value AS INTEGER)+1 WHERE key='rule_version'");$this->audit('rule_'.$type.($id?'_updated':'_created'));$db->exec('COMMIT');
        }catch(\Throwable $e){$db->exec('ROLLBACK');throw $e;}
    }
}

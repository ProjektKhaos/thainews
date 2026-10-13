<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);

namespace Cosmos;

require_once __DIR__.'/Runtime.php';

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME'])===__FILE__) { http_response_code(404); exit; }

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;

final class BookingError extends RuntimeException {
    public function __construct(string $message, public readonly int $httpStatus = 422) { parent::__construct($message); }
}

final class Booking {
    private const APPROVALS = ['house', 'capacity', 'stay', 'prices', 'discounts', 'fees', 'checkin', 'rules', 'contact', 'privacy', 'mail', 'backup', 'release'];
    public function __construct(public readonly PDO $db) {}

    public static function connect(): self {
        $path = getenv('COSMOS_DB_PATH') ?: '';
        if ($path === '' || $path[0] !== '/' || str_contains($path, "\0")) throw new BookingError('Bokningssystemet är inte konfigurerat.', 503);
        $dir = realpath(dirname($path));
        $project = realpath(dirname(__DIR__));
        $root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: $project;
        if ($dir === false || !is_dir($dir) || !is_writable($dir)) throw new BookingError('Privat lagring är inte tillgänglig.', 503);
        foreach ([$project, $root] as $public) if ($dir === $public || str_starts_with($dir, $public . '/')) throw new BookingError('Privat lagring är felkonfigurerad.', 503);
        if (!is_file($path)) throw new BookingError('Bokningsdatabasen saknas.', 503);
        $realFile=realpath($path);
        foreach ([$project,$root] as $public) if ($realFile===false || $realFile===$public || str_starts_with($realFile,$public.'/')) throw new BookingError('Privat lagring är felkonfigurerad.',503);
        if(is_file($dir.'/.maintenance'))throw new BookingError('Administration is undergoing maintenance.',503);
        static $locks=[];
        if(!isset($locks[$dir])){
            $lock=fopen($dir.'/.runtime.lock','c');
            if(!$lock||!flock($lock,LOCK_SH))throw new BookingError('Private storage is unavailable.',503);
            chmod($dir.'/.runtime.lock',0600);$locks[$dir]=$lock;
        }
        if(is_file($dir.'/.maintenance'))throw new BookingError('Administration is undergoing maintenance.',503);
        $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000');
        return new self($db);
    }

    public function setting(string $key, ?string $default = null): ?string {
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE key=?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string)$value;
    }

    public function mailConfig(): array {
        $map=['mode'=>'COSMOS_MAIL_MODE','host'=>'COSMOS_SMTP_HOST','port'=>'COSMOS_SMTP_PORT','user'=>'COSMOS_SMTP_USER','password'=>'COSMOS_SMTP_PASSWORD','from'=>'COSMOS_MAIL_FROM','admin'=>'COSMOS_ADMIN_EMAIL'];
        $keys=['mode'=>'mail_mode','host'=>'smtp_host','port'=>'smtp_port','user'=>'smtp_user','password'=>'smtp_password','from'=>'mail_from','admin'=>'admin_email'];
        $config=[];
        foreach ($map as $key=>$env) $config[$key]=getenv($env)!==false?(string)getenv($env):(string)$this->setting($keys[$key],$key==='mode'?'log':($key==='port'?'587':''));
        $config['security']=(string)$this->setting('smtp_security','starttls');
        $config['approved']=getenv('COSMOS_MAIL_APPROVED')==='1' || $this->setting('mail_enabled')==='1';
        return $config;
    }

    public function approved(): bool {
        foreach (self::APPROVALS as $name) if ($this->setting('approved_' . $name) !== '1') return false;
        return true;
    }

    public function publicOpen(): bool {
        $rate=$this->setting('base_rate_satang');$config=$this->configuration();
        if (getenv('COSMOS_BOOKING_PUBLIC_ENABLED') !== '1' || $this->setting('booking_public_enabled') !== '1' || !$this->approved() || $rate===null || !ctype_digit($rate) || (int)$rate<1 || $config['maxGuests']<1 || $config['minNights']<1 || $config['maxNights']<$config['minNights'] || !$this->setting('policy_version')) return false;
        $mail=$this->mailConfig();
        if ($mail['mode']!=='smtp' || !$mail['approved'] || !$mail['host'] || !$mail['user'] || !$mail['password']) return false;
        if (!filter_var($mail['admin'],FILTER_VALIDATE_EMAIL) || !filter_var($mail['from'],FILTER_VALIDATE_EMAIL)) return false;
        $languages=$this->setting('public_language')==='en'?['en']:['en','sv','th'];
        foreach ($languages as $lang) if (!$this->setting('privacy_text_'.$lang)) return false;
        return true;
    }

    public function configuration(): array {
        return ['maxGuests' => (int)$this->setting('max_guests', '0'), 'minNights' => (int)$this->setting('min_nights', '1'), 'maxNights' => (int)$this->setting('max_nights', '90'), 'currency' => 'THB', 'timezone' => 'Asia/Bangkok'];
    }

    private function date(string $value): DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Bangkok'));
        if (!$date || $date->format('Y-m-d') !== $value) throw new BookingError('Ogiltigt datum.');
        return $date;
    }

    public function validateStay(string $start, string $end, int $guests): int {
        $a = $this->date($start); $b = $this->date($end);
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Bangkok'));
        $nights = (int)$a->diff($b)->format('%r%a');
        $c = $this->configuration();
        if ($a < $today || $nights < $c['minNights'] || $nights > $c['maxNights']) throw new BookingError('Välj giltiga datum och vistelselängd.');
        if ($guests < 1 || $guests > $c['maxGuests']) throw new BookingError('Ogiltigt antal gäster.');
        return $nights;
    }

    // Halvöppna intervall tillåter att nästa gäst checkar in samma dag som föregående checkar ut.
    public function conflicts(string $start, string $end, ?int $ignoreBooking = null): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM bookings WHERE status IN ('confirmed','hold') AND (status != 'hold' OR expires_at > datetime('now')) AND check_in < ? AND check_out > ? AND id != ? LIMIT 1");
        $stmt->execute([$end, $start, $ignoreBooking ?? -1]);
        if ($stmt->fetchColumn()) return true;
        $stmt = $this->db->prepare('SELECT 1 FROM availability_blocks WHERE active=1 AND check_in < ? AND check_out > ? LIMIT 1');
        $stmt->execute([$end, $start]);
        return (bool)$stmt->fetchColumn();
    }

    public function availability(string $from, string $to): array {
        $a = $this->date($from); $b = $this->date($to);
        if ($b <= $a || (int)$a->diff($b)->days > 366) throw new BookingError('Ogiltigt datumintervall.');
        $blocked = [];
        for ($day = $a; $day < $b; $day = $day->modify('+1 day')) {
            $next = $day->modify('+1 day');
            if ($this->conflicts($day->format('Y-m-d'), $next->format('Y-m-d'))) $blocked[] = $day->format('Y-m-d');
        }
        return ['blocked' => $blocked, 'from' => $from, 'to' => $to] + $this->configuration();
    }

    private static function percent(int $amount, int $basisPoints): int { return intdiv($amount * $basisPoints + 5000, 10000); }

    public function price(string $start, string $end, int $guests, ?string $code = null, ?int $ignoreBooking = null): array {
        $nights = $this->validateStay($start, $end, $guests);
        if ($this->conflicts($start, $end, $ignoreBooking)) throw new BookingError('Datumen är inte tillgängliga.', 409);
        $base = $this->setting('base_rate_satang');
        if ($base === null || !ctype_digit($base) || (int)$base <= 0 || $this->setting('approved_prices') !== '1') throw new BookingError('Priser är inte godkända.', 503);
        $monthlyMode=$this->setting('base_rate_mode','nightly')==='monthly';
        $monthly=(int)$this->setting('base_rate_monthly_satang','0');
        $referenceDays=(int)$this->setting('base_rate_reference_days','30');
        if($monthlyMode&&($referenceDays<28||$referenceDays>31||$monthly<$referenceDays||$monthly>1000000000))throw new BookingError('The monthly reference price is not configured.',503);
        $baseNightCount=0;$baseAllocated=0;
        $rules = $this->db->query('SELECT * FROM rate_rules WHERE active=1 ORDER BY priority DESC, id ASC')->fetchAll();
        $rows = []; $subtotal = 0;
        $day = $this->date($start); $finish = $this->date($end);
        while ($day < $finish) {
            $date = $day->format('Y-m-d'); $weekday = (int)$day->format('N'); $rate = (int)$base; $ruleId = null;
            foreach ($rules as $rule) {
                if ($date < $rule['start_date'] || $date >= $rule['end_date']) continue;
                if ($rule['weekday'] !== null && (int)$rule['weekday'] !== $weekday) continue;
                $rate = (int)$rule['amount_satang']; $ruleId = (int)$rule['id']; break;
            }
            if($monthlyMode&&$ruleId===null){
                // Round cumulative amounts so a complete reference period matches its monthly price exactly.
                $baseNightCount++;
                $cumulative=intdiv($monthly*$baseNightCount+intdiv($referenceDays,2),$referenceDays);
                $rate=$cumulative-$baseAllocated;$baseAllocated=$cumulative;
            }
            $rows[] = ['date' => $date, 'amountSatang' => $rate, 'ruleId' => $ruleId];
            $subtotal += $rate; $day = $day->modify('+1 day');
        }
        $discounts = $this->db->query('SELECT * FROM discount_rules WHERE active=1 ORDER BY priority DESC, id ASC')->fetchAll();
        $chosen = []; $discountTotal = 0;
        $advance=(int)(new DateTimeImmutable('today',new DateTimeZone('Asia/Bangkok')))->diff($this->date($start))->format('%r%a');
        $stacking=$this->setting('discount_stacking','0')==='1';
        foreach ($discounts as $rule) {
            if ($nights < (int)$rule['min_nights'] || ($rule['max_nights'] !== null && $nights > (int)$rule['max_nights'])) continue;
            if ($rule['start_date'] !== null && $start < $rule['start_date']) continue;
            if ($rule['end_date'] !== null && $start >= $rule['end_date']) continue;
            if ($rule['min_advance_days'] !== null && $advance < (int)$rule['min_advance_days']) continue;
            if ($rule['max_advance_days'] !== null && $advance > (int)$rule['max_advance_days']) continue;
            if ($rule['code'] !== null && (!is_string($code) || !hash_equals($rule['code'], strtoupper(trim($code))))) continue;
            $family = $rule['family'];
            if (isset($chosen[$family]) || (!$stacking && $chosen!==[])) continue;
            $value = $rule['kind'] === 'percent' ? self::percent($subtotal, (int)$rule['value']) : (int)$rule['value'];
            $value = min($value, $subtotal - $discountTotal);
            $chosen[$family] = ['ruleId' => (int)$rule['id'], 'label'=>$rule['label'], 'amountSatang' => $value]; $discountTotal += $value;
        }
        $fees = []; $feeTotal = 0; $allFees=$this->db->query('SELECT * FROM fee_rules WHERE active=1 ORDER BY id')->fetchAll();
        foreach ($allFees as $fee) {
            if ($fee['kind']!=='fixed') continue;
            $amount = (int)$fee['amount_satang'];
            if ($fee['per_night']) $amount *= $nights;
            if ($fee['per_extra_guest']) $amount *= max(0, $guests - (int)$this->setting('included_guests', '1'));
            $fees[] = ['ruleId' => (int)$fee['id'], 'label' => $fee['label'], 'amountSatang' => $amount]; $feeTotal += $amount;
        }
        $fixedFeeTotal=$feeTotal;
        foreach ($allFees as $fee) {
            if ($fee['kind']!=='percent') continue;
            $basis=$subtotal-$discountTotal+($fee['basis']==='subtotal_plus_fees'?$fixedFeeTotal:0);
            $amount=self::percent($basis,(int)$fee['percent_bps']);
            $fees[]=['ruleId'=>(int)$fee['id'],'label'=>$fee['label'],'amountSatang'=>$amount,'basis'=>$fee['basis']];$feeTotal+=$amount;
        }
        $result=['currency' => 'THB', 'nights' => $rows, 'nightSubtotalSatang' => $subtotal, 'discounts' => array_values($chosen), 'fees' => $fees, 'totalSatang' => $subtotal - $discountTotal + $feeTotal, 'ruleVersion' => (int)$this->setting('rule_version', '1')];
        if($monthlyMode)$result['monthlyReference']=['amountSatang'=>$monthly,'days'=>$referenceDays,'baseNights'=>$baseNightCount,'baseSubtotalSatang'=>$baseAllocated];
        return $result;
    }

    public function quote(string $start, string $end, int $guests, ?string $code = null): array {
        $price = $this->price($start, $end, $guests, $code);
        $token = bin2hex(random_bytes(24));
        $expiry = gmdate('Y-m-d H:i:s', time() + 900);
        $stmt = $this->db->prepare('INSERT INTO quotes(token,check_in,check_out,guests,code,snapshot_json,total_satang,rule_version,expires_at) VALUES(?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$token, $start, $end, $guests, $code === null ? null : strtoupper(trim($code)), json_encode($price, JSON_THROW_ON_ERROR), $price['totalSatang'], $price['ruleVersion'], $expiry]);
        return ['quoteToken' => $token, 'expiresAt' => $expiry . ' UTC', 'price' => $price];
    }

    private function audit(?int $bookingId, string $actor, string $action, ?string $from, ?string $to): void {
        $stmt = $this->db->prepare('INSERT INTO audit_log(booking_id,actor,action,status_from,status_to) VALUES(?,?,?,?,?)');
        $stmt->execute([$bookingId, $actor, $action, $from, $to]);
    }

    private function enqueue(string $eventKey, string $kind, string $language, array $payload): void {
        $stmt = $this->db->prepare('INSERT OR IGNORE INTO notification_outbox(event_key,kind,language,payload_json) VALUES(?,?,?,?)');
        $stmt->execute([$eventKey, $kind, $language, json_encode($payload, JSON_THROW_ON_ERROR)]);
    }

    public function inquire(array $data): array {
        if (!$this->publicOpen()) throw new BookingError('Bokningsförfrågningar är inte öppna ännu.', 503);
        $key = (string)($data['idempotencyKey'] ?? ''); $token = (string)($data['quoteToken'] ?? '');
        if (!preg_match('/^[a-f0-9]{32,64}$/', $key) || !preg_match('/^[a-f0-9]{48}$/', $token)) throw new BookingError('Ogiltig förfrågan.');
        $name = trim((string)($data['name'] ?? '')); $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? '')); $message = trim((string)($data['message'] ?? ''));
        $language = (string)($data['language'] ?? 'en');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || mb_strlen($phone) > 40 || mb_strlen($message) > 1500 || !in_array($language, ['en','sv','th'], true)) throw new BookingError('Kontrollera kontaktuppgifterna.');
        if (($data['privacy'] ?? false) !== true) throw new BookingError('Läs integritetsinformationen innan du skickar.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt = $this->db->prepare('SELECT reference,status,quote_id,name,email,phone,message,language FROM bookings WHERE idempotency_key=?'); $stmt->execute([$key]);
            if ($existing = $stmt->fetch()) {
                $quoteStmt = $this->db->prepare('SELECT token FROM quotes WHERE id=?'); $quoteStmt->execute([$existing['quote_id']]);
                $sameRequest = hash_equals((string)$quoteStmt->fetchColumn(), $token)
                    && $existing['name'] === $name && $existing['email'] === $email
                    && $existing['phone'] === $phone && $existing['message'] === $message
                    && $existing['language'] === $language;
                if (!$sameRequest) throw new BookingError('Förfrågningsnyckeln används redan för andra uppgifter.', 409);
                $this->db->exec('COMMIT'); return ['reference' => $existing['reference'], 'status' => $existing['status']];
            }
            $stmt = $this->db->prepare('SELECT * FROM quotes WHERE token=?'); $stmt->execute([$token]); $quote = $stmt->fetch();
            if (!$quote || $quote['expires_at'] <= gmdate('Y-m-d H:i:s')) throw new BookingError('Offerten har gått ut. Hämta ett nytt pris.', 410);
            if ((int)$quote['rule_version'] !== (int)$this->setting('rule_version', '1')) throw new BookingError('Priset har ändrats. Hämta ett nytt pris.', 409);
            $price = $this->price($quote['check_in'], $quote['check_out'], (int)$quote['guests'], $quote['code']);
            if ($price['totalSatang'] !== (int)$quote['total_satang']) throw new BookingError('Priset har ändrats. Hämta ett nytt pris.', 409);
            $reference = strtoupper(bin2hex(random_bytes(8)));
            $stmt = $this->db->prepare("INSERT INTO bookings(reference,idempotency_key,quote_id,name,email,phone,message,language,check_in,check_out,guests,status,payment_status,total_satang,price_snapshot_json,policy_version,consent_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,'inquiry','unpaid',?,?,?,CURRENT_TIMESTAMP)");
            $stmt->execute([$reference,$key,$quote['id'],$name,$email,$phone,$message,$language,$quote['check_in'],$quote['check_out'],$quote['guests'],$price['totalSatang'],$quote['snapshot_json'],$this->setting('policy_version')]);
            $id = (int)$this->db->lastInsertId(); $this->audit($id, 'guest', 'inquiry_created', null, 'inquiry');
            $this->enqueue('booking:' . $id . ':inquiry:guest', 'inquiry_guest', $language, ['reference'=>$reference,'email'=>$email,'name'=>$name,'checkIn'=>$quote['check_in'],'checkOut'=>$quote['check_out']]);
            $this->enqueue('booking:' . $id . ':inquiry:admin', 'inquiry_admin', 'en', ['reference'=>$reference]);
            $this->db->exec('COMMIT'); return ['reference'=>$reference,'status'=>'inquiry'];
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function createManual(string $start, string $end, int $guests, string $name, string $email, string $actor): string {
        if (mb_strlen(trim($name)) < 2 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new BookingError('Ange namn och giltig e-post.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            // Pris och konflikt kontrolleras under samma skrivtransaktion som det nya ärendet.
            $price=$this->price($start,$end,$guests);
            if ($this->conflicts($start,$end)) throw new BookingError('Datumen är upptagna.',409);
            $reference='TEST-'.strtoupper(bin2hex(random_bytes(6)));
            $stmt=$this->db->prepare("INSERT INTO bookings(reference,idempotency_key,name,email,language,check_in,check_out,guests,status,total_satang,price_snapshot_json,internal_note) VALUES(?,? ,? ,? ,'sv',?,?,?,'inquiry',?,?,'Intern testbokning')");
            $stmt->execute([$reference,bin2hex(random_bytes(16)),$name,$email,$start,$end,$guests,$price['totalSatang'],json_encode($price,JSON_THROW_ON_ERROR)]);
            $this->audit((int)$this->db->lastInsertId(),$actor,'manual_test_created',null,'inquiry');
            $this->db->exec('COMMIT'); return $reference;
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function createBooking(array $data,string $actor): string {
        $name=trim((string)($data['name']??''));$email=trim((string)($data['email']??''));$phone=trim((string)($data['phone']??''));
        $start=(string)($data['check_in']??'');$end=(string)($data['check_out']??'');$guests=(int)($data['guests']??0);$status=(string)($data['status']??'inquiry');
        if(mb_strlen($name)<2||mb_strlen($name)>120||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||strlen($email)>254||mb_strlen($phone)>40||!in_array($status,['inquiry','hold','confirmed'],true))throw new BookingError('Check the guest details and booking status.');
        $this->validateStay($start,$end,$guests);
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            if($this->conflicts($start,$end))throw new BookingError('The selected dates are unavailable.',409);
            if(($data['amount_satang']??'')!=='') {
                $amount=(string)$data['amount_satang'];$reason=trim((string)($data['price_reason']??''));
                if(!ctype_digit($amount)||(int)$amount>1000000000||mb_strlen($reason)<3||mb_strlen($reason)>300)throw new BookingError('Enter a valid agreed amount and the reason for the manual price.');
                $price=['currency'=>'THB','totalSatang'=>(int)$amount,'manual'=>true,'reason'=>$reason,'ruleVersion'=>(int)$this->setting('rule_version','1')];
            } else $price=$this->price($start,$end,$guests);
            $expiry=$status==='hold'?gmdate('Y-m-d H:i:s',time()+max(1,min(168,(int)($data['hold_hours']??24)))*3600):null;
            $reference='CH-'.strtoupper(bin2hex(random_bytes(6)));
            $q=$this->db->prepare('INSERT INTO bookings(reference,idempotency_key,name,email,phone,language,check_in,check_out,guests,status,expires_at,total_satang,price_snapshot_json,internal_note) VALUES(?,?,?,?,?,\'en\',?,?,?,?,?,?,?,?)');
            $q->execute([$reference,bin2hex(random_bytes(16)),$name,$email,$phone,$start,$end,$guests,$status,$expiry,$price['totalSatang'],json_encode($price,JSON_THROW_ON_ERROR),mb_substr(trim((string)($data['note']??'')),0,2000)]);
            $id=(int)$this->db->lastInsertId();$this->audit($id,$actor,'manual_booking_created',null,$status);
            if(!empty($data['notify'])&&$email!==''&&$status==='confirmed')$this->enqueue('booking:'.$id.':confirmed:guest','status_confirmed','en',['reference'=>$reference,'email'=>$email,'name'=>$name,'checkIn'=>$start,'checkOut'=>$end]);
            $this->db->exec('COMMIT');return $reference;
        }catch(\Throwable $error){try{$this->db->exec('ROLLBACK');}catch(\Throwable $ignored){}throw $error;}
    }

    public function transition(int $id, string $status, string $actor, ?string $expiresAt = null): void {
        $allowed = ['inquiry'=>['hold','confirmed','rejected'], 'hold'=>['confirmed','expired','cancelled'], 'confirmed'=>['cancelled']];
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('SELECT * FROM bookings WHERE id=?'); $stmt->execute([$id]); $booking=$stmt->fetch();
            if (!$booking || !in_array($status, $allowed[$booking['status']] ?? [], true)) throw new BookingError('Statusövergången är inte tillåten.',409);
            if ($booking['status']==='hold' && $booking['expires_at']<=gmdate('Y-m-d H:i:s') && $status!=='expired') throw new BookingError('Reservationen har gått ut.',409);
            if (in_array($status,['hold','confirmed'],true) && $this->conflicts($booking['check_in'],$booking['check_out'],$id)) throw new BookingError('Datumen är upptagna.',409);
            if ($status==='hold' && (!$expiresAt || strtotime($expiresAt) <= time())) throw new BookingError('Ange giltig sluttid för reservation.');
            $stmt=$this->db->prepare('UPDATE bookings SET status=?,expires_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $stmt->execute([$status,$status==='hold'?$expiresAt:null,$id]);
            $this->audit($id,$actor,'status_changed',$booking['status'],$status);
            // Interna testärenden får aldrig skapa externa gästsmejl, även efter en framtida SMTP-aktivering.
            if (!str_starts_with($booking['reference'],'TEST-') && in_array($status,['confirmed','rejected','cancelled'],true)) $this->enqueue('booking:'.$id.':'.$status.':guest','status_'.$status,$booking['language'],['reference'=>$booking['reference'],'email'=>$booking['email'],'name'=>$booking['name']]);
            $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function reschedule(int $id, string $start, string $end, int $guests, string $actor, ?int $manualTotal=null, string $reason=''): void {
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('SELECT * FROM bookings WHERE id=?'); $stmt->execute([$id]); $old=$stmt->fetch();
            if (!$old || !in_array($old['status'],['inquiry','hold','confirmed'],true)) throw new BookingError('Bokningen kan inte ändras.',409);
            if($manualTotal!==null){
                $this->validateStay($start,$end,$guests);
                if($manualTotal<0||$manualTotal>1000000000||mb_strlen(trim($reason))<3||mb_strlen($reason)>300)throw new BookingError('Enter a valid agreed amount and reason.');
                if($this->conflicts($start,$end,$id))throw new BookingError('The selected dates are unavailable.',409);
                $price=['currency'=>'THB','manual'=>true,'reason'=>$reason,'totalSatang'=>$manualTotal,'ruleVersion'=>(int)$this->setting('rule_version','1')];
            }else $price=$this->price($start,$end,$guests,null,$id);
            $snapshot=json_encode($price,JSON_THROW_ON_ERROR);
            $stmt=$this->db->prepare('UPDATE bookings SET check_in=?,check_out=?,guests=?,total_satang=?,price_snapshot_json=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $stmt->execute([$start,$end,$guests,$price['totalSatang'],$snapshot,$id]);
            $stmt=$this->db->prepare('INSERT INTO booking_revisions(booking_id,actor,before_json,after_json) VALUES(?,?,?,?)');
            $stmt->execute([$id,$actor,json_encode(['checkIn'=>$old['check_in'],'checkOut'=>$old['check_out'],'guests'=>$old['guests'],'totalSatang'=>$old['total_satang'],'price'=>$old['price_snapshot_json']],JSON_THROW_ON_ERROR),json_encode(['checkIn'=>$start,'checkOut'=>$end,'guests'=>$guests,'totalSatang'=>$price['totalSatang'],'price'=>$snapshot],JSON_THROW_ON_ERROR)]);
            $this->audit($id,$actor,'rescheduled',$old['status'],$old['status']);
            if (!str_starts_with($old['reference'],'TEST-')) $this->enqueue('booking:'.$id.':rescheduled:'.time(),'status_rescheduled',$old['language'],['reference'=>$old['reference'],'email'=>$old['email'],'name'=>$old['name'],'checkIn'=>$start,'checkOut'=>$end]);
            $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function paymentStatus(int $id, string $status, string $actor): void {
        if (!in_array($status,['unpaid','payment_instructions_sent','paid_manual','refunded_manual'],true)) throw new BookingError('Ogiltig betalstatus.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('SELECT payment_status FROM bookings WHERE id=?'); $stmt->execute([$id]); $old=$stmt->fetchColumn();
            if ($old===false) throw new BookingError('Bokningen saknas.',404);
            $stmt=$this->db->prepare('UPDATE bookings SET payment_status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?'); $stmt->execute([$status,$id]);
            $this->audit($id,$actor,'payment_status',(string)$old,$status); $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function guestDetails(int $id,string $name,string $email,string $phone,string $actor): void {
        $name=trim($name);$email=trim($email);$phone=trim($phone);
        if(mb_strlen($name)<2||mb_strlen($name)>120||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||strlen($email)>254||mb_strlen($phone)>40)throw new BookingError('Check the guest contact details.');
        $this->db->exec('BEGIN IMMEDIATE');
        try{
            $q=$this->db->prepare('SELECT status FROM bookings WHERE id=?');$q->execute([$id]);$status=$q->fetchColumn();
            if(!in_array($status,['inquiry','hold','confirmed'],true))throw new BookingError('Only active booking contact details can be changed.',409);
            $q=$this->db->prepare('UPDATE bookings SET name=?,email=?,phone=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$q->execute([$name,$email,$phone,$id]);
            // Pending notifications follow the corrected recipient; sent history remains intact.
            $q=$this->db->prepare("SELECT id,payload_json FROM notification_outbox WHERE event_key LIKE ? AND status IN ('pending','failed')");$q->execute(['booking:'.$id.':%']);
            foreach($q->fetchAll()as $event){$payload=json_decode($event['payload_json'],true);if(isset($payload['email'])){$payload['email']=$email;$payload['name']=$name;$update=$this->db->prepare('UPDATE notification_outbox SET payload_json=? WHERE id=?');$update->execute([json_encode($payload,JSON_THROW_ON_ERROR),$event['id']]);}}
            $this->audit($id,$actor,'guest_contact_updated',(string)$status,(string)$status);$this->db->exec('COMMIT');
        }catch(\Throwable $e){try{$this->db->exec('ROLLBACK');}catch(\Throwable $ignored){}throw $e;}
    }

    public function updateNote(int $id, string $note, string $actor): void {
        $note=trim($note);
        if ($id<1 || mb_strlen($note)>2000) throw new BookingError('Ogiltig intern notering.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('SELECT status FROM bookings WHERE id=?');$stmt->execute([$id]);$status=$stmt->fetchColumn();
            if ($status===false) throw new BookingError('Bokningen saknas.',404);
            $stmt=$this->db->prepare('UPDATE bookings SET internal_note=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$stmt->execute([$note,$id]);
            $this->audit($id,$actor,'internal_note_updated',(string)$status,(string)$status);
            $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try {$this->db->exec('ROLLBACK');} catch (\Throwable $ignored) {} throw $error; }
    }

    public function toggleRule(string $type, int $id, bool $active, string $actor): void {
        $table=['rate'=>'rate_rules','discount'=>'discount_rules','fee'=>'fee_rules'][$type]??null;
        if ($table===null) throw new BookingError('Ogiltig regel.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('UPDATE '.$table.' SET active=? WHERE id=?'); $stmt->execute([$active?1:0,$id]);
            if ($stmt->rowCount()!==1) throw new BookingError('Regeln saknas.',404);
            $this->db->exec("UPDATE settings SET value=CAST(value AS INTEGER)+1 WHERE key='rule_version'");
            $this->audit(null,$actor,'rule_'.$type.'_'.($active?'enabled':'disabled'),null,null); $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function updateRuleValue(string $type, int $id, string $label, int $value, int $priority, string $actor): void {
        $table=['rate'=>'rate_rules','discount'=>'discount_rules','fee'=>'fee_rules'][$type]??null;
        $column=$type==='discount'?'value':'amount_satang';
        if ($table===null || $id<1 || trim($label)==='' || mb_strlen($label)>100 || $value<0 || $value>($type==='discount'?100000000000:1000000000) || $priority < -1000 || $priority>1000) throw new BookingError('Ogiltig regeländring.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            if ($type==='discount') { $stmt=$this->db->prepare('SELECT kind FROM discount_rules WHERE id=?');$stmt->execute([$id]);$kind=$stmt->fetchColumn();if($kind==='percent' && $value>10000)throw new BookingError('Procenten är för hög.'); }
            if ($type==='fee') { $stmt=$this->db->prepare('SELECT kind FROM fee_rules WHERE id=?');$stmt->execute([$id]);$kind=$stmt->fetchColumn();if($kind==='percent'){if($value>10000)throw new BookingError('Procenten är för hög.');$column='percent_bps';} }
            $sql='UPDATE '.$table.' SET label=?,'.$column.'=?'.($type==='fee'?'':',priority=?').' WHERE id=?';
            $stmt=$this->db->prepare($sql);$params=[$label,$value];if($type!=='fee')$params[]=$priority;$params[]=$id;$stmt->execute($params);
            if($stmt->rowCount()!==1)throw new BookingError('Regeln saknas eller är oförändrad.',404);
            $this->db->exec("UPDATE settings SET value=CAST(value AS INTEGER)+1 WHERE key='rule_version'");
            $this->audit(null,$actor,'rule_'.$type.'_updated',null,null);$this->db->exec('COMMIT');
        } catch(\Throwable $error) { try{$this->db->exec('ROLLBACK');}catch(\Throwable $ignored){}throw $error; }
    }

    public function anonymize(int $id, string $actor): void {
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('SELECT status FROM bookings WHERE id=?'); $stmt->execute([$id]); $status=$stmt->fetchColumn();
            if (!in_array($status,['rejected','cancelled','expired'],true)) throw new BookingError('Aktiva ärenden kan inte anonymiseras.',409);
            $stmt=$this->db->prepare("UPDATE bookings SET name='[anonymized]',email='anonymous@example.invalid',phone='',message='',internal_note='',updated_at=CURRENT_TIMESTAMP WHERE id=?"); $stmt->execute([$id]);
            $stmt=$this->db->prepare("UPDATE notification_outbox SET payload_json='{}',status='simulated',last_error=NULL WHERE event_key LIKE ?"); $stmt->execute(['booking:'.$id.':%']);
            $this->audit($id,$actor,'personal_data_anonymized',(string)$status,(string)$status); $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function block(string $start, string $end, string $reason, string $actor): void {
        $this->date($start); $this->date($end);
        if ($end <= $start) throw new BookingError('Ogiltig period.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            if ($this->conflicts($start,$end)) throw new BookingError('Datumen är upptagna.',409);
            $stmt=$this->db->prepare('INSERT INTO availability_blocks(check_in,check_out,reason) VALUES(?,?,?)'); $stmt->execute([$start,$end,mb_substr($reason,0,200)]);
            $this->audit(null,$actor,'block_created',null,null); $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function unblock(int $id, string $actor): void {
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('UPDATE availability_blocks SET active=0 WHERE id=? AND active=1'); $stmt->execute([$id]);
            if ($stmt->rowCount()!==1) throw new BookingError('Blockeringen saknas.',404);
            $this->audit(null,$actor,'block_removed',null,null); $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }

    public function updateSetting(string $key, string $value, string $actor): void {
        $allowed = ['base_rate_satang','max_guests','min_nights','max_nights','included_guests','discount_stacking'];
        if (!in_array($key,$allowed,true) || !ctype_digit($value) || ($key==='discount_stacking'?!in_array($value,['0','1'],true):((int)$value<1 || (int)$value>($key==='base_rate_satang'?1000000000:($key==='max_nights'?365:1000))))) throw new BookingError('Ogiltig inställning.');
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$this->db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value'); $stmt->execute([$key,$value]);
            if($key==='base_rate_satang')$stmt->execute(['base_rate_mode','nightly']);
            $this->db->exec("UPDATE settings SET value=CAST(value AS INTEGER)+1 WHERE key='rule_version'");
            $this->audit(null,$actor,'setting_'.$key,null,null); $this->db->exec('COMMIT');
        } catch (\Throwable $error) { try { $this->db->exec('ROLLBACK'); } catch (\Throwable $ignored) {} throw $error; }
    }
    public function setMonthlyRate(int $amountSatang,int $referenceDays,string $actor): void {
        if($referenceDays<28||$referenceDays>31||$amountSatang<$referenceDays||$amountSatang>1000000000)throw new BookingError('Enter a monthly price and a reference period of 28–31 nights.');
        $this->db->exec('BEGIN IMMEDIATE');
        try{
            $daily=intdiv($amountSatang+intdiv($referenceDays,2),$referenceDays);
            $q=$this->db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');
            foreach(['base_rate_mode'=>'monthly','base_rate_monthly_satang'=>(string)$amountSatang,'base_rate_reference_days'=>(string)$referenceDays,'base_rate_satang'=>(string)$daily,'approved_prices'=>'1']as $key=>$value)$q->execute([$key,$value]);
            $this->db->exec("UPDATE settings SET value=CAST(value AS INTEGER)+1 WHERE key='rule_version'");
            $this->audit(null,$actor,'monthly_reference_price_updated',null,null);
            $this->db->exec('COMMIT');
        }catch(\Throwable $error){try{$this->db->exec('ROLLBACK');}catch(\Throwable $ignored){}throw $error;}
    }
}

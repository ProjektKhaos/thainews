<?php
declare(strict_types=1);
namespace Cosmos;
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME'])===__FILE__) { http_response_code(404); exit; }
final class Auth {
    public function __construct(private Booking $app) {}
    public function current(): ?array {
        if (empty($_SESSION['cosmos_admin_id']) || time()-(int)($_SESSION['cosmos_admin_login_at']??0)>=28800) return null;
        $q=$this->app->db->prepare('SELECT id,username,role,session_version FROM admin_users WHERE id=? AND active=1');
        $q->execute([$_SESSION['cosmos_admin_id']]);$user=$q->fetch();
        if (!$user || (int)$user['session_version']!==(int)($_SESSION['cosmos_admin_version']??0)) return null;
        return $user;
    }
    public function throttle(string $operation): void {
        $key=hash('sha256',$operation.'|'.($_SERVER['REMOTE_ADDR']??'local'));
        $now=time();$db=$this->app->db;
        $db->exec('BEGIN IMMEDIATE');
        try {
            $q=$db->prepare('INSERT INTO rate_limits(key,window_start,attempts) VALUES(?,?,1) ON CONFLICT(key) DO UPDATE SET attempts=CASE WHEN window_start<? THEN 1 ELSE attempts+1 END,window_start=CASE WHEN window_start<? THEN excluded.window_start ELSE window_start END');
            $q->execute([$key,$now,$now-900,$now-900]);
            $q=$db->prepare('SELECT attempts FROM rate_limits WHERE key=?');$q->execute([$key]);$attempts=(int)$q->fetchColumn();
            $db->exec('COMMIT');
        } catch (\Throwable $error) { $db->exec('ROLLBACK');throw $error; }
        if ($attempts>10) throw new BookingError('Too many attempts. Please try again in 15 minutes.',429);
    }
    public function login(string $username,string $password): array {
        $this->throttle('admin-login');
        $q=$this->app->db->prepare('SELECT * FROM admin_users WHERE username=? AND active=1');$q->execute([trim($username)]);$user=$q->fetch();
        // Always perform a password hash check, including unknown accounts.
        $valid=password_verify($password,$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (!$user || !$valid || ($user['locked_until'] && $user['locked_until']>gmdate('Y-m-d H:i:s'))) {
            if ($user) { $q=$this->app->db->prepare("UPDATE admin_users SET failed_count=failed_count+1,locked_until=CASE WHEN failed_count>=4 THEN datetime('now','+15 minutes') ELSE locked_until END WHERE id=?");$q->execute([$user['id']]); }
            throw new BookingError('Incorrect username or password.',401);
        }
        $q=$this->app->db->prepare('UPDATE admin_users SET failed_count=0,locked_until=NULL WHERE id=?');$q->execute([$user['id']]);
        $this->start($user);return $user;
    }
    public function start(array $user): void {
        session_regenerate_id(true);
        $_SESSION['cosmos_admin_id']=(int)$user['id'];$_SESSION['cosmos_admin_version']=(int)$user['session_version'];
        $_SESSION['cosmos_admin_login_at']=time();$_SESSION['cosmos_admin_csrf']=bin2hex(random_bytes(24));
        $_SESSION['cosmos_site_access']=true;
        unset($_SESSION['cosmos_setup_token']);
    }
    public function setupValid(string $token): bool {
        if (!preg_match('/^[a-f0-9]{64}$/',$token)) return false;
        if ((int)$this->app->db->query('SELECT COUNT(*) FROM admin_users WHERE active=1')->fetchColumn()>0) return false;
        $q=$this->app->db->prepare('SELECT 1 FROM admin_setup WHERE token_hash=? AND used_at IS NULL AND expires_at>CURRENT_TIMESTAMP');$q->execute([hash('sha256',$token)]);
        return (bool)$q->fetchColumn();
    }
    public function setup(string $token,string $username,string $password): void {
        $this->throttle('admin-setup');self::validateCredentials($username,$password);
        $this->app->db->exec('BEGIN IMMEDIATE');
        try {
            if (!$this->setupValid($token)) throw new BookingError('The setup link has expired or has already been used.',403);
            $version=random_int(100000,PHP_INT_MAX-1000000);
            $q=$this->app->db->prepare("INSERT INTO admin_users(username,password_hash,role,session_version) VALUES(?,?,'admin',?)");$q->execute([$username,password_hash($password,PASSWORD_DEFAULT),$version]);
            $id=(int)$this->app->db->lastInsertId();
            $this->app->db->exec('UPDATE admin_setup SET used_at=CURRENT_TIMESTAMP WHERE used_at IS NULL');
            $q=$this->app->db->prepare("INSERT INTO audit_log(actor,action) VALUES(?,'first_admin_created')");$q->execute(['admin:'.$id]);
            $this->app->db->exec('COMMIT');$this->start(['id'=>$id,'session_version'=>$version]);
        } catch (\Throwable $error) { try{$this->app->db->exec('ROLLBACK');}catch(\Throwable $ignored){}throw $error; }
    }
    public static function validateCredentials(string $username,string $password): void {
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/',$username)) throw new BookingError('Use 3–50 letters, numbers, dots, underscores or hyphens for the username.');
        if (strlen($password)<16 || strlen($password)>200) throw new BookingError('Use a password of 16–200 characters.');
    }
}

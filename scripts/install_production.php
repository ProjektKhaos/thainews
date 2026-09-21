<?php
// Senast uppdaterad: 2026-09-20 19:30 | One-time production DB/config installer

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || function_exists('posix_geteuid') && posix_geteuid() !== 0) {
    fwrite(STDERR, "Run this installer as root from CLI.\n");
    exit(77);
}

$projectRoot = dirname(__DIR__);
$configDir = '/etc/thainews';
$configFile = $configDir . '/config.php';
if (is_file($configFile)) {
    fwrite(STDOUT, "Production config already exists; it was not overwritten.\n");
    exit(0);
}

$databaseName = 'thai_news';
$databaseUser = 'thai_news_app';
$databasePassword = bin2hex(random_bytes(24));
$appSecret = bin2hex(random_bytes(32));
$rateSecret = bin2hex(random_bytes(32));

$pdo = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$quotedPassword = $pdo->quote($databasePassword);
$pdo->exec("CREATE USER IF NOT EXISTS '{$databaseUser}'@'127.0.0.1' IDENTIFIED BY {$quotedPassword}");
$pdo->exec("ALTER USER '{$databaseUser}'@'127.0.0.1' IDENTIFIED BY {$quotedPassword}");
$pdo->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$databaseName}`.* TO '{$databaseUser}'@'127.0.0.1'");
$pdo->exec('FLUSH PRIVILEGES');

$smtpDsn = trim((string) getenv('THAI_NEWS_SMTP_DSN'));
$translationKey = trim((string) getenv('THAI_NEWS_TRANSLATION_API_KEY'));
$config = [
    'env' => 'production',
    'public_origin' => 'https://thainews.aberg.online',
    'base_url' => '',
    'timezone' => 'Asia/Bangkok',
    'default_language' => 'en',
    'supported_languages' => ['en', 'th', 'sv'],
    'asset_version' => '1.0.16',
    'app_secret' => $appSecret,
    'rate_limit_secret' => $rateSecret,
    'db' => [
        'dsn' => "mysql:host=127.0.0.1;dbname={$databaseName};charset=utf8mb4",
        'user' => $databaseUser,
        'pass' => $databasePassword,
    ],
    'smtp' => [
        'enabled' => $smtpDsn !== '',
        'dsn' => $smtpDsn,
        'from_email' => trim((string) getenv('THAI_NEWS_FROM_EMAIL')) ?: 'news@thainews.aberg.online',
        'from_name' => 'Thai News',
    ],
    'fetch' => [
        'user_agent' => 'ThaiNewsAggregator/1.0 (+https://thainews.aberg.online/)',
        'connect_timeout' => 5,
        'timeout' => 20,
        'max_bytes' => 2_097_152,
    ],
    'translation' => [
        'enabled' => $translationKey !== '',
        'provider' => 'google_cloud_v2',
        'api_key' => $translationKey,
        'connect_timeout' => 5,
        'timeout' => 30,
        'batch_limit' => 50,
    ],
    'digest' => [
        'times' => ['00:00', '06:00', '12:00', '18:00'],
        'slot_window_minutes' => 30,
        'max_per_source' => 10,
        'max_total' => 40,
    ],
];

if (!is_dir($configDir) && !mkdir($configDir, 0750, true) && !is_dir($configDir)) {
    throw new RuntimeException('Unable to create config directory.');
}
$temporary = tempnam($configDir, '.config.');
if ($temporary === false) {
    throw new RuntimeException('Unable to create temporary config file.');
}
$contents = "<?php\n// Generated production secrets — keep outside DocumentRoot.\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
    throw new RuntimeException('Unable to write production config.');
}
chmod($temporary, 0640);
chown($temporary, 'root');
chgrp($temporary, 'www-data');
if (!rename($temporary, $configFile)) {
    throw new RuntimeException('Unable to install production config.');
}
chmod($configDir, 0750);
chown($configDir, 'root');
chgrp($configDir, 'www-data');

fwrite(STDOUT, "Created production database, runtime user and external config.\n");
fwrite(STDOUT, $smtpDsn === '' ? "SMTP remains disabled until THAI_NEWS_SMTP_DSN is supplied.\n" : "SMTP configured.\n");
fwrite(STDOUT, $translationKey === '' ? "Headline translation remains disabled until THAI_NEWS_TRANSLATION_API_KEY is supplied.\n" : "Headline translation configured.\n");

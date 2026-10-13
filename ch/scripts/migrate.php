<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Runtime.php';
$path = getenv('COSMOS_DB_PATH') ?: '';
$project = realpath(dirname(__DIR__));
$dir = $path !== '' ? realpath(dirname($path)) : false;
if ($dir === false || $path[0] !== '/' || $project === false || $dir === $project || str_starts_with($dir, $project . '/')) {
    fwrite(STDERR, "COSMOS_DB_PATH måste peka på en privat katalog utanför projektet.\n"); exit(2);
}
$root = realpath(getenv('COSMOS_DOCUMENT_ROOT') ?: '') ?: $project;
if ($dir === $root || str_starts_with($dir, $root . '/')) { fwrite(STDERR, "Databasen får inte ligga i DocumentRoot.\n"); exit(2); }
$new = !is_file($path);
if (!$new) {
    $realFile=realpath($path);
    if ($realFile===false || $realFile===$project || str_starts_with($realFile,$project.'/') || $realFile===$root || str_starts_with($realFile,$root.'/')) { fwrite(STDERR,"Databasen får inte peka in i DocumentRoot.\n"); exit(2); }
}
if ($new && (!is_writable($dir) || !touch($path) || !chmod($path, 0600))) { fwrite(STDERR,"Kan inte skapa privat databas.\n"); exit(2); }
try {
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000');
    $db->exec('BEGIN IMMEDIATE');
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations(version INTEGER PRIMARY KEY, applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    foreach (glob(dirname(__DIR__).'/db/migrations/[0-9]*.php') as $migration) {
        $version=(int)basename($migration);
        $check=$db->prepare('SELECT 1 FROM schema_migrations WHERE version=?');$check->execute([$version]);
        if ($check->fetchColumn()) continue;
        $sql=require $migration;
        if (!is_string($sql)) throw new RuntimeException('Migrationen saknas.');
        foreach (explode(';', $sql) as $statement) if (trim($statement)!=='') $db->exec($statement);
        $record=$db->prepare('INSERT INTO schema_migrations(version) VALUES(?)');$record->execute([$version]);
    }
    $db->exec('COMMIT');
    echo "Databasmigrationer klara. Befintliga driftflaggor bevarade.\n";
} catch (Throwable $error) {
    if (isset($db)) { try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {} }
    fwrite(STDERR, "Migration misslyckades: " . $error->getMessage() . "\n"); exit(1);
}

<?php
// Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | Version 1.00 | av: KlⒶssⓔ & Ⓐberg
/**
 * ZIP-export och återställning för recensioner och originalbilder.
 * Version 1.00 | Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok)
 * Kräver PHP-tillägget zip. Adminsession och CSRF-skydd krävs.
 */
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(tr('Endast POST tillåtet.')); }
csrf_check();
if (!class_exists('ZipArchive')) { flash('error', tr('PHP-tillägget zip saknas.')); go('admin.php'); }
$pdo = db();
$action = (string)($_POST['action'] ?? '');

try {
    if ($action === 'export') {
        $tmpZip = tempnam(STORAGE_DIR . '/tmp', 'cm-export-');
        if ($tmpZip === false) throw new RuntimeException(tr('Kunde inte skapa temporär backupfil.'));
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpZip);
            throw new RuntimeException(tr('Kunde inte skapa ZIP-arkivet.'));
        }
        $reviews = $pdo->query('SELECT id, market_slug, title, body, source_language, rating, visited_on, created_at, updated_at FROM reviews ORDER BY id')->fetchAll();
        $photoRows = $pdo->query('SELECT id, review_id, filename, mime, created_at FROM photos ORDER BY id')->fetchAll();
        $photos = [];
        foreach ($photoRows as $photo) {
            $path = STORAGE_DIR . '/photos/' . $photo['filename'];
            if (is_file($path)) {
                $zip->addFile($path, 'photos/' . $photo['filename']);
                $photos[] = $photo;
            }
        }
        $manifest = ['format'=>'chiang-mai-market-journal', 'version'=>1, 'exported_at'=>gmdate('c'), 'reviews'=>$reviews, 'photos'=>$photos];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        if (!$zip->close()) { @unlink($tmpZip); throw new RuntimeException(tr('ZIP-arkivet kunde inte färdigställas.')); }
        $name = 'chiang-mai-market-backup-' . date('Y-m-d-His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($tmpZip));
        header('Cache-Control: no-store');
        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }
    if ($action !== 'import') throw new RuntimeException(tr('Okänd backupåtgärd.'));
    if (($_POST['confirm_replace'] ?? '') !== 'yes') throw new RuntimeException(tr('Bekräfta att nuvarande innehåll får ersättas.'));
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException(tr('Välj en giltig ZIP-backup att återställa.'));
    $upload = $_FILES['backup_file'];
    if ($upload['size'] < 1 || $upload['size'] > MAX_BACKUP_BYTES) throw new RuntimeException(tr('ZIP-filen får vara högst 2 GB.'));
    if (!is_uploaded_file($upload['tmp_name'])) throw new RuntimeException(tr('Uppladdningen är ogiltig.'));
    $zip = new ZipArchive();
    if ($zip->open($upload['tmp_name'], ZipArchive::RDONLY) !== true) throw new RuntimeException(tr('Filen är inte ett läsbart ZIP-arkiv.'));
    try {
        if ($zip->numFiles > 30001) throw new RuntimeException(tr('Backupen innehåller för många filer.'));
        $manifestIndex = $zip->locateName('manifest.json');
        $meta = $manifestIndex === false ? false : $zip->statIndex($manifestIndex);
        if (!$meta || ($meta['size'] ?? 0) > 16 * 1024 * 1024) throw new RuntimeException(tr('ZIP-filen saknar en giltig manifest.json.'));
        $json = $zip->getFromName('manifest.json');
        if ($json === false) throw new RuntimeException(tr('Kunde inte läsa manifest.json.'));
        $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['format'] ?? '') !== 'chiang-mai-market-journal' || ($manifest['version'] ?? null) !== 1 || !is_array($manifest['reviews'] ?? null) || !is_array($manifest['photos'] ?? null)) {
            throw new RuntimeException(tr('Detta är inte en kompatibel Market Journal-backup.'));
        }
        if (count($manifest['reviews']) > 2000 || count($manifest['photos']) > 30000) throw new RuntimeException(tr('Backupen innehåller för många poster.'));
        // Kontrollera poster innan databasens nuvarande innehåll påverkas.
        $reviewIds = [];
        foreach ($manifest['reviews'] as $r) {
            if (!is_array($r) || !isset($r['id']) || !is_numeric($r['id']) || !market_by_slug((string)($r['market_slug'] ?? '')) || !is_string($r['title'] ?? null) || !is_string($r['body'] ?? null) || trim($r['title']) === '' || trim($r['body']) === '' || strlen($r['title']) > 400 || strlen($r['body']) > 20000 || !in_array($r['source_language'] ?? 'sv', ['sv', 'en'], true) || !is_numeric($r['rating'] ?? null) || (int)$r['rating'] < 1 || (int)$r['rating'] > 5) {
                throw new RuntimeException(tr('Backupen har en ogiltig recension.'));
            }
            $oldId = (int)$r['id'];
            if ($oldId < 1 || isset($reviewIds[$oldId])) throw new RuntimeException(tr('Backupen har dubbla eller felaktiga recensions-ID.'));
            $reviewIds[$oldId] = true;
        }
        $preparedPhotos = [];
        $newFiles = [];
        $totalUncompressed = 0;
        $mediaCounts = [];
        try {
            foreach ($manifest['photos'] as $p) {
                if (!is_array($p) || !isset($reviewIds[(int)($p['review_id'] ?? 0)]) || !is_string($p['filename'] ?? null) || !preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp|mp4|webm|mov)\z/', $p['filename'])) {
                    throw new RuntimeException(tr('Backupen har en ogiltig filreferens.'));
                }
                $reviewId = (int)$p['review_id'];
                $mediaCounts[$reviewId] = ($mediaCounts[$reviewId] ?? 0) + 1;
                if ($mediaCounts[$reviewId] > MAX_MEDIA_PER_REVIEW) throw new RuntimeException(tr('Högst %d bilder och videor per recension.', MAX_MEDIA_PER_REVIEW));
                $entry = 'photos/' . $p['filename'];
                $entryStat = $zip->statName($entry);
                if (!$entryStat || ($entryStat['size'] ?? 0) > MAX_VIDEO_BYTES || ($entryStat['size'] ?? 0) < 1) throw new RuntimeException(tr('En backupfil saknas eller är för stor.'));
                $totalUncompressed += (int)$entryStat['size'];
                if ($totalUncompressed > MAX_BACKUP_UNCOMPRESSED_BYTES) throw new RuntimeException(tr('Backupen innehåller mer än 4 GB uppackade filer.'));
                $temp = tempnam(STORAGE_DIR . '/tmp', 'cm-restore-');
                if ($temp === false) throw new RuntimeException(tr('Kunde inte förbereda filåterställningen.'));
                try {
                    $input = $zip->getStream($entry);
                    $output = fopen($temp, 'wb');
                    if ($input === false || $output === false) {
                        if (is_resource($input)) fclose($input);
                        if (is_resource($output)) fclose($output);
                        throw new RuntimeException(tr('Kunde inte läsa en fil i backupen.'));
                    }
                    try { $copied = stream_copy_to_stream($input, $output, MAX_VIDEO_BYTES + 1); }
                    finally { fclose($input); fclose($output); }
                    if ($copied !== (int)$entryStat['size']) throw new RuntimeException(tr('Kunde inte läsa en fil i backupen.'));
                    $mime = validate_media($temp, $copied);
                    if ($mime !== ($p['mime'] ?? null)) throw new RuntimeException(tr('En fil har fel MIME-typ i backupen.'));
                    $newName = bin2hex(random_bytes(16)) . '.' . media_extension($mime);
                    if (!rename($temp, STORAGE_DIR . '/photos/' . $newName)) throw new RuntimeException(tr('Kunde inte återställa en fil.'));
                } finally { @unlink($temp); }
                @chmod(STORAGE_DIR . '/photos/' . $newName, 0600);
                $newFiles[] = $newName;
                $preparedPhotos[] = ['review_id'=>(int)$p['review_id'], 'filename'=>$newName, 'mime'=>$mime, 'created_at'=>(string)($p['created_at'] ?? gmdate('c'))];
            }
            $oldFiles = $pdo->query('SELECT filename FROM photos')->fetchAll(PDO::FETCH_COLUMN);
            $pdo->beginTransaction();
            try {
                $pdo->exec('DELETE FROM photos');
                $pdo->exec('DELETE FROM reviews');
                $map = [];
                $insReview = $pdo->prepare('INSERT INTO reviews (market_slug,title,body,source_language,rating,visited_on,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)');
                foreach ($manifest['reviews'] as $r) {
                    $insReview->execute([(string)$r['market_slug'], (string)$r['title'], (string)$r['body'], (string)($r['source_language'] ?? 'sv'), (int)$r['rating'], $r['visited_on'] ?: null, (string)($r['created_at'] ?? gmdate('c')), (string)($r['updated_at'] ?? gmdate('c'))]);
                    $map[(int)$r['id']] = (int)$pdo->lastInsertId();
                }
                $insPhoto = $pdo->prepare('INSERT INTO photos (review_id,filename,mime,created_at) VALUES (?,?,?,?)');
                foreach ($preparedPhotos as $p) $insPhoto->execute([$map[$p['review_id']], $p['filename'], $p['mime'], $p['created_at']]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            foreach ($oldFiles as $oldFile) @unlink(STORAGE_DIR . '/photos/' . $oldFile);
        } catch (Throwable $e) {
            foreach ($newFiles as $f) @unlink(STORAGE_DIR . '/photos/' . $f);
            throw $e;
        }
    } finally {
        $zip->close();
    }
    flash('success', tr('Backupen har återställts! Recensioner, bilder och videor är tillbaka.'));
} catch (Throwable $e) {
    flash('error', 'Backup: ' . $e->getMessage());
}
go('admin.php');

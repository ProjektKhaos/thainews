<?php
// Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | Version 1.00 | av: KlⒶssⓔ & Ⓐberg
/**
 * CRUD + bilduppladdning – Version 1.00
 * Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | KlⒶssⓔ & Ⓐberg
 */
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(tr('Endast POST tillåts.')); }
if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) { http_response_code(413); exit(tr('Uppladdningen är för stor. Högst 500 MB per gång.')); }
csrf_check();
$pdo = db();
$action = (string)($_POST['action'] ?? '');
$slug = (string)($_POST['market_slug'] ?? '');
if (!market_by_slug($slug)) { http_response_code(404); exit(tr('Okänd marknad.')); }
$redirectQuery = ['m' => $slug];
try {
    if ($action === 'save_review') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $wasEditing = $reviewId > 0;
        $title = trim((string)($_POST['title'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $sourceLanguage = (string)($_POST['source_language'] ?? market_language());
        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1, 'max_range'=>5]]);
        $visited = trim((string)($_POST['visited_on'] ?? ''));
        if ($title === '' || strlen($title) > 400 || $body === '' || strlen($body) > 20000 || $rating === false || !in_array($sourceLanguage, ['sv', 'en'], true)) {
            throw new RuntimeException(tr('Fyll i titel, recension och ett giltigt betyg.'));
        }
        if ($visited !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $visited);
            if (!$date || $date->format('Y-m-d') !== $visited || $visited > date('Y-m-d')) {
                throw new RuntimeException(tr('Ange ett giltigt datum som inte ligger i framtiden.'));
            }
        }
        if ($reviewId > 0) {
            $stmt = $pdo->prepare('SELECT id FROM reviews WHERE id = ? AND market_slug = ?');
            $stmt->execute([$reviewId, $slug]);
            if (!$stmt->fetch()) throw new RuntimeException(tr('Recensionen kunde inte hittas.'));
        }
        // Förhandsvalidera samtliga uppladdningar innan något skrivs till databasen.
        $files = [];
        $totalUploadBytes = 0;
        $upload = $_FILES['media'] ?? $_FILES['photos'] ?? null;
        if (is_array($upload) && is_array($upload['name'] ?? null)) {
            if (count($upload['name']) > MAX_MEDIA_PER_REVIEW) throw new RuntimeException(tr('Högst %d bilder och videor per recension.', MAX_MEDIA_PER_REVIEW));
            foreach ($upload['name'] as $i => $name) {
                $err = (int)($upload['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                if ($err === UPLOAD_ERR_NO_FILE) continue;
                if ($err !== UPLOAD_ERR_OK) throw new RuntimeException(tr('En filuppladdning misslyckades (felkod %d). Kontrollera filstorleken.', $err));
                $tmp = (string)$upload['tmp_name'][$i];
                if (!is_uploaded_file($tmp)) throw new RuntimeException(tr('Felaktig uppladdad fil.'));
                $size = (int)$upload['size'][$i];
                $totalUploadBytes += $size;
                if ($totalUploadBytes > MAX_UPLOAD_TOTAL_BYTES) throw new RuntimeException(tr('Uppladdningen är för stor. Högst 500 MB per gång.'));
                $files[] = ['tmp'=>$tmp, 'mime'=>validate_media($tmp, $size)];
            }
        }
        $existing = 0;
        if ($reviewId > 0) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM photos WHERE review_id=?');
            $stmt->execute([$reviewId]);
            $existing = (int)$stmt->fetchColumn();
        }
        if ($existing + count($files) > MAX_MEDIA_PER_REVIEW) throw new RuntimeException(tr('Högst %d bilder och videor per recension.', MAX_MEDIA_PER_REVIEW));
        $createdPhotos = [];
        try {
            $pdo->beginTransaction();
            if ($reviewId > 0) {
                $stmt = $pdo->prepare("UPDATE reviews SET title=?, body=?, source_language=?, rating=?, visited_on=?, updated_at=datetime('now') WHERE id=? AND market_slug=?");
                $stmt->execute([$title, $body, $sourceLanguage, (int)$rating, $visited === '' ? null : $visited, $reviewId, $slug]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO reviews (market_slug, title, body, source_language, rating, visited_on, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
                $stmt->execute([$slug, $title, $body, $sourceLanguage, (int)$rating, $visited === '' ? null : $visited]);
                $reviewId = (int)$pdo->lastInsertId();
            }
            $stmt = $pdo->prepare("INSERT INTO photos (review_id, filename, mime, created_at) VALUES (?, ?, ?, datetime('now'))");
            foreach ($files as $file) {
                $filename = store_uploaded_media($file['tmp'], $file['mime']);
                $createdPhotos[] = $filename;
                $stmt->execute([$reviewId, $filename, $file['mime']]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($createdPhotos as $filename) @unlink(STORAGE_DIR . '/photos/' . $filename);
            throw $e;
        }
        flash('success', tr($wasEditing ? 'Recensionen har uppdaterats!' : 'Recensionen har publicerats!'));
        $redirectQuery = ['m'=>$slug];
    } elseif ($action === 'delete_review') {
        $id = (int)($_POST['review_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT p.filename FROM photos p JOIN reviews r ON r.id=p.review_id WHERE r.id=? AND r.market_slug=?');
        $stmt->execute([$id, $slug]);
        $files = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare('DELETE FROM reviews WHERE id=? AND market_slug=?');
        $stmt->execute([$id, $slug]);
        if (!$stmt->rowCount()) throw new RuntimeException(tr('Recensionen hittades inte.'));
        foreach ($files as $filename) @unlink(STORAGE_DIR . '/photos/' . $filename);
        flash('success', tr('Recensionen och dess bilder och videor har tagits bort.'));
    } elseif ($action === 'delete_photo') {
        $id = (int)($_POST['photo_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT p.filename FROM photos p JOIN reviews r ON r.id=p.review_id WHERE p.id=? AND r.market_slug=?');
        $stmt->execute([$id, $slug]);
        $filename = $stmt->fetchColumn();
        if ($filename === false) throw new RuntimeException(tr('Filen hittades inte.'));
        $stmt = $pdo->prepare('DELETE FROM photos WHERE id=?');
        $stmt->execute([$id]);
        @unlink(STORAGE_DIR . '/photos/' . $filename);
        flash('success', tr('Filen har tagits bort.'));
    } else {
        throw new RuntimeException(tr('Okänd åtgärd.'));
    }
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    $redirectQuery = ['m'=>$slug];
}
go('index.php', $redirectQuery);

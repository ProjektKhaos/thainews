<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit; }
$stmt = db()->prepare('SELECT filename, mime FROM photos WHERE id = ?');
$stmt->execute([$id]);
$media = $stmt->fetch();
if (!$media || !preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp|mp4|webm|mov)\z/', $media['filename'])) { http_response_code(404); exit; }
$mime = (string)$media['mime'];
if (media_extension($mime) !== pathinfo($media['filename'], PATHINFO_EXTENSION)) { http_response_code(404); exit; }
$path = STORAGE_DIR . '/photos/' . $media['filename'];
if (!is_file($path)) { http_response_code(404); exit; }
$size = filesize($path);
if ($size === false || $size < 1) { http_response_code(404); exit; }

session_write_close();
$start = 0;
$end = $size - 1;
$range = $_SERVER['HTTP_RANGE'] ?? '';
if ($range !== '') {
    if (!preg_match('/\Abytes=(\d*)-(\d*)\z/', $range, $parts) || ($parts[1] === '' && $parts[2] === '')) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }
    if ($parts[1] === '') {
        $suffix = (int)$parts[2];
        if ($suffix < 1) { header('Content-Range: bytes */' . $size); http_response_code(416); exit; }
        $start = max(0, $size - $suffix);
    } else {
        $start = (int)$parts[1];
        if ($parts[2] !== '') $end = min($end, (int)$parts[2]);
    }
    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . ($end - $start + 1));
header('Content-Disposition: inline; filename="market-media.' . media_extension($mime) . '"');
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') exit;

$stream = fopen($path, 'rb');
if ($stream === false) exit;
if ($start > 0) fseek($stream, $start);
$remaining = $end - $start + 1;
while ($remaining > 0 && !feof($stream)) {
    $chunk = fread($stream, min(1024 * 1024, $remaining));
    if ($chunk === false || $chunk === '') break;
    echo $chunk;
    $remaining -= strlen($chunk);
    if (connection_aborted()) break;
}
fclose($stream);

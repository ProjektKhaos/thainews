<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
require __DIR__ . '/access.php';
cosmos_access_session(1);
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
$next = cosmos_safe_next($_POST['next'] ?? $_GET['next'] ?? null, 1);
$error = '';
if (empty($_SESSION['cosmos_preview_csrf'])) $_SESSION['cosmos_preview_csrf']=bin2hex(random_bytes(24));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempts=&$_SESSION['cosmos_preview_attempts']; $now=time();
    if (!is_array($attempts??null) || ($attempts['start']??0)<$now-900) $attempts=['start'=>$now,'count'=>0];
    if (++$attempts['count']>10) { http_response_code(429); $error='För många försök. Försök senare.'; }
    elseif (!hash_equals((string)$_SESSION['cosmos_preview_csrf'],(string)($_POST['csrf']??''))) { http_response_code(403); $error='Ogiltig säkerhetstoken.'; }
    $word = $_POST['word'] ?? '';
    $hash=getenv('COSMOS_PREVIEW_PASSWORD_HASH')?:'';
    $valid=is_string($word) && ($hash!=='' ? password_verify(trim($word),$hash) : hash_equals('huset',trim($word)));
    if ($error==='' && $valid) {
        session_regenerate_id(true);
        $_SESSION['cosmos_site_access'] = true;
        unset($_SESSION['cosmos_preview_attempts']);
        session_write_close();
        header('Location: ' . $next, true, 303);
        exit;
    }
    if ($error==='') $error = 'Fel ord. Försök igen.';
}
if (!empty($_SESSION['cosmos_site_access'])) {
    header('Location: ' . $next, true, 302);
    exit;
}
function access_h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Logga in | Cosmos House</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?=access_h(cosmos_project_url(1, 'assets/style.css'))?>">
</head>
<body class="access-page">
  <main class="access-card">
    <div class="brand"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></div>
    <div class="eyebrow"><span class="tinyline"></span> CHIANG MAI, THAILAND</div>
    <h1>Välkommen till huset.</h1>
    <p>Skriv in ordet för att öppna sidan.</p>
    <form method="post" action="<?=access_h(cosmos_project_url(1, 'login.php'))?>">
      <input type="hidden" name="next" value="<?=access_h($next)?>">
      <input type="hidden" name="csrf" value="<?=access_h($_SESSION['cosmos_preview_csrf'])?>">
      <label for="access-word">Ord</label>
      <input id="access-word" name="word" type="password" autocomplete="current-password" required autofocus>
      <?php if ($error !== ''): ?><p class="access-error" role="alert"><?=access_h($error)?></p><?php endif; ?>
      <button class="btn dark" type="submit">Öppna Cosmos House <span>↗</span></button>
    </form>
  </main>
</body>
</html>

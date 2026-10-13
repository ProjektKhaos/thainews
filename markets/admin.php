<?php
// Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | Version 1.00 | av: KlⒶssⓔ & Ⓐberg
/**
 * Administrationsinloggning – Version 1.00
 * Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | KlⒶssⓔ & Ⓐberg
 */
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

try { $pdo = db(); } catch (Throwable $e) { http_response_code(503); exit(h($e->getMessage())); }
$installed = is_installed();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'setup') {
            if ($installed) throw new RuntimeException(tr('Administrationen är redan installerad.'));
            $setupKey = trim((string)($_POST['setup_key'] ?? ''));
            $username = trim((string)($_POST['username'] ?? 'admin'));
            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');
            if (!hash_equals(SETUP_KEY, $setupKey)) throw new RuntimeException(tr('Fel installationsnyckel. Den finns i app/config.php.'));
            if (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $username)) throw new RuntimeException(tr('Användarnamn: 3–30 bokstäver, siffror, _ eller -.'));
            if (strlen($password) < 12) throw new RuntimeException(tr('Välj ett lösenord med minst 12 tecken.'));
            if ($password !== $confirm) throw new RuntimeException(tr('Lösenorden stämmer inte överens.'));
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, datetime('now'))");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$pdo->lastInsertId();
            flash('success', tr('Klart! Adminkontot är skapat. Nu kan du skriva recensioner.'));
            go();
        }
        if ($action === 'login') {
            if (!$installed) throw new RuntimeException(tr('Skapa adminkontot först.'));
            $until = (int)($_SESSION['locked_until'] ?? 0);
            if ($until > time()) throw new RuntimeException(tr('För många försök. Testa om ett par minuter.'));
            $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
            $stmt->execute([trim((string)($_POST['username'] ?? ''))]);
            $admin = $stmt->fetch();
            if (!$admin || !password_verify((string)($_POST['password'] ?? ''), $admin['password_hash'])) {
                $_SESSION['failed_logins'] = (int)($_SESSION['failed_logins'] ?? 0) + 1;
                if ($_SESSION['failed_logins'] >= 5) {
                    $_SESSION['locked_until'] = time() + 300;
                    $_SESSION['failed_logins'] = 0;
                }
                throw new RuntimeException(tr('Fel användarnamn eller lösenord.'));
            }
            session_regenerate_id(true);
            unset($_SESSION['failed_logins'], $_SESSION['locked_until']);
            $_SESSION['admin_id'] = (int)$admin['id'];
            flash('success', tr('Välkommen tillbaka!'));
            go();
        }
        if ($action === 'logout') {
            require_admin();
            unset($_SESSION['admin_id']);
            session_regenerate_id(true);
            flash('success', tr('Du är utloggad.'));
            go('admin.php');
        }
        if ($action === 'change_password') {
            require_admin();
            $adminId = (int)$_SESSION['admin_id'];
            $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
            $stmt->execute([$adminId]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify((string)($_POST['old_password'] ?? ''), (string)$hash)) throw new RuntimeException(tr('Nuvarande lösenord är fel.'));
            $new = (string)($_POST['new_password'] ?? '');
            if (strlen($new) < 12) throw new RuntimeException(tr('Nya lösenordet måste vara minst 12 tecken.'));
            if ($new !== (string)($_POST['new_password_confirm'] ?? '')) throw new RuntimeException(tr('Nya lösenorden stämmer inte överens.'));
            $stmt = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $adminId]);
            flash('success', tr('Lösenordet har ändrats.'));
            go('admin.php');
        }
        throw new RuntimeException(tr('Okänd åtgärd.'));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
view('header.php', ['title'=>tr('Administration')]);
?>
<section class="shell admin-section">
  <h1><?= h(tr(!$installed ? 'Välkommen till din marknadsdagbok.' : (is_admin() ? 'Din kontrollpanel.' : 'Välkommen tillbaka.'))) ?></h1>
  <?php if ($error): ?><div class="notice error" role="alert"><?= h($error) ?></div><?php endif; ?>
  <?php if (!$installed): ?>
    <div class="admin-card"><h2><?= h(tr('Skapa administratör')) ?></h2><p><?= h(tr('Det här görs en gång. Installationsnyckeln finns i filen')) ?> <code>app/config.php</code> (SETUP_KEY) <?= h(tr('och ska inte delas offentligt.')) ?></p>
      <form method="post" class="review-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="setup">
      <label><?= h(tr('Installationsnyckel')) ?><input type="password" name="setup_key" required autocomplete="off"></label>
      <label><?= h(tr('Användarnamn')) ?><input name="username" value="admin" required minlength="3" maxlength="30" autocomplete="username"></label>
      <label><?= h(tr('Lösenord, minst 12 tecken')) ?><input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
      <label><?= h(tr('Upprepa lösenordet')) ?><input type="password" name="password_confirm" required minlength="12" autocomplete="new-password"></label>
      <button type="submit" class="btn btn-primary"><?= h(tr('Skapa konto')) ?> →</button></form>
    </div>
  <?php elseif (!is_admin()): ?>
    <div class="admin-card"><h2><?= h(tr('Logga in')) ?></h2><p><?= h(tr('Bara du som administratör kan lägga till, ändra och ta bort recensioner, bilder och videor. Besökare kan läsa guiden.')) ?></p>
      <form method="post" class="review-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="login">
      <label><?= h(tr('Användarnamn')) ?><input name="username" required autocomplete="username" autofocus></label>
      <label><?= h(tr('Lösenord')) ?><input type="password" name="password" required autocomplete="current-password"></label>
      <button type="submit" class="btn btn-primary"><?= h(tr('Logga in')) ?> →</button></form>
    </div>
  <?php else: ?>
    <div class="admin-dashboard">
      <div class="admin-card"><span class="admin-card-icon">✎</span><h2><?= h(tr('Recensioner, bilder och videor')) ?></h2><p><?= h(tr('Öppna en marknad och skriv om ett nytt besök. Du kan redigera och ta bort dina inlägg när du vill.')) ?></p><a href="<?= h(app_url()) ?>#market-list" class="btn btn-primary"><?= h(tr('Visa alla marknader')) ?> ↗</a></div>
      <div class="admin-card"><span class="admin-card-icon">↥</span><h2><?= h(tr('Säkerhetskopia')) ?></h2><p><?= h(tr('Exportera recensionerna och alla uppladdade bilder och videor som en ZIP-fil. Återställ vid serverflytt eller olycka.')) ?></p>
        <?php if (class_exists('ZipArchive')): ?><form method="post" action="<?= h(app_url('backup.php')) ?>"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="export"><button type="submit" class="btn btn-outline">↓ <?= h(tr('Exportera ZIP-backup')) ?></button></form>
        <form action="<?= h(app_url('backup.php')) ?>" method="post" enctype="multipart/form-data" class="restore-form" onsubmit="return confirm(<?= h(json_encode(tr('Allt nuvarande innehåll (recensioner, bilder och videor) ersätts med innehållet i ZIP-filen. Fortsätta?'), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="import">
          <label><?= h(tr('Återställ från tidigare backup')) ?> <input type="file" name="backup_file" accept=".zip,application/zip" required></label>
          <label class="checkbox-line"><input type="checkbox" name="confirm_replace" value="yes" required> <?= h(tr('Jag förstår att nuvarande recensioner, bilder och videor ersätts.')) ?></label>
          <button type="submit" class="btn btn-outline"><?= h(tr('Återställ ZIP')) ?> →</button>
        </form>
        <?php else: ?><div class="notice error"><?= h(tr('PHP-tillägget zip saknas. Installera php-zip för import och export.')) ?></div><?php endif; ?>
      </div>
      <div class="admin-card"><span class="admin-card-icon">⚿</span><h2><?= h(tr('Kontosäkerhet')) ?></h2><p><?= h(tr('Byt lösenord när du vill. Använd gärna ett unikt lösenord på minst 12 tecken.')) ?></p>
        <form method="post" class="review-form compact-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="change_password">
        <label><?= h(tr('Nuvarande lösenord')) ?><input name="old_password" type="password" required autocomplete="current-password"></label><label><?= h(tr('Nytt lösenord')) ?><input name="new_password" type="password" minlength="12" required autocomplete="new-password"></label><label><?= h(tr('Upprepa nytt lösenord')) ?><input name="new_password_confirm" type="password" minlength="12" required autocomplete="new-password"></label>
        <button type="submit" class="btn btn-outline"><?= h(tr('Uppdatera lösenord')) ?></button></form>
      </div>
    </div>
    <form method="post" class="logout-row"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button type="submit" class="text-link logout-button">↩ <?= h(tr('Logga ut')) ?></button></form>
  <?php endif; ?>
</section>
<?php view('footer.php'); ?>

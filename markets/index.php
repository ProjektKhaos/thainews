<?php
// Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | Version 1.00 | av: KlⒶssⓔ & Ⓐberg
/**
 * Index / marknadsdetalj – Version 1.00
 * Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | KlⒶssⓔ & Ⓐberg
 */
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

try {
    $pdo = db();
} catch (Throwable $e) {
    http_response_code(503);
    view('header.php', ['title' => tr('Installation behövs')]);
    echo '<section class="shell narrow empty-state"><h1>' . h(tr('Servern behöver en liten inställning')) . '</h1><p>' . h($e->getMessage()) . '</p><p>' . h(tr('Se README.md för hjälp med PHP SQLite och lagringsrättigheter.')) . '</p></section>';
    view('footer.php');
    exit;
}

$slug = trim((string)($_GET['m'] ?? ''));
$market = $slug === '' ? null : market_by_slug($slug);
if ($slug !== '' && !$market) {
    http_response_code(404);
    view('header.php', ['title' => tr('Marknaden hittades inte')]);
    echo '<section class="shell narrow empty-state"><h1>' . h(tr('Den marknaden finns inte')) . '</h1><p><a class="btn" href="' . h(app_url()) . '">← ' . h(tr('Visa marknaderna')) . '</a></p></section>';
    view('footer.php');
    exit;
}

if (!$market) {
    $markets = market_catalog();
    $statsRows = $pdo->query('SELECT market_slug, AVG(rating) AS avg_rating FROM reviews GROUP BY market_slug')->fetchAll();
    $stats = [];
    foreach ($statsRows as $r) $stats[$r['market_slug']] = $r;
    view('header.php', ['title'=>tr('Alla marknader')]);
    ?>
    <section class="shell catalog-section" id="market-list">
      <h1 class="visually-hidden"><?= h(tr('Marknader i Chiang Mai')) ?></h1>
      <div class="filters">
        <div class="filter-pills" aria-label="<?= h(tr('Filtrera efter marknadstyp')) ?>">
          <?php foreach (['Alla', 'Dagmarknad', 'Nattmarknad', 'Helgmarknad', 'Kvartersmarknad', 'Specialmarknad'] as $i => $category): ?>
            <button type="button" class="filter-btn <?= $i === 0 ? 'selected' : '' ?>" data-filter="<?= h(tr($category)) ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"><?= h(tr($category)) ?></button>
          <?php endforeach; ?>
        </div>
        <label class="search-wrap"><span aria-hidden="true">⌕</span><input id="market-search" type="search" placeholder="<?= h(tr('Sök marknad eller område')) ?>" aria-label="<?= h(tr('Sök bland marknader')) ?>" autocomplete="off"></label>
      </div>
      <div class="market-grid" id="market-grid">
        <?php foreach ($markets as $index => $m):
          $average = isset($stats[$m['slug']]) ? number_format((float)$stats[$m['slug']]['avg_rating'], 1, market_language() === 'sv' ? ',' : '.', '') : null;
          $image = market_image($m['slug']);
        ?>
          <a class="market-card" href="<?= h(app_url('index.php', ['m'=>$m['slug']])) ?>" data-category="<?= h($m['category']) ?>" data-search="<?= h(mb_safe_lower(implode(' ', [$m['name'], $m['area'], $m['category'], implode(' ', $m['tags']), $m['description']]))) ?>">
            <div class="card-art card-photo art-tone-<?= $index % 6 ?>">
              <?php if ($image): ?><img src="<?= h(app_url($image['file'])) ?>" alt="<?= h($image['exact'] ? tr('Foto av %s', $m['name']) : tr('Stämningsbild från en marknad i Chiang Mai')) ?>" loading="<?= $index < 6 ? 'eager' : 'lazy' ?>" decoding="async" width="1200" height="800"><?php endif; ?>
              <?php if ($image && !$image['exact']): ?><span class="photo-kind"><?= h(tr('Stämningsbild')) ?></span><?php endif; ?>
            </div>
            <div class="card-content"><div class="card-meta"><span><?= h($m['area']) ?> · <?= h($m['category']) ?></span><?php if ($average !== null): ?><span class="rating-mini">★ <?= h($average) ?></span><?php endif; ?></div><h3><?= h($m['name']) ?></h3><p><?= h($m['description']) ?></p></div>
          </a>
        <?php endforeach; ?>
      </div>
      <div id="filter-empty" class="no-results" hidden><?= h(tr('Inga marknader matchade sökningen. Prova ett annat ord eller filter.')) ?></div>
    </section>
    <?php
    view('footer.php');
    exit;
}

// Individuell marknad: samla recensioner och deras bilder.
$stmt = $pdo->prepare('SELECT * FROM reviews WHERE market_slug = ? ORDER BY created_at DESC, id DESC');
$stmt->execute([$slug]);
$reviews = $stmt->fetchAll();
$reviewIds = array_column($reviews, 'id');
$photosByReview = [];
if ($reviewIds) {
    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
    $photoStmt = $pdo->prepare('SELECT * FROM photos WHERE review_id IN (' . $placeholders . ') ORDER BY id DESC');
    $photoStmt->execute($reviewIds);
    foreach ($photoStmt->fetchAll() as $p) $photosByReview[(int)$p['review_id']][] = $p;
}
$average = $reviews ? array_sum(array_map(static fn($r) => (int)$r['rating'], $reviews)) / count($reviews) : null;
$editing = null;
if (is_admin() && isset($_GET['edit'])) {
    foreach ($reviews as $r) if ((int)$r['id'] === (int)$_GET['edit']) $editing = $r;
}
$reviews = translated_reviews($pdo, $reviews, market_language());
$mapQuery = $market['name'] . ', Chiang Mai, Thailand';
$image = market_image($slug);
view('header.php', ['title'=>$market['name'], 'photoModal'=>true]);
?>
<section class="detail-banner">
  <div class="shell">
    <div class="breadcrumbs"><a href="<?= h(app_url()) ?>">← <?= h(tr('Alla marknader')) ?></a><span>/</span><span><?= h($market['category']) ?></span></div>
    <div class="detail-hero-grid"><div>
      <span class="eyebrow light-eyebrow">📍 <?= h($market['area']) ?> · <?= h($market['category']) ?></span>
      <h1><?= h($market['name']) ?></h1>
      <p><?= h($market['description']) ?></p>
      <div class="detail-actions"><a class="btn btn-light" href="<?= h(app_url('map.php', ['m'=>$slug])) ?>">📍 <?= h(tr('Visa på kartan')) ?></a><a class="detail-google-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($mapQuery) ?>" target="_blank" rel="noopener noreferrer">Google Maps ↗</a><span class="detail-hours">◷ <?= h($market['schedule']) ?></span></div>
    </div>
    <?php if ($image): ?><figure class="detail-market-photo">
      <a class="detail-photo-link" href="<?= h(app_url($image['file'])) ?>" target="_blank" rel="noopener" data-gallery-item><img src="<?= h(app_url($image['file'])) ?>" alt="<?= h($image['exact'] ? tr('Foto av %s', $market['name']) : tr('Stämningsbild från en marknad i Chiang Mai')) ?>" width="1200" height="800" decoding="async"></a>
      <figcaption><?= h(tr($image['exact'] ? 'Platsfoto' : 'Stämningsbild från Chiang Mai, visar inte nödvändigtvis denna marknad')) ?> · <?= h(tr('Foto:')) ?> <a href="<?= h($image['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($image['artist']) ?></a> · <?= h($image['license']) ?></figcaption>
    </figure><?php endif; ?>
    </div>
  </div>
</section>
<section class="shell detail-section">
  <div class="tag-row"><?php foreach ($market['tags'] as $tag): ?><span class="tag"><?= h($tag) ?></span><?php endforeach; ?></div>
  <div class="review-head"><div><h2><?= h(tr('Betyg & recensioner')) ?></h2></div><div class="overall-score"><?php if ($average !== null): ?><strong><?= number_format($average, 1, market_language() === 'sv' ? ',' : '.', '') ?><small>/5</small></strong><span>★ <?= count($reviews) ?> <?= h(tr(count($reviews) === 1 ? 'recension' : 'recensioner')) ?></span><?php else: ?><strong>—</strong><span><?= h(tr('Inga betyg ännu')) ?></span><?php endif; ?></div></div>
  <?php if (is_admin()): ?>
    <div class="editor-panel" id="skriv-recension">
      <div class="editor-top"><div><h3><?= h(tr($editing ? 'Redigera besöket' : 'Skriv om ett besök')) ?></h3></div><span class="editor-icon">✎</span></div>
      <form action="<?= h(app_url('action.php')) ?>" method="post" enctype="multipart/form-data" class="review-form">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_review">
        <input type="hidden" name="market_slug" value="<?= h($slug) ?>">
        <input type="hidden" name="review_id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
        <div class="form-two-col"><label><?= h(tr('Titel på besöket')) ?> <input type="text" name="title" maxlength="120" required placeholder="<?= h(tr('Exempel: Fantastisk street food!')) ?>" value="<?= h($editing['title'] ?? '') ?>"></label><label><?= h(tr('Besöksdatum')) ?> <input type="date" name="visited_on" max="<?= date('Y-m-d') ?>" value="<?= h($editing['visited_on'] ?? date('Y-m-d')) ?>"></label></div>
        <label><?= h(tr('Språk i recensionen')) ?> <select name="source_language"><option value="sv"<?= ($editing['source_language'] ?? market_language()) === 'sv' ? ' selected' : '' ?>><?= h(tr('Svenska')) ?></option><option value="en"<?= ($editing['source_language'] ?? market_language()) === 'en' ? ' selected' : '' ?>><?= h(tr('Engelska')) ?></option></select></label>
        <div class="rating-field"><span><?= h(tr('Mitt betyg')) ?></span><div class="star-picker" role="radiogroup" aria-label="<?= h(tr('Välj betyg mellan 1 och 5')) ?>">
            <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= (int)($editing['rating'] ?? 5) === $i ? 'checked' : '' ?> required><label for="star<?= $i ?>" title="<?= $i ?> <?= h(tr('av 5 stjärnor')) ?>">★</label><?php endfor; ?>
        </div></div>
        <label><?= h(tr('Din recension')) ?> <textarea name="body" rows="6" maxlength="5000" required placeholder="<?= h(tr('Vad såg du? Vad åt du? Var det prisvärt? Vilka fynd gjorde du? Skriv precis som du vill.')) ?>"><?= h($editing['body'] ?? '') ?></textarea></label>
        <label class="upload-box"><span class="upload-symbol">＋</span><strong><?= h(tr('Lägg till bilder och videor från besöket')) ?></strong><small><?= h(tr('JPG, PNG, WebP: 25 MB per bild · MP4, WebM, MOV: 200 MB per video · 500 MB per gång · högst %d filer per recension', MAX_MEDIA_PER_REVIEW)) ?></small><input type="file" name="media[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime,.mov" multiple data-photo-input data-existing-count="<?= $editing ? count($photosByReview[(int)$editing['id']] ?? []) : 0 ?>"></label>
        <div class="preview-grid" id="upload-preview" aria-live="polite"></div>
        <div class="form-bottom"><button class="btn btn-primary" type="submit"><?= h(tr($editing ? 'Spara ändringar' : 'Publicera min recension')) ?> ↗</button><?php if ($editing): ?><a href="<?= h(app_url('index.php', ['m'=>$slug])) ?>#skriv-recension" class="text-link"><?= h(tr('Avbryt redigering')) ?></a><?php endif; ?></div>
      </form>
    </div>
  <?php elseif (!is_installed()): ?>
    <div class="soft-callout"><?= h(tr('Första besöket här?')) ?> <a href="<?= h(app_url('admin.php')) ?>"><?= h(tr('Skapa ditt adminkonto')) ?></a> <?= h(tr('för att lägga till recensioner, bilder och videor.')) ?></div>
  <?php endif; ?>
  <?php if (!$reviews): ?><div class="review-empty"><span>✧</span><h3><?= h(tr('Inga recensioner ännu')) ?></h3><p><?= h(tr(is_admin() ? 'Skriv den första recensionen ovan.' : 'Recensioner, bilder och videor visas här när de läggs till.')) ?></p></div><?php endif; ?>
  <div class="review-list">
    <?php foreach ($reviews as $r): $photos = $photosByReview[(int)$r['id']] ?? []; ?>
      <article class="review-card" id="review-<?= (int)$r['id'] ?>">
        <div class="review-card-top"><div class="review-date"><span class="review-avatar" aria-hidden="true"><img src="<?= h(app_url('cmm_logga.png')) ?>" alt="" width="1930" height="267" decoding="async"></span><div><strong><?= h(tr('Marknadsdagboken')) ?></strong><small><?= h($r['visited_on'] ? date_se($r['visited_on']) : date_se($r['created_at'])) ?></small></div></div><span class="review-stars" aria-label="<?= (int)$r['rating'] ?> <?= h(tr('av 5 stjärnor')) ?>"><?= stars((int)$r['rating']) ?></span></div>
        <h3><?= h($r['display_title'] ?? $r['title']) ?></h3><div class="review-copy"><?= nl2br(h($r['display_body'] ?? $r['body'])) ?></div>
        <?php if ($photos): ?><div class="photo-grid"><?php foreach ($photos as $p): $isVideo = str_starts_with($p['mime'], 'video/'); ?><div class="photo-tile<?= $isVideo ? ' video-tile' : '' ?>"><a href="<?= h(app_url('photo.php', ['id'=>(int)$p['id']])) ?>" target="_blank" rel="noopener" data-gallery-item data-media-type="<?= $isVideo ? 'video' : 'image' ?>" data-caption="<?= h($r['display_title'] ?? $r['title']) ?>"<?= $isVideo ? ' aria-label="' . h(tr('Spela video från %s', $market['name'])) . '"' : '' ?>><?php if ($isVideo): ?><span class="video-thumb" aria-hidden="true"><span class="video-play">▶</span><small>VIDEO</small></span><?php else: ?><img src="<?= h(app_url('photo.php', ['id'=>(int)$p['id']])) ?>" loading="lazy" alt="<?= h(tr('Foto från %s', $market['name'])) ?>"><?php endif; ?></a><?php if (is_admin()): ?><form method="post" action="<?= h(app_url('action.php')) ?>" onsubmit="return confirm(<?= h(json_encode(tr('Ta bort den här filen?'), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete_photo"><input type="hidden" name="market_slug" value="<?= h($slug) ?>"><input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>"><button type="submit" title="<?= h(tr('Ta bort filen')) ?>" aria-label="<?= h(tr('Ta bort filen')) ?>">×</button></form><?php endif; ?></div><?php endforeach; ?></div><?php endif; ?>
        <?php if (is_admin()): ?><div class="review-tools"><a href="<?= h(app_url('index.php', ['m'=>$slug,'edit'=>(int)$r['id']])) ?>#skriv-recension">✎ <?= h(tr('Redigera')) ?></a><form action="<?= h(app_url('action.php')) ?>" method="post" onsubmit="return confirm(<?= h(json_encode(tr('Vill du radera hela recensionen och alla dess bilder och videor?'), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete_review"><input type="hidden" name="market_slug" value="<?= h($slug) ?>"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>"><button type="submit" class="delete-action"><?= h(tr('Radera')) ?></button></form></div><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
  <a class="bottom-back" href="<?= h(app_url()) ?>">← <?= h(tr('Tillbaka till alla marknader')) ?></a>
</section>
<dialog class="photo-modal" id="market-photo-modal" aria-label="<?= h(tr('Bildvisare')) ?>">
  <div class="photo-modal-inner">
    <button class="photo-modal-close" type="button" data-gallery-close aria-label="<?= h(tr('Stäng bildvisaren')) ?>">×</button>
    <img class="photo-modal-image" alt="">
    <video class="photo-modal-video" controls playsinline preload="metadata" hidden></video>
    <div class="photo-modal-bottom"><button type="button" data-gallery-prev aria-label="<?= h(tr('Föregående bild')) ?>">←</button><span class="photo-modal-caption"></span><span class="photo-modal-count" aria-live="polite"></span><button type="button" data-gallery-next aria-label="<?= h(tr('Nästa bild')) ?>">→</button></div>
  </div>
</dialog>
<?php view('footer.php'); ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/locations.php';

$locations = market_locations();
$markers = [];
foreach (market_catalog() as $market) {
    $slug = $market['slug'];
    if (!isset($locations[$slug])) continue;
    $image = market_image($slug);
    $markers[] = [
        'slug' => $slug,
        'name' => $market['name'],
        'area' => $market['area'],
        'category' => $market['category'],
        'schedule' => $market['schedule'],
        'lat' => $locations[$slug][0],
        'lng' => $locations[$slug][1],
        'approximate' => $locations[$slug][2],
        'image' => $image ? app_url($image['file']) : '',
        'url' => app_url('index.php', ['m' => $slug]),
    ];
}
view('header.php', ['title' => tr('Kartvy'), 'mapPage' => true]);
?>
<section class="shell map-page">
  <div class="map-heading"><div><h1><?= h(tr('Marknader på kartan')) ?></h1><p><?= h(tr('Sök i listan eller välj en markör.')) ?></p></div><a href="<?= h(app_url()) ?>#market-list" class="map-back">← <?= h(tr('Lista')) ?></a></div>
  <div class="map-toolbar">
    <label class="map-search"><span aria-hidden="true">⌕</span><input type="search" id="map-search" placeholder="<?= h(tr('Sök marknad eller område')) ?>" aria-label="<?= h(tr('Sök marknad på kartan')) ?>" autocomplete="off"></label>
    <div class="map-filters" aria-label="<?= h(tr('Filtrera kartans marknader')) ?>">
      <?php foreach (['Alla', 'Dagmarknad', 'Nattmarknad', 'Helgmarknad', 'Kvartersmarknad', 'Specialmarknad'] as $index => $category): ?>
        <button type="button" class="map-filter<?= $index === 0 ? ' selected' : '' ?>" data-category="<?= h(tr($category)) ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"><?= h(tr($category)) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="map-layout">
    <aside class="map-sidebar" aria-label="<?= h(tr('Marknadslista')) ?>">
      <div class="map-sidebar-head"><strong><?= h(tr('Marknader')) ?></strong><span id="map-count"><?= count($markers) ?> <?= h(tr('platser')) ?></span></div>
      <div class="map-list" id="map-list">
        <?php foreach ($markers as $marker): ?>
          <article class="map-list-item" data-slug="<?= h($marker['slug']) ?>" data-category="<?= h($marker['category']) ?>" data-search="<?= h(mb_safe_lower($marker['name'] . ' ' . $marker['area'] . ' ' . $marker['category'])) ?>">
            <button type="button" class="map-list-focus" data-focus="<?= h($marker['slug']) ?>" aria-label="<?= h(tr('Visa %s på kartan', $marker['name'])) ?>">
              <img src="<?= h($marker['image']) ?>" alt="" loading="lazy" width="76" height="76">
              <span><strong><?= h($marker['name']) ?></strong><small><?= h($marker['area']) ?> · <?= h($marker['category']) ?></small><?php if ($marker['approximate']): ?><small class="map-approx"><?= h(tr('Ungefärlig plats')) ?></small><?php endif; ?></span>
            </button>
            <a href="<?= h($marker['url']) ?>" class="map-list-link" aria-label="<?= h(tr('Öppna %s', $marker['name'])) ?>">↗</a>
          </article>
        <?php endforeach; ?>
      </div>
      <p id="map-empty" class="map-empty" hidden><?= h(tr('Inga marknader matchar sökningen.')) ?></p>
    </aside>
    <div id="market-map" class="market-map" role="region" aria-label="<?= h(tr('Interaktiv karta över marknader i Chiang Mai')) ?>"></div>
  </div>
  <p class="map-note"><?= h(tr('Kartnålar för stråk, popupmarknader och områden kan vara ungefärliga. Kontrollera adress och öppettider innan besök. Kartdata ©')) ?> <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>.</p>
  <noscript><p class="map-note"><?= h(tr('Kartan kräver JavaScript. Alla marknader finns i listan ovan.')) ?></p></noscript>
</section>
<script type="application/json" id="map-translations"><?= json_encode(['approximate'=>tr('Ungefärlig plats'),'view'=>tr('Visa marknaden ↗'),'place'=>tr('plats'),'places'=>tr('platser')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="application/json" id="market-map-data"><?= json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php view('footer.php'); ?>

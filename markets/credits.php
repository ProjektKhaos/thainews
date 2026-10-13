<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$images = market_images();
view('header.php', ['title' => tr('Bildkällor och licenser')]);
?>
<section class="shell credits-page">
  <div class="breadcrumbs"><a href="<?= h(app_url()) ?>">← <?= h(tr('Alla marknader')) ?></a><span>/</span><span><?= h(tr('Bildkällor')) ?></span></div>
  <h1><?= h(tr('Bildkällor och licenser')) ?></h1>
  <p><?= h(tr('Platsfoto visar den namngivna marknaden. Stämningsbilder visar en annan marknad eller ett marknadsmotiv i Chiang Mai. Bilderna har konverterats till WebP och kan beskäras vid visning.')) ?></p>
  <div class="credits-grid">
    <?php foreach (market_catalog() as $market): $image = $images[$market['slug']] ?? null; if (!$image) continue; ?>
      <article class="credit-card">
        <img src="<?= h(app_url($image['file'])) ?>" alt="" loading="lazy" width="320" height="200">
        <div><span class="credit-kind"><?= h(tr($image['exact'] ? 'Platsfoto' : 'Stämningsbild')) ?></span>
          <h2><?= h($market['name']) ?></h2>
          <p><?= h(tr('Foto:')) ?> <?= h($image['artist']) ?></p>
          <p><a href="<?= h($image['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= h(tr('Original och upphovsman ↗')) ?></a>
          <?php if ($image['license_url']): ?> · <a href="<?= h($image['license_url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($image['license']) ?> ↗</a><?php else: ?> · <?= h($image['license']) ?><?php endif; ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php view('footer.php'); ?>

<?php
// Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | Version 1.00 | av: KlⒶssⓔ & Ⓐberg
/** @var string $title */
$displayTitle = $title ?? 'Marknader i Chiang Mai';
$logoUrl = app_url('cmm_logga.png');
$socialLogoUrl = 'https://markets.aberg.online' . app_url('cmm-og.png');
?>
<!doctype html>
<html lang="<?= h(market_language()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#18312e">
  <meta name="description" content="<?= h(tr('Upptäck marknaderna i Chiang Mai och dokumentera egna besök med recensioner, stjärnor och bilder.')) ?>">
  <title><?= h(tr($displayTitle)) ?> · Chiang Mai Markets</title>
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Chiang Mai Markets Developed by Hans Åberg">
  <meta property="og:title" content="Chiang Mai Markets Developed by Hans Åberg">
  <meta property="og:description" content="<?= h(tr('Upptäck marknaderna i Chiang Mai och dokumentera egna besök med recensioner, stjärnor och bilder.')) ?>">
  <meta property="og:image" content="<?= h($socialLogoUrl) ?>">
  <meta property="og:image:secure_url" content="<?= h($socialLogoUrl) ?>">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1733">
  <meta property="og:image:height" content="907">
  <meta property="og:image:alt" content="Chiang Mai Markets">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:image" content="<?= h($socialLogoUrl) ?>">
  <script>try{const saved=localStorage.getItem('cm-markets-theme');document.documentElement.dataset.theme=saved==='light'||saved==='dark'?saved:(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light')}catch(e){document.documentElement.dataset.theme='light'}</script>
  <link rel="stylesheet" href="<?= h(app_url('assets/style.css')) ?>?v=1.00">
  <link rel="stylesheet" href="<?= h(app_url('assets/market-images.css')) ?>?v=1.01">
  <?php if (!empty($mapPage)): ?>
    <link rel="stylesheet" href="<?= h(app_url('assets/vendor/leaflet/leaflet.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('assets/map.css')) ?>?v=1.00">
    <script src="<?= h(app_url('assets/vendor/leaflet/leaflet.js')) ?>" defer></script>
    <script src="<?= h(app_url('assets/map.js')) ?>?v=1.01" defer></script>
  <?php endif; ?>
  <link rel="stylesheet" href="<?= h(app_url('assets/terminal.css')) ?>?v=1.14">
  <script src="<?= h(app_url('assets/app.js')) ?>?v=1.03" defer></script>
  <?php if (!empty($photoModal)): ?><script src="<?= h(app_url('assets/photo-modal.js')) ?>?v=1.01" defer></script><?php endif; ?>
  <script src="<?= h(app_url('assets/theme.js')) ?>?v=1.01" defer></script>
  <script type="application/json" id="market-ui-translations"><?= json_encode(['one'=>tr('marknad'),'many'=>tr('marknader'),'upload'=>tr('Högst %d bilder och videor per recension.', MAX_MEDIA_PER_REVIEW),'total'=>tr('Uppladdningen är för stor. Högst 500 MB per gång.'),'image'=>tr('En bild får vara högst 25 MB.'),'video'=>tr('En video får vara högst 200 MB.'),'previewMore'=>tr('Visar de första 12 förhandsvisningarna. Alla valda filer laddas upp.'),'maxMedia'=>MAX_MEDIA_PER_REVIEW,'maxImageBytes'=>MAX_IMAGE_BYTES,'maxVideoBytes'=>MAX_VIDEO_BYTES,'maxUploadBytes'=>MAX_UPLOAD_TOTAL_BYTES], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</head>
<body>
<a class="skip-link" href="#main"><?= h(tr('Hoppa till innehållet')) ?></a>
<header class="site-header">
  <div class="shell nav-bar">
    <a class="brand" href="<?= h(app_url()) ?>" aria-label="<?= h(tr('Tillbaka till alla marknader')) ?>">
      <img class="brand-logo" src="<?= h($logoUrl) ?>" alt="Chiang Mai Markets" width="1930" height="267" decoding="async">
    </a>
    <nav class="nav-links" aria-label="Huvudmeny">
      <a href="<?= h(app_url()) ?>"><?= h(tr('Marknader')) ?></a>
      <a href="<?= h(app_url('map.php')) ?>"><?= h(tr('Karta')) ?></a>
      <?php if (is_admin()): ?>
        <a class="nav-pill" href="<?= h(app_url('admin.php')) ?>"><?= h(tr('Admin')) ?></a>
      <?php else: ?>
        <a class="nav-pill" href="<?= h(app_url('admin.php')) ?>"><?= h(tr('Logga in')) ?></a>
      <?php endif; ?>
    </nav>
    <div class="header-controls"><div class="language-switcher" aria-label="Language / Språk"><a href="<?= h(market_language_url('sv')) ?>" hreflang="sv" lang="sv"<?= market_language() === 'sv' ? ' aria-current="page"' : '' ?>>SV</a><a href="<?= h(market_language_url('en')) ?>" hreflang="en" lang="en"<?= market_language() === 'en' ? ' aria-current="page"' : '' ?>>EN</a></div><button class="theme-toggle" type="button" aria-pressed="false" aria-label="<?= h(tr('Mörkt tema')) ?>" data-light-label="<?= h(tr('Ljust tema')) ?>" data-dark-label="<?= h(tr('Mörkt tema')) ?>"><span class="theme-icon" aria-hidden="true">☾</span><span class="theme-label"><?= h(tr('Mörkt tema')) ?></span></button></div>
  </div>
</header>
<?php if ($flash = take_flash()): ?>
  <div class="shell"><div class="notice <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= h($flash['message']) ?></div></div>
<?php endif; ?>
<main id="main">

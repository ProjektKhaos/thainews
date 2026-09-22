<?php
// Senast uppdaterad: 2026-09-21 | Shared header with right-aligned language flags
/** @var array<string,mixed> $meta */
$nonce=$GLOBALS['tn_csp_nonce']??'';
$displayPreferences=$GLOBALS['tn_preferences']??['font_family'=>'inter','font_size'=>'medium'];
?><!doctype html>
<html lang="<?= e(translator()->language()) ?>" data-font="<?= e((string)$displayPreferences['font_family']) ?>" data-font-size="<?= e((string)$displayPreferences['font_size']) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#083b7d">
<meta name="application-name" content="Thai News">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Thai News">
<title><?= e((string)$meta['title']) ?></title>
<meta name="description" content="<?= e((string)$meta['description']) ?>">
<link rel="canonical" href="<?= e((string)$meta['canonical']) ?>">
<link rel="icon" href="<?= e(asset_url('img/thainews_logo1.png')) ?>" type="image/png">
<link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset_url('img/pwa/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
<meta property="og:site_name" content="Thai News"><meta property="og:type" content="<?= e((string)$meta['type']) ?>">
<meta property="og:title" content="<?= e((string)$meta['title']) ?>"><meta property="og:description" content="<?= e((string)$meta['description']) ?>">
<meta property="og:url" content="<?= e((string)$meta['canonical']) ?>"><meta property="og:image" content="<?= e((string)$meta['image']) ?>"><meta property="og:image:secure_url" content="<?= e((string)$meta['image']) ?>"><meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="<?= (int)$meta['image_width'] ?>"><meta property="og:image:height" content="<?= (int)$meta['image_height'] ?>"><meta property="og:image:alt" content="<?= e((string)$meta['image_alt']) ?>">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?= e((string)$meta['title']) ?>"><meta name="twitter:description" content="<?= e((string)$meta['description']) ?>"><meta name="twitter:image" content="<?= e((string)$meta['image']) ?>"><meta name="twitter:image:alt" content="<?= e((string)$meta['image_alt']) ?>">
<script nonce="<?= e($nonce) ?>">(()=>{try{const saved=localStorage.getItem('thai-news-theme');const theme=saved==='dark'||saved==='light'?saved:(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=theme}catch(error){}})();</script>
<link rel="stylesheet" href="<?= e(asset_url('assets/css/site.css')) ?>">
<script src="<?= e(asset_url('assets/js/site.js')) ?>" defer></script>
<script src="<?= e(asset_url('assets/js/tour.js')) ?>" defer></script>
<script type="application/ld+json" nonce="<?= e($nonce) ?>"><?= json_encode(['@context'=>'https://schema.org','@type'=>'NewsMediaOrganization','name'=>'Thai News','url'=>absolute_url(''),'logo'=>absolute_url('img/logo_trans.png')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP) ?></script>
</head><body>
<header class="site-header">
<a class="brand" href="<?= e(url()) ?>"><img src="<?= e(asset_url('img/logo_trans.png')) ?>" alt="Thai News" width="1893" height="422" fetchpriority="high"></a>
<nav><a href="<?= e(url()) ?>"><?= e(t('nav.home')) ?></a><a href="<?= e(url('settings.php')) ?>"><?= e(t('nav.settings')) ?></a></nav>
<div class="language-switcher" aria-label="<?= e(t('nav.language')) ?>">
<a href="?lang=sv" aria-label="Svenska"<?= translator()->language()==='sv'?' aria-current="page"':'' ?>><img class="flag-icon" src="<?= e(asset_url('img/flags/se.svg')) ?>" alt="" width="24" height="15"></a>
<a href="?lang=en" aria-label="English"<?= translator()->language()==='en'?' aria-current="page"':'' ?>><img class="flag-icon" src="<?= e(asset_url('img/flags/gb.svg')) ?>" alt="" width="24" height="12"></a>
<a href="?lang=th" aria-label="ไทย"<?= translator()->language()==='th'?' aria-current="page"':'' ?>><img class="flag-icon" src="<?= e(asset_url('img/flags/th.svg')) ?>" alt="" width="24" height="16"></a>
</div>
</header><main class="page-shell">

<?php
// Senast uppdaterad: 2026-09-22 | First-visit tours, PWA install controls and site credit
$scriptName=basename((string)($_SERVER['SCRIPT_NAME']??''));
$tourSteps=$scriptName==='settings.php' ? [
 ['selector'=>'.theme-setting','title'=>t('tour.settings_theme_title'),'body'=>t('tour.settings_theme_body')],
 ['selector'=>'.settings-panel fieldset:first-of-type','title'=>t('tour.settings_language_title'),'body'=>t('tour.settings_language_body')],
 ['selector'=>'.typography-settings','title'=>t('tour.settings_typography_title'),'body'=>t('tour.settings_typography_body')],
 ['selector'=>'.source-sort li:first-child','title'=>t('tour.settings_sources_title'),'body'=>t('tour.settings_sources_body')],
 ['selector'=>'.settings-panel .primary','title'=>t('tour.settings_save_title'),'body'=>t('tour.settings_save_body')],
] : [
 ['selector'=>'.brand','title'=>t('tour.home_welcome_title'),'body'=>t('tour.home_welcome_body')],
 ['selector'=>'.language-switcher','title'=>t('tour.home_language_title'),'body'=>t('tour.home_language_body')],
 ['selector'=>'.source-heading','title'=>t('tour.home_sources_title'),'body'=>t('tour.home_sources_body')],
 ['selector'=>'.news-item','title'=>t('tour.home_articles_title'),'body'=>t('tour.home_articles_body')],
 ['selector'=>'.site-header nav a[href*="settings.php"]','title'=>t('tour.home_settings_title'),'body'=>t('tour.home_settings_body')],
];
$tourConfig=['id'=>$scriptName==='settings.php'?'settings-v1':'home-v1','counter'=>t('tour.counter'),'steps'=>$tourSteps];
$tourNonce=$GLOBALS['tn_csp_nonce']??'';
?>
</main>
<div class="tour-layer" id="tour-layer" hidden>
<div class="tour-spotlight" id="tour-spotlight" aria-hidden="true"></div>
<section class="tour-dialog" id="tour-dialog" role="dialog" aria-modal="true" aria-labelledby="tour-title" aria-describedby="tour-body" tabindex="-1">
<p class="tour-counter" id="tour-counter"></p>
<h2 id="tour-title"></h2>
<p id="tour-body"></p>
<div class="tour-actions">
<button class="tour-skip" id="tour-skip" type="button"><?= e(t('tour.skip')) ?></button>
<span class="tour-spacer"></span>
<button class="tour-back" id="tour-back" type="button"><?= e(t('tour.back')) ?></button>
<button class="tour-next" id="tour-next" type="button" data-next-label="<?= e(t('tour.next')) ?>" data-done-label="<?= e(t('tour.done')) ?>"><?= e(t('tour.next')) ?></button>
</div>
</section>
</div>
<script type="application/json" id="tour-config" nonce="<?= e($tourNonce) ?>"><?= json_encode($tourConfig,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP) ?></script>
<aside class="pwa-install" id="pwa-install" aria-live="polite">
<button class="pwa-install-button" id="pwa-install-button" type="button" hidden><?= e(t('pwa.install')) ?></button>
<p class="pwa-ios-hint" id="pwa-ios-hint" hidden><?= e(t('pwa.ios_hint')) ?></p>
</aside>
<footer class="site-footer">Thai News — Developed by Hans Åberg 2026 — Relayworks</footer></body></html>

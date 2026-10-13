<?php
// Senast uppdaterad 2026-10-12 (Asia/Bangkok)
declare(strict_types=1);
require __DIR__ . '/../access.php';
cosmos_require_access(2);
require_once __DIR__ . '/../app/Cms.php';
header('Content-Type: text/html; charset=utf-8');
ob_start();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <script>if (location.hash === '#top') history.replaceState(null, '', location.pathname + location.search);else { const oldPages = {'#home':'house','#gallery':'gallery','#experience':'experience','#location':'location','#book':'book'}; const target = oldPages[location.hash]; if (target) location.replace(new URL(target + '/', location.href)); }</script>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#f7f5f0">
  <meta name="description" content="Upptäck Cosmos House – ett ljust hem med takterrass i Chiang Mai, Thailand. Utforska bilder och upptäck huset.">
  <title>Your place in Chiang Mai | Cosmos House</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header class="site-header" id="top">
  <a class="brand" href="../sv/"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a>
  <nav class="nav" aria-label="Huvudmeny">
    <a href="../sv/house/" >Hemmet</a>
    <a href="../sv/gallery/" >Galleri</a>
    <a href="../sv/experience/" >Upplevelsen</a>
    <a href="../sv/location/" >Chiang Mai</a>
    <div class="language-switch" role="group" aria-label="Språk">
      <a href="../en/" hreflang="en" lang="en" aria-label="English" ><img class="flag-icon" src="../assets/flags/gb.svg" alt="" width="24" height="16"> Eng</a>
      <a href="../th/" hreflang="th" lang="th" aria-label="ภาษาไทย" ><img class="flag-icon" src="../assets/flags/th.svg" alt="" width="24" height="16"> Thai</a>
      <a href="../sv/" hreflang="sv" lang="sv" aria-label="Svenska" aria-current="true"><img class="flag-icon" src="../assets/flags/se.svg" alt="" width="24" height="16"> Swe</a>
    </div>
  </nav>
  <a href="../sv/book/" class="header-cta" >Planera din vistelse <span>↗</span></a>
  <button class="menu-toggle" aria-label="Öppna menyn" aria-expanded="false">☰</button>
</header>
<main>
    <section class="hero" aria-labelledby="hero-title">
    <div class="hero-image"></div><div class="hero-shade"></div>
    <div class="hero-content">
      <div class="eyebrow light"><span class="dot"></span> EN PLATS ATT LANDA · CHIANG MAI, THAILAND</div>
      <h1 id="hero-title" data-cms="copy-01">Make room<br>for <i>living.</i></h1>
      <p data-cms="copy-02">Ett ljust och personligt hem i Chiang Mai. Högt i tak, sköna ytor och en takterrass för de där stunderna som får tiden att sakta ner.</p>
      <p class="sample-copy" data-cms="copy-03">Exempeltext: Här kan en längre välkomsthälsning berätta om känslan i huset, vad gäster kan vänta sig av vistelsen och varför platsen passar för både lugna dagar och utflykter. Texten är en skiss tills innehållet har bekräftats.</p>
      <div class="hero-buttons"><a class="btn cream" href="../sv/book/">Upptäck din vistelse <span>↗</span></a><a class="btn ghost" href="../sv/house/">Utforska huset <span>↗</span></a></div>
    </div>
    <div class="hero-bottom"><span>18°47′ N · 98°59′ E</span><span>SCROLL TO EXPLORE ↓</span><span>01 / COSMOS HOUSE</span></div>
  </section>
  <section class="page-directory section" aria-label="Utforska Cosmos House">
    <div class="container"><div class="eyebrow"><span class="tinyline"></span> COSMOS HOUSE</div><h2 data-cms="copy-04">Utforska Cosmos House</h2>
      <div class="page-directory-grid">
                  <a class="page-tile page-tile-house" href="../sv/house/"><span>Hemmet</span><b aria-hidden="true">↗</b></a>
                  <a class="page-tile page-tile-gallery" href="../sv/gallery/"><span>Galleri</span><b aria-hidden="true">↗</b></a>
                  <a class="page-tile page-tile-experience" href="../sv/experience/"><span>Upplevelsen</span><b aria-hidden="true">↗</b></a>
                  <a class="page-tile page-tile-location" href="../sv/location/"><span>Chiang Mai</span><b aria-hidden="true">↗</b></a>
                  <a class="page-tile page-tile-book" href="../sv/book/"><span>Bokning</span><b aria-hidden="true">↗</b></a>
              </div>
    </div>
  </section>
            </main>
<footer><div class="container footer-top"><div><a href="../sv/" class="brand footer-brand"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a><p data-cms="copy-05">Come as you are.<br>Stay as long as you like.</p></div><div><span>EXPLORE</span><a href="../sv/house/">Om huset</a><a href="../sv/gallery/">Galleri</a><a href="../sv/experience/">Upplevelsen</a><a href="../sv/location/">Chiang Mai</a><a href="../sv/book/">Bokning</a></div><div><span>FIND US</span><p data-cms="copy-06">Chiang Mai<br>Thailand</p></div></div><div class="container footer-bottom"><span>© 2026 COSMOS HOUSE</span><span>MADE FOR SLOWER MOMENTS ✳</span><a href="../admin.php">Admin</a></div></footer>
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Bildgalleri" hidden><div class="lightbox-top"><span>COSMOS HOUSE · GALLERY</span><button id="closeLightbox" aria-label="Stäng galleri">✕</button></div><div class="lightbox-body"><button id="prevPhoto" aria-label="Föregående bild">‹</button><figure><img id="lightboxImg" alt=""><figcaption id="lightboxCaption"></figcaption></figure><button id="nextPhoto" aria-label="Nästa bild">›</button></div><div class="lightbox-counter" id="lightboxCounter"></div><div class="lightbox-filters" id="lightboxFilters"><button data-filter="all" class="active">Alla bilder</button><button data-filter="living">Sällskapsytor</button><button data-filter="bedroom">Sovrum</button><button data-filter="terrace">Terrass</button><button data-filter="details">Detaljer</button></div><div class="lightbox-thumbs" id="lightboxThumbs"></div></div>
<script>window.COSMOS={gallery:[{"src":"assets\/photos\/photo-01.webp","label":"Takterrass","category":"terrace"},{"src":"assets\/photos\/photo-02.webp","label":"Vardagsrum","category":"living"},{"src":"assets\/photos\/photo-03.webp","label":"Huset kvällstid","category":"details"},{"src":"assets\/photos\/photo-04.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-05.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-06.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-07.webp","label":"Matplats","category":"living"},{"src":"assets\/photos\/photo-08.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-09.webp","label":"Sällskapsrum","category":"bedroom"},{"src":"assets\/photos\/photo-10.webp","label":"Terrass","category":"terrace"},{"src":"assets\/photos\/photo-11.webp","label":"Takterrass","category":"terrace"},{"src":"assets\/photos\/photo-12.webp","label":"Hårtork","category":"details"},{"src":"assets\/photos\/photo-13.webp","label":"Förvaring","category":"details"},{"src":"assets\/photos\/photo-14.webp","label":"Värdeskåp","category":"details"},{"src":"assets\/photos\/photo-15.webp","label":"Tvättmaskin","category":"details"},{"src":"assets\/photos\/photo-16.webp","label":"Entré","category":"details"},{"src":"assets\/photos\/photo-17.webp","label":"Detaljer","category":"details"},{"src":"assets\/photos\/photo-18.webp","label":"Trädgårdsutsikt","category":"terrace"},{"src":"assets\/photos\/photo-19.webp","label":"Kök","category":"living"},{"src":"assets\/photos\/photo-20.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-21.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-22.webp","label":"Huset","category":"details"},{"src":"assets\/photos\/photo-23.webp","label":"Öppen planlösning","category":"living"},{"src":"assets\/photos\/photo-24.webp","label":"Matplats","category":"living"},{"src":"assets\/photos\/photo-25.webp","label":"Badrum","category":"details"},{"src":"assets\/photos\/photo-26.webp","label":"Takterrass","category":"terrace"},{"src":"assets\/photos\/photo-27.webp","label":"Vardagsrum","category":"living"},{"src":"assets\/photos\/photo-28.webp","label":"Badrum","category":"details"},{"src":"assets\/photos\/photo-29.webp","label":"Vardagsrum","category":"living"},{"src":"assets\/photos\/photo-30.webp","label":"TV","category":"living"},{"src":"assets\/photos\/photo-31.webp","label":"Soffhörna","category":"living"},{"src":"assets\/photos\/photo-32.webp","label":"Trappa","category":"details"},{"src":"assets\/photos\/photo-33.webp","label":"Trappa","category":"details"},{"src":"assets\/photos\/photo-34.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-35.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-36.webp","label":"Sällskapsrum","category":"bedroom"},{"src":"assets\/photos\/photo-37.webp","label":"Värdeskåp","category":"details"},{"src":"assets\/photos\/photo-38.webp","label":"Entré","category":"details"}],base:"..",viewPhotoPrefix:"Visa ",menuOpenLabel:"Öppna menyn",menuCloseLabel:"Stäng menyn"};</script>
<script src="../assets/app.js" defer></script>
</body>
</html>
<?php echo ob_get_clean(); ?>

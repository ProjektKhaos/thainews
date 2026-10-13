<?php
// Senast uppdaterad 2026-10-12 (Asia/Bangkok)
declare(strict_types=1);
require __DIR__ . '/../../access.php';
cosmos_require_access(3);
require_once __DIR__ . '/../../app/Cms.php';
header('Content-Type: text/html; charset=utf-8');
ob_start();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <script>if (location.hash === '#top') history.replaceState(null, '', location.pathname + location.search);</script>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#f7f5f0">
  <meta name="description" content="Upptäck Cosmos House – ett ljust hem med takterrass i Chiang Mai, Thailand. Utforska bilder och upptäck huset.">
  <title>Om huset | Cosmos House</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<header class="site-header" id="top">
  <a class="brand" href="../../sv/"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a>
  <nav class="nav" aria-label="Huvudmeny">
    <a href="../../sv/house/" aria-current="page">Hemmet</a>
    <a href="../../sv/gallery/" >Galleri</a>
    <a href="../../sv/experience/" >Upplevelsen</a>
    <a href="../../sv/location/" >Chiang Mai</a>
    <div class="language-switch" role="group" aria-label="Språk">
      <a href="../../en/house/" hreflang="en" lang="en" aria-label="English" ><img class="flag-icon" src="../../assets/flags/gb.svg" alt="" width="24" height="16"> Eng</a>
      <a href="../../th/house/" hreflang="th" lang="th" aria-label="ภาษาไทย" ><img class="flag-icon" src="../../assets/flags/th.svg" alt="" width="24" height="16"> Thai</a>
      <a href="../../sv/house/" hreflang="sv" lang="sv" aria-label="Svenska" aria-current="true"><img class="flag-icon" src="../../assets/flags/se.svg" alt="" width="24" height="16"> Swe</a>
    </div>
  </nav>
  <a href="../../sv/book/" class="header-cta" >Planera din vistelse <span>↗</span></a>
  <button class="menu-toggle" aria-label="Öppna menyn" aria-expanded="false">☰</button>
</header>
<main>
    <div class="subpage-banner subpage-house"><div class="container"><div class="eyebrow light"><span class="dot"></span> COSMOS HOUSE · Chiang Mai</div><h1 data-cms="copy-01">Om huset</h1></div></div>
      <section class="intro section" id="home">
    <div class="container intro-grid"><div><div class="eyebrow"><span class="tinyline"></span> WELCOME TO COSMOS</div><h2 data-cms="copy-02">Not just a place<br>to stay. <i>A place to be.</i></h2></div>
      <div class="intro-copy"><p data-cms="copy-03">Det är något särskilt med att ha en egen plats i en ny stad. Cosmos House bjuder in till lugna morgnar, långsamma kvällar och friheten att känna sig hemma – långt hemifrån.</p><p class="sample-copy" data-cms="copy-04">Exempeltext: Den här introduktionen kan senare få mer utrymme för husets bakgrund, planlösning och sådant som gör vardagen enkel under en vistelse. Här provar vi ett längre textflöde för att se hur rubrik, brödtext och de små informationsrutorna samspelar på olika skärmar.</p>
        <div class="feature-strip">
          <div><span>✳</span><strong data-cms="copy-05">Ljust &amp; luftigt</strong><small data-cms="copy-06">Öppna sällskapsytor</small></div>
          <div><span>☀</span><strong data-cms="copy-07">Takterrass</strong><small data-cms="copy-08">Utrymme för avkoppling</small></div>
          <div><span>⌂</span><strong data-cms="copy-09">Eget hem</strong><small data-cms="copy-10">Plats att landa</small></div>
        </div>
      </div>
    </div>
  </section>
          </main>
<footer><div class="container footer-top"><div><a href="../../sv/" class="brand footer-brand"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a><p data-cms="copy-11">Come as you are.<br>Stay as long as you like.</p></div><div><span>EXPLORE</span><a href="../../sv/house/">Om huset</a><a href="../../sv/gallery/">Galleri</a><a href="../../sv/experience/">Upplevelsen</a><a href="../../sv/location/">Chiang Mai</a><a href="../../sv/book/">Bokning</a></div><div><span>FIND US</span><p data-cms="copy-12">Chiang Mai<br>Thailand</p></div></div><div class="container footer-bottom"><span>© 2026 COSMOS HOUSE</span><span>MADE FOR SLOWER MOMENTS ✳</span><a href="../../admin.php">Admin</a></div></footer>
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Bildgalleri" hidden><div class="lightbox-top"><span>COSMOS HOUSE · GALLERY</span><button id="closeLightbox" aria-label="Stäng galleri">✕</button></div><div class="lightbox-body"><button id="prevPhoto" aria-label="Föregående bild">‹</button><figure><img id="lightboxImg" alt=""><figcaption id="lightboxCaption"></figcaption></figure><button id="nextPhoto" aria-label="Nästa bild">›</button></div><div class="lightbox-counter" id="lightboxCounter"></div><div class="lightbox-filters" id="lightboxFilters"><button data-filter="all" class="active">Alla bilder</button><button data-filter="living">Sällskapsytor</button><button data-filter="bedroom">Sovrum</button><button data-filter="terrace">Terrass</button><button data-filter="details">Detaljer</button></div><div class="lightbox-thumbs" id="lightboxThumbs"></div></div>
<script>window.COSMOS={gallery:[{"src":"assets\/photos\/photo-01.webp","label":"Takterrass","category":"terrace"},{"src":"assets\/photos\/photo-02.webp","label":"Vardagsrum","category":"living"},{"src":"assets\/photos\/photo-03.webp","label":"Huset kvällstid","category":"details"},{"src":"assets\/photos\/photo-04.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-05.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-06.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-07.webp","label":"Matplats","category":"living"},{"src":"assets\/photos\/photo-08.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-09.webp","label":"Sällskapsrum","category":"bedroom"},{"src":"assets\/photos\/photo-10.webp","label":"Terrass","category":"terrace"},{"src":"assets\/photos\/photo-11.webp","label":"Takterrass","category":"terrace"},{"src":"assets\/photos\/photo-12.webp","label":"Hårtork","category":"details"},{"src":"assets\/photos\/photo-13.webp","label":"Förvaring","category":"details"},{"src":"assets\/photos\/photo-14.webp","label":"Värdeskåp","category":"details"},{"src":"assets\/photos\/photo-15.webp","label":"Tvättmaskin","category":"details"},{"src":"assets\/photos\/photo-16.webp","label":"Entré","category":"details"},{"src":"assets\/photos\/photo-17.webp","label":"Detaljer","category":"details"},{"src":"assets\/photos\/photo-18.webp","label":"Trädgårdsutsikt","category":"terrace"},{"src":"assets\/photos\/photo-19.webp","label":"Kök","category":"living"},{"src":"assets\/photos\/photo-20.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-21.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-22.webp","label":"Huset","category":"details"},{"src":"assets\/photos\/photo-23.webp","label":"Öppen planlösning","category":"living"},{"src":"assets\/photos\/photo-24.webp","label":"Matplats","category":"living"},{"src":"assets\/photos\/photo-25.webp","label":"Badrum","category":"details"},{"src":"assets\/photos\/photo-26.webp","label":"Takterrass","category":"terrace"},{"src":"assets\/photos\/photo-27.webp","label":"Vardagsrum","category":"living"},{"src":"assets\/photos\/photo-28.webp","label":"Badrum","category":"details"},{"src":"assets\/photos\/photo-29.webp","label":"Vardagsrum","category":"living"},{"src":"assets\/photos\/photo-30.webp","label":"TV","category":"living"},{"src":"assets\/photos\/photo-31.webp","label":"Soffhörna","category":"living"},{"src":"assets\/photos\/photo-32.webp","label":"Trappa","category":"details"},{"src":"assets\/photos\/photo-33.webp","label":"Trappa","category":"details"},{"src":"assets\/photos\/photo-34.webp","label":"Arbetsplats","category":"living"},{"src":"assets\/photos\/photo-35.webp","label":"Sovrum","category":"bedroom"},{"src":"assets\/photos\/photo-36.webp","label":"Sällskapsrum","category":"bedroom"},{"src":"assets\/photos\/photo-37.webp","label":"Värdeskåp","category":"details"},{"src":"assets\/photos\/photo-38.webp","label":"Entré","category":"details"}],base:"..\/..",viewPhotoPrefix:"Visa ",menuOpenLabel:"Öppna menyn",menuCloseLabel:"Stäng menyn"};</script>
<script src="../../assets/app.js" defer></script>
</body>
</html>
<?php echo ob_get_clean(); ?>

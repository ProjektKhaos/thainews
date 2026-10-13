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
<html lang="en">
<head>
  <meta charset="utf-8">
  <script>if (location.hash === '#top') history.replaceState(null, '', location.pathname + location.search);</script>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#f7f5f0">
  <meta name="description" content="Discover Cosmos House, a bright home with a rooftop terrace in Chiang Mai, Thailand. Explore the house and its photos.">
  <title>The experience | Cosmos House</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<header class="site-header" id="top">
  <a class="brand" href="../../en/"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a>
  <nav class="nav" aria-label="Main menu">
    <a href="../../en/house/" >The house</a>
    <a href="../../en/gallery/" >Gallery</a>
    <a href="../../en/experience/" aria-current="page">The experience</a>
    <a href="../../en/location/" >Chiang Mai</a>
    <div class="language-switch" role="group" aria-label="Language">
      <a href="../../en/experience/" hreflang="en" lang="en" aria-label="English" aria-current="true"><img class="flag-icon" src="../../assets/flags/gb.svg" alt="" width="24" height="16"> Eng</a>
      <a href="../../th/experience/" hreflang="th" lang="th" aria-label="ภาษาไทย" ><img class="flag-icon" src="../../assets/flags/th.svg" alt="" width="24" height="16"> Thai</a>
      <a href="../../sv/experience/" hreflang="sv" lang="sv" aria-label="Svenska" ><img class="flag-icon" src="../../assets/flags/se.svg" alt="" width="24" height="16"> Swe</a>
    </div>
  </nav>
  <a href="../../en/book/" class="header-cta" >Plan your stay <span>↗</span></a>
  <button class="menu-toggle" aria-label="Open menu" aria-expanded="false">☰</button>
</header>
<main>
    <div class="subpage-banner subpage-experience"><div class="container"><div class="eyebrow light"><span class="dot"></span> COSMOS HOUSE · Chiang Mai</div><h1 data-cms="copy-01">The experience</h1></div></div>
          <section class="experience section" id="experience">
    <div class="container experience-grid">
      <div class="experience-image"><img src="../../assets/photos/photo-23.webp" alt="Open-plan kitchen and living area" loading="lazy"><div class="image-sticker">STAY<br><i>AWHILE</i><span>✳</span></div></div>
      <div class="experience-info"><div class="eyebrow"><span class="tinyline"></span> LIVE A LITTLE SLOWER</div><h2 data-cms="copy-02">Space to do<br><i>your own thing.</i></h2>
        <p data-cms="copy-03">Take a book to the terrace, cook something good or settle in for a little work. There is space to do plenty, and space to do nothing at all.</p>
        <p class="sample-copy" data-cms="copy-04">Sample text: This section could describe how the rooms are used through the day, what is included in a stay and which details give the house its character. Until those facts are checked, the copy mainly shows the rhythm between longer descriptions, highlights and photos.</p>
        <div class="amenities">
          <div><span>⌂</span><div><strong data-cms="copy-05">Shared spaces</strong><small data-cms="copy-06">Open interiors and room to gather</small></div></div>
          <div><span>♨</span><div><strong data-cms="copy-07">Kitchen</strong><small data-cms="copy-08">For everyday breakfasts and long dinners</small></div></div>
          <div><span>☀</span><div><strong data-cms="copy-09">Private rooftop terrace</strong><small data-cms="copy-10">Take your coffee outside</small></div></div>
          <div><span>▣</span><div><strong data-cms="copy-11">Workspace</strong><small data-cms="copy-12">A quiet corner when you need to focus</small></div></div>
        </div><a class="text-link" href="../../en/gallery/">Explore more rooms <span>↗</span></a>
      </div>
    </div>
  </section>
  <section class="quote-section"><div class="container"><span class="sun-mark">✳</span><p data-cms="copy-13">Good days begin with<br><i>feeling at home.</i></p><p class="quote-detail" data-cms="copy-14">Sample text: A short paragraph here shows how this quiet section looks with a line of context beneath the large statement. Later, it could be replaced with a personal note from the host.</p><span class="quote-label">COSMOS HOUSE · Chiang Mai</span></div></section>
      </main>
<footer><div class="container footer-top"><div><a href="../../en/" class="brand footer-brand"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a><p data-cms="copy-15">Come as you are.<br>Stay as long as you like.</p></div><div><span>EXPLORE</span><a href="../../en/house/">About the house</a><a href="../../en/gallery/">Gallery</a><a href="../../en/experience/">The experience</a><a href="../../en/location/">Chiang Mai</a><a href="../../en/book/">Booking</a></div><div><span>FIND US</span><p data-cms="copy-16">Chiang Mai<br>Thailand</p></div></div><div class="container footer-bottom"><span>© 2026 COSMOS HOUSE</span><span>MADE FOR SLOWER MOMENTS ✳</span><a href="../../admin.php">Admin</a></div></footer>
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Photo gallery" hidden><div class="lightbox-top"><span>COSMOS HOUSE · GALLERY</span><button id="closeLightbox" aria-label="Close gallery">✕</button></div><div class="lightbox-body"><button id="prevPhoto" aria-label="Previous photo">‹</button><figure><img id="lightboxImg" alt=""><figcaption id="lightboxCaption"></figcaption></figure><button id="nextPhoto" aria-label="Next photo">›</button></div><div class="lightbox-counter" id="lightboxCounter"></div><div class="lightbox-filters" id="lightboxFilters"><button data-filter="all" class="active">All photos</button><button data-filter="living">Living spaces</button><button data-filter="bedroom">Bedrooms</button><button data-filter="terrace">Terrace</button><button data-filter="details">Details</button></div><div class="lightbox-thumbs" id="lightboxThumbs"></div></div>
<script>window.COSMOS={gallery:[{"src":"assets\/photos\/photo-01.webp","label":"Rooftop terrace","category":"terrace"},{"src":"assets\/photos\/photo-02.webp","label":"Living room","category":"living"},{"src":"assets\/photos\/photo-03.webp","label":"The house at dusk","category":"details"},{"src":"assets\/photos\/photo-04.webp","label":"Bedrooms","category":"bedroom"},{"src":"assets\/photos\/photo-05.webp","label":"Workspace","category":"living"},{"src":"assets\/photos\/photo-06.webp","label":"Bedrooms","category":"bedroom"},{"src":"assets\/photos\/photo-07.webp","label":"Dining area","category":"living"},{"src":"assets\/photos\/photo-08.webp","label":"Bedrooms","category":"bedroom"},{"src":"assets\/photos\/photo-09.webp","label":"Living room","category":"bedroom"},{"src":"assets\/photos\/photo-10.webp","label":"Terrace","category":"terrace"},{"src":"assets\/photos\/photo-11.webp","label":"Rooftop terrace","category":"terrace"},{"src":"assets\/photos\/photo-12.webp","label":"Hair dryer","category":"details"},{"src":"assets\/photos\/photo-13.webp","label":"Storage","category":"details"},{"src":"assets\/photos\/photo-14.webp","label":"Safe","category":"details"},{"src":"assets\/photos\/photo-15.webp","label":"Washing machine","category":"details"},{"src":"assets\/photos\/photo-16.webp","label":"Entrance","category":"details"},{"src":"assets\/photos\/photo-17.webp","label":"Details","category":"details"},{"src":"assets\/photos\/photo-18.webp","label":"Garden view","category":"terrace"},{"src":"assets\/photos\/photo-19.webp","label":"Kitchen","category":"living"},{"src":"assets\/photos\/photo-20.webp","label":"Workspace","category":"living"},{"src":"assets\/photos\/photo-21.webp","label":"Workspace","category":"living"},{"src":"assets\/photos\/photo-22.webp","label":"The house","category":"details"},{"src":"assets\/photos\/photo-23.webp","label":"Open-plan space","category":"living"},{"src":"assets\/photos\/photo-24.webp","label":"Dining area","category":"living"},{"src":"assets\/photos\/photo-25.webp","label":"Bathroom","category":"details"},{"src":"assets\/photos\/photo-26.webp","label":"Rooftop terrace","category":"terrace"},{"src":"assets\/photos\/photo-27.webp","label":"Living room","category":"living"},{"src":"assets\/photos\/photo-28.webp","label":"Bathroom","category":"details"},{"src":"assets\/photos\/photo-29.webp","label":"Living room","category":"living"},{"src":"assets\/photos\/photo-30.webp","label":"TV","category":"living"},{"src":"assets\/photos\/photo-31.webp","label":"Sofa corner","category":"living"},{"src":"assets\/photos\/photo-32.webp","label":"Staircase","category":"details"},{"src":"assets\/photos\/photo-33.webp","label":"Staircase","category":"details"},{"src":"assets\/photos\/photo-34.webp","label":"Workspace","category":"living"},{"src":"assets\/photos\/photo-35.webp","label":"Bedrooms","category":"bedroom"},{"src":"assets\/photos\/photo-36.webp","label":"Living room","category":"bedroom"},{"src":"assets\/photos\/photo-37.webp","label":"Safe","category":"details"},{"src":"assets\/photos\/photo-38.webp","label":"Entrance","category":"details"}],base:"..\/..",viewPhotoPrefix:"View ",menuOpenLabel:"Open menu",menuCloseLabel:"Close menu"};</script>
<script src="../../assets/app.js" defer></script>
</body>
</html>
<?php echo \Cosmos\Cms::apply((string)ob_get_clean(),'page-experience'); ?>

<?php
// Senast uppdaterad: 2026-10-12 10:00 (Asia/Bangkok) | KlⒶssⓔ & Ⓐberg
declare(strict_types=1);
// Mall för genererade språkfiler; ska aldrig serveras direkt.
if (!defined('COSMOS_STATIC_EXPORT') || !COSMOS_STATIC_EXPORT) { http_response_code(404); exit; }
require __DIR__ . '/config.php';
require __DIR__ . '/translations.php';
$page = COSMOS_PAGE_SLUG;
$pageTitles = ['home' => 'Your place in Chiang Mai', 'house' => 'Om huset', 'gallery' => 'Galleri', 'experience' => 'Upplevelsen', 'location' => 'Chiang Mai', 'book' => 'Bokning'];
$pagePath = static fn(string $language, string $slug): string => $language . '/' . ($slug === 'home' ? '' : $slug . '/');
$currentPath = static fn(string $slug): string => url($pagePath($site_language, $slug));
$gallery = json_decode((string) file_get_contents(__DIR__ . '/gallery.json'), true) ?: [];
foreach ($gallery as &$photo) $photo['label'] = t($photo['label']);
unset($photo);
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!DOCTYPE html>
<html lang="<?=h($site_language)?>">
<head>
  <meta charset="utf-8">
  <script>if (location.hash === '#top') history.replaceState(null, '', location.pathname + location.search);<?php if ($page === 'home'): ?>else { const oldPages = {'#home':'house','#gallery':'gallery','#experience':'experience','#location':'location','#book':'book'}; const target = oldPages[location.hash]; if (target) location.replace(new URL(target + '/', location.href)); }<?php endif; ?></script>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#f7f5f0">
  <meta name="description" content="<?=ht('Upptäck Cosmos House – ett ljust hem med takterrass i Chiang Mai, Thailand. Utforska bilder och upptäck huset.')?>">
  <title><?=ht($pageTitles[$page])?> | Cosmos House</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?=h(asset_url('style.css'))?>">
</head>
<body>
<header class="site-header" id="top">
  <a class="brand" href="<?=h($currentPath('home'))?>"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a>
  <nav class="nav" aria-label="<?=ht('Huvudmeny')?>">
    <a href="<?=h($currentPath('house'))?>" <?=$page==='house'?'aria-current="page"':''?>><?=ht('Hemmet')?></a>
    <a href="<?=h($currentPath('gallery'))?>" <?=$page==='gallery'?'aria-current="page"':''?>><?=ht('Galleri')?></a>
    <a href="<?=h($currentPath('experience'))?>" <?=$page==='experience'?'aria-current="page"':''?>><?=ht('Upplevelsen')?></a>
    <a href="<?=h($currentPath('location'))?>" <?=$page==='location'?'aria-current="page"':''?>><?=ht('Chiang Mai')?></a>
    <div class="language-switch" role="group" aria-label="<?=ht('Språk')?>">
      <a href="<?=h(url($pagePath('en', $page)))?>" hreflang="en" lang="en" aria-label="English" <?=$site_language==='en'?'aria-current="true"':''?>><img class="flag-icon" src="<?=h(asset_url('flags/gb.svg'))?>" alt="" width="24" height="16"> Eng</a>
      <a href="<?=h(url($pagePath('th', $page)))?>" hreflang="th" lang="th" aria-label="ภาษาไทย" <?=$site_language==='th'?'aria-current="true"':''?>><img class="flag-icon" src="<?=h(asset_url('flags/th.svg'))?>" alt="" width="24" height="16"> Thai</a>
      <a href="<?=h(url($pagePath('sv', $page)))?>" hreflang="sv" lang="sv" aria-label="Svenska" <?=$site_language==='sv'?'aria-current="true"':''?>><img class="flag-icon" src="<?=h(asset_url('flags/se.svg'))?>" alt="" width="24" height="16"> Swe</a>
    </div>
  </nav>
  <a href="<?=h($currentPath('book'))?>" class="header-cta" <?=$page==='book'?'aria-current="page"':''?>><?=ht('Planera din vistelse')?> <span>↗</span></a>
  <button class="menu-toggle" aria-label="<?=ht('Öppna menyn')?>" aria-expanded="false">☰</button>
</header>
<main>
  <?php if ($page === 'home'): ?>
  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-image"></div><div class="hero-shade"></div>
    <div class="hero-content">
      <div class="eyebrow light"><span class="dot"></span> <?=ht('EN PLATS ATT LANDA · CHIANG MAI, THAILAND')?></div>
      <h1 id="hero-title"><?=ht('Make room')?><br><?=ht('for ')?><i><?=ht('living.')?></i></h1>
      <p><?=ht('Ett ljust och personligt hem i Chiang Mai. Högt i tak, sköna ytor och en takterrass för de där stunderna som får tiden att sakta ner.')?></p>
      <p class="sample-copy"><?=ht('Exempeltext: Här kan en längre välkomsthälsning berätta om känslan i huset, vad gäster kan vänta sig av vistelsen och varför platsen passar för både lugna dagar och utflykter. Texten är en skiss tills innehållet har bekräftats.')?></p>
      <div class="hero-buttons"><a class="btn cream" href="<?=h($currentPath('book'))?>"><?=ht('Upptäck din vistelse')?> <span>↗</span></a><a class="btn ghost" href="<?=h($currentPath('house'))?>"><?=ht('Utforska huset')?> <span>↗</span></a></div>
    </div>
    <div class="hero-bottom"><span>18°47′ N · 98°59′ E</span><span><?=ht('SCROLL TO EXPLORE ↓')?></span><span>01 / COSMOS HOUSE</span></div>
  </section>
  <section class="page-directory section" aria-label="<?=ht('Utforska Cosmos House')?>">
    <div class="container"><div class="eyebrow"><span class="tinyline"></span> COSMOS HOUSE</div><h2><?=ht('Utforska Cosmos House')?></h2>
      <div class="page-directory-grid">
        <?php foreach (['house' => 'Hemmet', 'gallery' => 'Galleri', 'experience' => 'Upplevelsen', 'location' => 'Chiang Mai', 'book' => 'Bokning'] as $slug => $label): ?>
          <a class="page-tile page-tile-<?=h($slug)?>" href="<?=h($currentPath($slug))?>"><span><?=ht($label)?></span><b aria-hidden="true">↗</b></a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php else: ?>
  <div class="subpage-banner subpage-<?=h($page)?>"><div class="container"><div class="eyebrow light"><span class="dot"></span> COSMOS HOUSE · <?=ht('Chiang Mai')?></div><h1><?=ht($pageTitles[$page])?></h1></div></div>
  <?php endif; ?>
  <?php if ($page === 'house'): ?>
  <section class="intro section" id="home">
    <div class="container intro-grid"><div><div class="eyebrow"><span class="tinyline"></span> <?=ht('WELCOME TO COSMOS')?></div><h2><?=ht('Not just a place')?><br><?=ht('to stay. ')?><i><?=ht('A place to be.')?></i></h2></div>
      <div class="intro-copy"><p><?=ht('Det är något särskilt med att ha en egen plats i en ny stad. Cosmos House bjuder in till lugna morgnar, långsamma kvällar och friheten att känna sig hemma – långt hemifrån.')?></p><p class="sample-copy"><?=ht('Exempeltext: Den här introduktionen kan senare få mer utrymme för husets bakgrund, planlösning och sådant som gör vardagen enkel under en vistelse. Här provar vi ett längre textflöde för att se hur rubrik, brödtext och de små informationsrutorna samspelar på olika skärmar.')?></p>
        <div class="feature-strip">
          <div><span>✳</span><strong><?=ht('Ljust & luftigt')?></strong><small><?=ht('Öppna sällskapsytor')?></small></div>
          <div><span>☀</span><strong><?=ht('Takterrass')?></strong><small><?=ht('Utrymme för avkoppling')?></small></div>
          <div><span>⌂</span><strong><?=ht('Eget hem')?></strong><small><?=ht('Plats att landa')?></small></div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>
  <?php if ($page === 'gallery'): ?>
  <section class="gallery-section section" id="gallery">
    <div class="container">
      <div class="section-top"><div><div class="eyebrow"><span class="tinyline"></span> <?=ht('A CLOSER LOOK')?></div><h2><?=ht('Take a ')?><i><?=ht('look around.')?></i></h2></div><p><?=ht('Ljuset, detaljerna och alla små hörn som gör Cosmos House till just Cosmos House.')?><span class="sample-copy"><?=ht('Exempeltext: Bilderna kan kompletteras med korta berättelser om rummen och vad varje vy visar. Här ligger mer text som gör det lättare att bedöma hur galleriets inledning fungerar när den får lite mer innehåll, utan att lägga till obekräftade fakta om huset.')?></span></p></div>
      <div class="gallery-feature">
        <button class="g-item big" data-open="1"><img src="<?=h(asset_url('photos/photo-02.webp'))?>" alt="<?=ht('Luftigt vardagsrum med högt tak och synliga takbjälkar')?>" loading="lazy"><span><?=ht('Living, elevated')?> <b>↗</b></span></button>
        <button class="g-item" data-open="25"><img src="<?=h(asset_url('photos/photo-26.webp'))?>" alt="<?=ht('Takterrass med sittplatser och utsikt')?>" loading="lazy"><span><?=ht('The rooftop')?> <b>↗</b></span></button>
        <button class="g-item" data-open="7"><img src="<?=h(asset_url('photos/photo-08.webp'))?>" alt="<?=ht('Ljust sovrum med dubbelsäng')?>" loading="lazy"><span><?=ht('Rest easy')?> <b>↗</b></span></button>
      </div>
      <div class="gallery-actions"><span><b><?=count($gallery)?></b> <?=ht('verkliga bilder från huset')?></span><button class="btn dark" id="allPhotos"><?=ht('Visa hela galleriet')?> <span>↗</span></button></div>
    </div>
  </section>
  <?php endif; ?>
  <?php if ($page === 'experience'): ?>
  <section class="experience section" id="experience">
    <div class="container experience-grid">
      <div class="experience-image"><img src="<?=h(asset_url('photos/photo-23.webp'))?>" alt="<?=ht('Öppen planlösning med kök och sällskapsytor')?>" loading="lazy"><div class="image-sticker"><?=ht('STAY')?><br><i><?=ht('AWHILE')?></i><span>✳</span></div></div>
      <div class="experience-info"><div class="eyebrow"><span class="tinyline"></span> <?=ht('LIVE A LITTLE SLOWER')?></div><h2><?=ht('Space to do')?><br><i><?=ht('your own thing.')?></i></h2>
        <p><?=ht('Ta med en bok till terrassen, laga något gott i köket eller slå dig ner för en stunds jobb. Här finns utrymme både för aktivitet och för att inte göra någonting alls.')?></p>
        <p class="sample-copy"><?=ht('Exempeltext: Den här delen kan beskriva hur rummen används under en dag, vad som ingår i vistelsen och vilka detaljer som gör huset personligt. Tills uppgifterna är kontrollerade får texten främst visa rytmen mellan längre beskrivningar, fördelar och bilder.')?></p>
        <div class="amenities">
          <div><span>⌂</span><div><strong><?=ht('Sociala ytor')?></strong><small><?=ht('Luftig inredning och plats att umgås')?></small></div></div>
          <div><span>♨</span><div><strong><?=ht('Kök')?></strong><small><?=ht('För vardagsfrukost och långa middagar')?></small></div></div>
          <div><span>☀</span><div><strong><?=ht('Privat takterrass')?></strong><small><?=ht('Ta kaffet utomhus')?></small></div></div>
          <div><span>▣</span><div><strong><?=ht('Arbetsplats')?></strong><small><?=ht('En lugn hörna när du behöver fokusera')?></small></div></div>
        </div><a class="text-link" href="<?=h($currentPath('gallery'))?>"><?=ht('Upptäck fler rum')?> <span>↗</span></a>
      </div>
    </div>
  </section>
  <section class="quote-section"><div class="container"><span class="sun-mark">✳</span><p><?=ht('Good days begin with')?><br><i><?=ht('feeling at home.')?></i></p><p class="quote-detail"><?=ht('Exempeltext: Ett kortare stycke här visar hur citatytan fungerar när den får en förklarande rad under den stora rubriken. Senare kan den ersättas med en personlig hälsning från värden.')?></p><span class="quote-label">COSMOS HOUSE · <?=ht('Chiang Mai')?></span></div></section>
  <?php endif; ?>
  <?php if ($page === 'location'): ?>
  <section class="location section" id="location">
    <div class="container location-grid">
      <div><div class="eyebrow"><span class="tinyline"></span> <?=ht('THE NEIGHBOURHOOD')?></div><h2><?=ht('Your Chiang Mai')?><br><i><?=ht('chapter starts here.')?></i></h2><p><?=ht('Tempel, kaféer, marknader och storslagna vyer – Chiang Mai är en stad som bjuder in till upptäckter. Låt Cosmos House vara din utgångspunkt.')?></p><p class="sample-copy"><?=ht('Exempeltext: Här kan vi så småningom berätta mer om kvarteret, hur man tar sig runt och vilka platser som kan vara intressanta att upptäcka. Exakta avstånd, restider och rekommendationer läggs till först när de har kontrollerats.')?></p><p class="location-note"><?=ht('Exakt adress och vägbeskrivning lämnas i samband med bekräftad bokning.')?></p><a class="text-link" href="<?=h($currentPath('book'))?>"><?=ht('Planera din vistelse')?> <span>↗</span></a></div>
      <div class="location-img"><img src="<?=h(asset_url('photos/photo-10.webp'))?>" alt="<?=ht('Cosmos House terrass med bord och stolar')?>" loading="lazy"><div class="location-card"><span class="dot"></span><div><strong>Cosmos House</strong><small><?=ht('Chiang Mai')?> · <?=ht('Thailand')?></small></div></div></div>
    </div>
  </section>
  <?php endif; ?>
  <?php if ($page === 'book'): ?>
  <section class="booking-section section" id="book">
    <div class="container booking-grid">
      <div><div class="eyebrow"><span class="tinyline"></span> <?=ht('YOUR NEXT ESCAPE')?></div><h2><?=ht("Let's make")?><br><i><?=ht('some memories.')?></i></h2>
        <p><?=ht('Utforska huset och bilderna. Aktuell bokningsstatus visas här bredvid.')?></p>
        <div class="booking-points"><div>✓ <span><?=ht('Utforska huset och galleriet')?></span></div><div>✓ <span><?=ht('Priser och villkor bekräftas före bokningsstart')?></span></div></div>
      </div>
      <div class="booking-card"><!-- COSMOS_RUNTIME_BOOKING --></div>
    </div>
  </section>
  <?php endif; ?>
</main>
<footer><div class="container footer-top"><div><a href="<?=h($currentPath('home'))?>" class="brand footer-brand"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a><p><?=ht('Come as you are.')?><br><?=ht('Stay as long as you like.')?></p></div><div><span><?=ht('EXPLORE')?></span><a href="<?=h($currentPath('house'))?>"><?=ht('Om huset')?></a><a href="<?=h($currentPath('gallery'))?>"><?=ht('Galleri')?></a><a href="<?=h($currentPath('experience'))?>"><?=ht('Upplevelsen')?></a><a href="<?=h($currentPath('location'))?>"><?=ht('Chiang Mai')?></a><a href="<?=h($currentPath('book'))?>"><?=ht('Bokning')?></a></div><div><span><?=ht('FIND US')?></span><p><?=ht('Chiang Mai')?><br><?=ht('Thailand')?></p></div></div><div class="container footer-bottom"><span>© <?=date('Y')?> COSMOS HOUSE</span><span><?=ht('MADE FOR SLOWER MOMENTS ✳')?></span><a href="<?=h(url('admin.php'))?>"><?=ht('Admin')?></a></div></footer>
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="<?=ht('Bildgalleri')?>" hidden><div class="lightbox-top"><span>COSMOS HOUSE · <?=ht('GALLERY')?></span><button id="closeLightbox" aria-label="<?=ht('Stäng galleri')?>">✕</button></div><div class="lightbox-body"><button id="prevPhoto" aria-label="<?=ht('Föregående bild')?>">‹</button><figure><img id="lightboxImg" alt=""><figcaption id="lightboxCaption"></figcaption></figure><button id="nextPhoto" aria-label="<?=ht('Nästa bild')?>">›</button></div><div class="lightbox-counter" id="lightboxCounter"></div><div class="lightbox-filters" id="lightboxFilters"><button data-filter="all" class="active"><?=ht('Alla bilder')?></button><button data-filter="living"><?=ht('Sällskapsytor')?></button><button data-filter="bedroom"><?=ht('Sovrum')?></button><button data-filter="terrace"><?=ht('Terrass')?></button><button data-filter="details"><?=ht('Detaljer')?></button></div><div class="lightbox-thumbs" id="lightboxThumbs"></div></div>
<script>window.COSMOS={gallery:<?=json_encode($gallery,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?>,base:<?=json_encode(site_base_path())?>,viewPhotoPrefix:<?=json_encode(t('Visa '),JSON_UNESCAPED_UNICODE)?>,menuOpenLabel:<?=json_encode(t('Öppna menyn'),JSON_UNESCAPED_UNICODE)?>,menuCloseLabel:<?=json_encode(t('Stäng menyn'),JSON_UNESCAPED_UNICODE)?>};</script>
<script src="<?=h(asset_url('app.js'))?>" defer></script>
</body>
</html>

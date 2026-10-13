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
<html lang="th">
<head>
  <meta charset="utf-8">
  <script>if (location.hash === '#top') history.replaceState(null, '', location.pathname + location.search);</script>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#f7f5f0">
  <meta name="description" content="ค้นพบคอสมอสเฮาส์ บ้านพักโปร่งสบายพร้อมดาดฟ้าในเชียงใหม่ ประเทศไทย ชมบ้านและภาพถ่าย">
  <title>เกี่ยวกับบ้าน | Cosmos House</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<header class="site-header" id="top">
  <a class="brand" href="../../th/"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a>
  <nav class="nav" aria-label="เมนูหลัก">
    <a href="../../th/house/" aria-current="page">บ้านพัก</a>
    <a href="../../th/gallery/" >แกลเลอรี</a>
    <a href="../../th/experience/" >ประสบการณ์</a>
    <a href="../../th/location/" >เชียงใหม่</a>
    <div class="language-switch" role="group" aria-label="ภาษา">
      <a href="../../en/house/" hreflang="en" lang="en" aria-label="English" ><img class="flag-icon" src="../../assets/flags/gb.svg" alt="" width="24" height="16"> Eng</a>
      <a href="../../th/house/" hreflang="th" lang="th" aria-label="ภาษาไทย" aria-current="true"><img class="flag-icon" src="../../assets/flags/th.svg" alt="" width="24" height="16"> Thai</a>
      <a href="../../sv/house/" hreflang="sv" lang="sv" aria-label="Svenska" ><img class="flag-icon" src="../../assets/flags/se.svg" alt="" width="24" height="16"> Swe</a>
    </div>
  </nav>
  <a href="../../th/book/" class="header-cta" >วางแผนการเข้าพัก <span>↗</span></a>
  <button class="menu-toggle" aria-label="เปิดเมนู" aria-expanded="false">☰</button>
</header>
<main>
    <div class="subpage-banner subpage-house"><div class="container"><div class="eyebrow light"><span class="dot"></span> COSMOS HOUSE · เชียงใหม่</div><h1 data-cms="copy-01">เกี่ยวกับบ้าน</h1></div></div>
      <section class="intro section" id="home">
    <div class="container intro-grid"><div><div class="eyebrow"><span class="tinyline"></span> ยินดีต้อนรับสู่คอสมอส</div><h2 data-cms="copy-02">ไม่ใช่แค่ที่พัก<br>ชั่วคราว <i>แต่เป็นที่ของคุณ.</i></h2></div>
      <div class="intro-copy"><p data-cms="copy-03">การมีพื้นที่ของตัวเองในเมืองใหม่เป็นความรู้สึกพิเศษ คอสมอสเฮาส์ชวนคุณใช้เวลาในเช้าที่สงบ ค่ำคืนสบาย ๆ และรู้สึกเหมือนอยู่บ้านแม้ไกลจากบ้าน</p><p class="sample-copy" data-cms="copy-04">ข้อความตัวอย่าง: บทนำนี้อาจเล่าที่มาของบ้าน ผังพื้นที่ และรายละเอียดที่ช่วยให้การเข้าพักสะดวกขึ้น ตอนนี้เราเพิ่มข้อความเพื่อดูว่าหัวเรื่อง เนื้อหา และข้อมูลสั้น ๆ จัดวางร่วมกันอย่างไรบนหน้าจอขนาดต่าง ๆ</p>
        <div class="feature-strip">
          <div><span>✳</span><strong data-cms="copy-05">สว่างและโปร่ง</strong><small data-cms="copy-06">พื้นที่นั่งเล่นเปิดโล่ง</small></div>
          <div><span>☀</span><strong data-cms="copy-07">ดาดฟ้า</strong><small data-cms="copy-08">พื้นที่พักผ่อน</small></div>
          <div><span>⌂</span><strong data-cms="copy-09">บ้านส่วนตัว</strong><small data-cms="copy-10">ที่ให้พักใจ</small></div>
        </div>
      </div>
    </div>
  </section>
          </main>
<footer><div class="container footer-top"><div><a href="../../th/" class="brand footer-brand"><span class="brand-icon">✳</span><span>COSMOS <em>HOUSE</em></span></a><p data-cms="copy-11">มาในแบบที่คุณเป็น.<br>พักผ่อนในแบบที่คุณชอบ.</p></div><div><span>สำรวจ</span><a href="../../th/house/">เกี่ยวกับบ้าน</a><a href="../../th/gallery/">แกลเลอรี</a><a href="../../th/experience/">ประสบการณ์</a><a href="../../th/location/">เชียงใหม่</a><a href="../../th/book/">การจอง</a></div><div><span>ที่ตั้ง</span><p data-cms="copy-12">เชียงใหม่<br>ประเทศไทย</p></div></div><div class="container footer-bottom"><span>© 2026 COSMOS HOUSE</span><span>พื้นที่สำหรับช่วงเวลาแสนสบาย ✳</span><a href="../../admin.php">ผู้ดูแล</a></div></footer>
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="แกลเลอรีภาพ" hidden><div class="lightbox-top"><span>COSMOS HOUSE · แกลเลอรี</span><button id="closeLightbox" aria-label="ปิดแกลเลอรี">✕</button></div><div class="lightbox-body"><button id="prevPhoto" aria-label="ภาพก่อนหน้า">‹</button><figure><img id="lightboxImg" alt=""><figcaption id="lightboxCaption"></figcaption></figure><button id="nextPhoto" aria-label="ภาพถัดไป">›</button></div><div class="lightbox-counter" id="lightboxCounter"></div><div class="lightbox-filters" id="lightboxFilters"><button data-filter="all" class="active">ภาพทั้งหมด</button><button data-filter="living">พื้นที่นั่งเล่น</button><button data-filter="bedroom">ห้องนอน</button><button data-filter="terrace">ระเบียง</button><button data-filter="details">รายละเอียด</button></div><div class="lightbox-thumbs" id="lightboxThumbs"></div></div>
<script>window.COSMOS={gallery:[{"src":"assets\/photos\/photo-01.webp","label":"ดาดฟ้า","category":"terrace"},{"src":"assets\/photos\/photo-02.webp","label":"ห้องนั่งเล่น","category":"living"},{"src":"assets\/photos\/photo-03.webp","label":"บ้านพักยามค่ำ","category":"details"},{"src":"assets\/photos\/photo-04.webp","label":"ห้องนอน","category":"bedroom"},{"src":"assets\/photos\/photo-05.webp","label":"มุมทำงาน","category":"living"},{"src":"assets\/photos\/photo-06.webp","label":"ห้องนอน","category":"bedroom"},{"src":"assets\/photos\/photo-07.webp","label":"พื้นที่รับประทานอาหาร","category":"living"},{"src":"assets\/photos\/photo-08.webp","label":"ห้องนอน","category":"bedroom"},{"src":"assets\/photos\/photo-09.webp","label":"ห้องนั่งเล่น","category":"bedroom"},{"src":"assets\/photos\/photo-10.webp","label":"ระเบียง","category":"terrace"},{"src":"assets\/photos\/photo-11.webp","label":"ดาดฟ้า","category":"terrace"},{"src":"assets\/photos\/photo-12.webp","label":"ไดร์เป่าผม","category":"details"},{"src":"assets\/photos\/photo-13.webp","label":"พื้นที่เก็บของ","category":"details"},{"src":"assets\/photos\/photo-14.webp","label":"ตู้เซฟ","category":"details"},{"src":"assets\/photos\/photo-15.webp","label":"เครื่องซักผ้า","category":"details"},{"src":"assets\/photos\/photo-16.webp","label":"ทางเข้า","category":"details"},{"src":"assets\/photos\/photo-17.webp","label":"รายละเอียด","category":"details"},{"src":"assets\/photos\/photo-18.webp","label":"วิวสวน","category":"terrace"},{"src":"assets\/photos\/photo-19.webp","label":"ครัว","category":"living"},{"src":"assets\/photos\/photo-20.webp","label":"มุมทำงาน","category":"living"},{"src":"assets\/photos\/photo-21.webp","label":"มุมทำงาน","category":"living"},{"src":"assets\/photos\/photo-22.webp","label":"บ้านพัก","category":"details"},{"src":"assets\/photos\/photo-23.webp","label":"พื้นที่เปิดโล่ง","category":"living"},{"src":"assets\/photos\/photo-24.webp","label":"พื้นที่รับประทานอาหาร","category":"living"},{"src":"assets\/photos\/photo-25.webp","label":"ห้องน้ำ","category":"details"},{"src":"assets\/photos\/photo-26.webp","label":"ดาดฟ้า","category":"terrace"},{"src":"assets\/photos\/photo-27.webp","label":"ห้องนั่งเล่น","category":"living"},{"src":"assets\/photos\/photo-28.webp","label":"ห้องน้ำ","category":"details"},{"src":"assets\/photos\/photo-29.webp","label":"ห้องนั่งเล่น","category":"living"},{"src":"assets\/photos\/photo-30.webp","label":"TV","category":"living"},{"src":"assets\/photos\/photo-31.webp","label":"มุมโซฟา","category":"living"},{"src":"assets\/photos\/photo-32.webp","label":"บันได","category":"details"},{"src":"assets\/photos\/photo-33.webp","label":"บันได","category":"details"},{"src":"assets\/photos\/photo-34.webp","label":"มุมทำงาน","category":"living"},{"src":"assets\/photos\/photo-35.webp","label":"ห้องนอน","category":"bedroom"},{"src":"assets\/photos\/photo-36.webp","label":"ห้องนั่งเล่น","category":"bedroom"},{"src":"assets\/photos\/photo-37.webp","label":"ตู้เซฟ","category":"details"},{"src":"assets\/photos\/photo-38.webp","label":"ทางเข้า","category":"details"}],base:"..\/..",viewPhotoPrefix:"ดู",menuOpenLabel:"เปิดเมนู",menuCloseLabel:"ปิดเมนู"};</script>
<script src="../../assets/app.js" defer></script>
</body>
</html>
<?php echo ob_get_clean(); ?>

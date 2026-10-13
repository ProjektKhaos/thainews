/* Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok) */
const { chromium } = require('@playwright/test');
(async()=>{
  const base=(process.env.COSMOS_TEST_BASE || 'https://thainews.aberg.online/ch/').replace(/\/?$/,'/');
  const browser=await chromium.launch({headless:true});
  try {
    for(const width of [320,390,651,768,901,1024,1440]){
      const context=await browser.newContext({viewport:{width,height:900}}),page=await context.newPage();
      await page.goto(base+'login.php');
      await page.locator('#access-word').fill(process.env.COSMOS_TEST_PREVIEW_WORD || 'huset');
      await page.locator('button[type=submit]').click();
      for(const language of ['en','sv','th']) for(const slug of ['','house/','gallery/','experience/','location/','book/']){
        const response=await page.goto(base+language+'/'+slug);
        if(response.status()!==200)throw Error(language+'/'+slug+' HTTP '+response.status());
        const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>window.innerWidth+2);
        if(overflow)throw Error(language+'/'+slug+' overflows at '+width);
        if(slug==='book/' && await page.locator('#cosmosBookingForm').count())throw Error('booking unexpectedly public');
      }
      await page.goto(base+'en/gallery/');
      if(width===320){const photos=await page.evaluate(()=>window.COSMOS.gallery.map(p=>p.src));if(photos.length!==38)throw Error('gallery manifest count');for(const path of photos){const response=await page.request.get(base+path);if(response.status()!==200)throw Error('photo unavailable: '+path)}}
      await page.locator('#allPhotos').click();
      if(await page.locator('#lightbox').isHidden())throw Error('gallery did not open');
      await page.locator('#lightboxFilters [data-filter=bedroom]').click();
      const bedroomCount=await page.locator('#lightboxThumbs button').count();
      if(bedroomCount<1||bedroomCount>=38)throw Error('gallery filter failed');
      await page.keyboard.press('Escape');
      if(await page.locator('#lightbox').isVisible())throw Error('gallery did not close');
      if(!await page.locator('#allPhotos').evaluate(el=>el===document.activeElement))throw Error('gallery focus did not return');
      await page.goto(base+'sv/gallery/');
      if(await page.locator('.language-switch a[hreflang=en]').isHidden())await page.locator('.menu-toggle').click();
      await page.locator('.language-switch a[hreflang=en]').click();
      if(!page.url().endsWith('/en/gallery/'))throw Error('language switch lost subpage');
      await context.close();
      console.log('PASS '+width+'px: 18 pages, booking closed, gallery');
    }
  } finally {await browser.close()}
})().catch(error=>{console.error(error.message);process.exitCode=1});

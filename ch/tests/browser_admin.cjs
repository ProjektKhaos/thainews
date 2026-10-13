const { chromium } = require('@playwright/test');
(async () => {
  const base = process.env.COSMOS_TEST_BASE;
  if (!base) throw Error('COSMOS_TEST_BASE required');
  const browser = await chromium.launch({headless: true});
  try {
    for (const width of [390, 1280]) {
      const page = await browser.newPage({viewport: {width, height: 900}});
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(base + 'admin.php');
      await page.locator('[name=username]').fill('testadmin');
      await page.locator('[name=password]').fill('temporary-test-password');
      await page.getByRole('button', {name: 'Sign in', exact: true}).click();
      await page.getByRole('heading', {name: 'Overview', exact: true}).waitFor();
      for (const view of ['dashboard','bookings','calendar','rates','content','media','mail','settings','users','launch','backup','audit','account','guide']) {
        const response = await page.goto(base + 'admin.php?view=' + view);
        if (response.status() !== 200) throw Error(view + ' returned ' + response.status());
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 2);
        if (overflow) throw Error(view + ' overflows at ' + width);
      }
      await page.goto(base + 'admin.php?view=calendar&mode=week');
      if (await page.locator('.calendar .day').count() !== 7) throw Error('Week calendar missing');
      await page.goto(base + 'admin.php?view=bookings&id=1');
      await page.getByRole('heading', {name: 'History and notifications'}).waitFor();
      await page.goto(base + 'admin.php?view=content');
      await page.locator('[name="content[intro]"]').fill('Browser checked draft');
      await page.getByRole('button', {name: 'Save draft', exact: true}).click();
      await page.getByRole('status').waitFor();
      await page.goto(base + 'public.php?preview=1');
      if (!(await page.locator('.intro').textContent()).includes('Browser checked draft')) throw Error('Draft preview missing');
      await page.locator('.hero-photo').click();
      if (!(await page.locator('#lightbox').evaluate(el => el.open))) throw Error('Landing gallery failed');
      if (errors.length) throw Error(errors.join('; '));
      console.log('PASS all 14 admin views, week calendar, detail, draft preview and gallery at ' + width + 'px');
      await page.close();
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });

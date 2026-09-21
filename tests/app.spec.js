const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

async function setAllSourceVisibility(page, visible) {
  const checkboxes = page.locator('input[name^="visible"]');
  const count = await checkboxes.count();
  for (let index = 0; index < count; index += 1) {
    if (visible) await checkboxes.nth(index).check();
    else await checkboxes.nth(index).uncheck();
  }
}

test('home/settings responsive basics', async ({ page }, testInfo) => {
  await page.goto('/');
  await expect(page.locator('header')).toBeVisible();
  await expect(page.locator('.top-controls')).toHaveCount(0);
  await expect(page.locator('header > .language-switcher')).toBeVisible();
  expect(await page.locator('.language-switcher a').evaluateAll(links => links.map(link => link.textContent.trim()))).toEqual(['', '', '']);
  expect(await page.locator('.language-switcher a').evaluateAll(links => links.map(link => link.getAttribute('aria-label')))).toEqual(['Svenska', 'English', 'ไทย']);
  expect(await page.locator('.language-switcher .flag-icon').evaluateAll(images => images.map(image => new URL(image.src).pathname))).toEqual(['/img/flags/se.svg', '/img/flags/gb.svg', '/img/flags/th.svg']);
  expect(await page.locator('.language-switcher a').evaluateAll(links => links.map(link => link.getAttribute('href')))).toEqual(['?lang=sv', '?lang=en', '?lang=th']);
  expect(await page.locator('header > .language-switcher').evaluate(switcher => {
    const right = switcher.getBoundingClientRect().right;
    return [...switcher.parentElement.children].every(element => element.getBoundingClientRect().right <= right + 1);
  })).toBeTruthy();
  await expect(page.locator('header .theme-toggle')).toHaveCount(0);
  await expect(page.locator('.site-footer')).toHaveText('Thai News — Developed by Hans Åberg 2026 — Relayworks');
  await expect(page.locator('img[alt="Thai News"]')).toBeVisible();
  await expect(page.locator('.source-section h2', { hasText: 'Bangkok Post' })).toBeVisible();
  const headlineLengths = await page.locator('.news-item h3 a').evaluateAll(links =>
    links.map(link => Array.from(link.textContent.trim()).length)
  );
  expect(headlineLengths.length).toBeGreaterThan(0);
  expect(Math.max(...headlineLengths)).toBeLessThanOrEqual(34);
  const excerptLengths = await page.locator('.excerpt-text').evaluateAll(excerpts =>
    excerpts.map(excerpt => Array.from(excerpt.textContent.trim()).length)
  );
  expect(excerptLengths.length).toBeGreaterThan(0);
  expect(Math.max(...excerptLengths)).toBeLessThanOrEqual(95);
  await page.evaluate(() => { document.documentElement.dataset.theme = 'light'; });
  const cardColors = await page.locator('.source-section').first().evaluate(section => ({
    heading: getComputedStyle(section.querySelector('.source-heading')).backgroundColor,
    article: getComputedStyle(section.querySelector('.news-item')).backgroundColor,
  }));
  expect(cardColors.heading).not.toBe(cardColors.article);
  expect(cardColors.article).toBe('rgb(255, 255, 255)');
  const sourceShape = await page.locator('.source-section').first().evaluate(section => {
    const heading = section.querySelector('.source-heading');
    return {
      sectionWidth: section.getBoundingClientRect().width,
      headingWidth: heading.getBoundingClientRect().width,
      headingRadius: parseFloat(getComputedStyle(heading).borderTopLeftRadius),
    };
  });
  expect(Math.abs(sourceShape.sectionWidth - sourceShape.headingWidth)).toBeLessThanOrEqual(2);
  expect(sourceShape.headingRadius).toBeGreaterThan(0);
  expect(await page.locator('html').evaluate(element => getComputedStyle(element).fontFamily)).toContain('Consolas');
  expect(await page.locator('.source-section h2').first().evaluate(element => parseFloat(getComputedStyle(element).fontSize))).toBeGreaterThan(20);
  const columns = await page.locator('.source-grid').evaluate(element => getComputedStyle(element).gridTemplateColumns.split(' ').length);
  expect(columns).toBe({ mobile: 1, tablet: 2, desktop: 3 }[testInfo.project.name]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBeTruthy();
  await page.goto('/settings.php');
  await expect(page.locator('#source-sort')).toBeVisible();
  await expect(page.locator('.settings-panel .theme-toggle')).toBeVisible();
  const initialTheme = await page.locator('html').getAttribute('data-theme');
  const nextTheme = initialTheme === 'dark' ? 'light' : 'dark';
  await page.locator('.theme-toggle').click();
  await expect(page.locator('html')).toHaveAttribute('data-theme', nextTheme);
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-theme', nextTheme);
});
test('no filesystem path leaks in home html', async ({ page }) => { await page.goto('/'); expect(await page.content()).not.toContain('/var/www/'); });
test('language and source visibility persist', async ({ page }) => {
  await page.goto('/settings.php');
  await page.locator('input[name="language"][value="sv"]').check();
  await setAllSourceVisibility(page, false);
  await page.locator('button.primary[type="submit"]').click();
  await expect(page).toHaveURL(/saved=1/);
  await page.goto('/');
  await expect(page).toHaveTitle(/Nyheter från Thailand/);
  await expect(page.locator('.source-section')).toHaveCount(0);
  await page.goto('/settings.php');
  await setAllSourceVisibility(page, true);
  await page.locator('button.primary[type="submit"]').click();
  await page.goto('/');
  await expect(page.locator('.source-section h2', { hasText: 'Bangkok Post' })).toBeVisible();
});
test('metadata and branding use approved assets', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', 'https://thainews.aberg.online/');
  await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content', 'https://thainews.aberg.online/img/thainews_fb_og.png');
  await expect(page.locator('.brand img')).toHaveAttribute('src', /img\/thainews_logo1\.png/);
});
test('read-more link follows the selected language', async ({ page }) => {
  for (const [language, label] of [['sv', 'Läs mer >'], ['en', 'Read more >'], ['th', 'อ่านเพิ่มเติม >']]) {
    await page.goto(`/?lang=${language}`);
    await expect(page.locator('.read-more').first()).toHaveText(label);
  }
});
test('home has no serious accessibility violations', async ({ page }) => {
  await page.goto('/');
  for (const theme of ['light', 'dark']) {
    await page.evaluate(value => {
      localStorage.setItem('thai-news-theme', value);
      document.documentElement.dataset.theme = value;
    }, theme);
    const results = await new AxeBuilder({ page }).analyze();
    expect(results.violations.filter(v => ['serious', 'critical'].includes(v.impact))).toEqual([]);
  }
});
test('settings persist with JavaScript disabled', async ({ browser }, testInfo) => {
  const context = await browser.newContext({
    baseURL: process.env.THAI_NEWS_BASE_URL || 'http://127.0.0.1:8080',
    javaScriptEnabled: false,
    viewport: testInfo.project.use.viewport,
  });
  const page = await context.newPage();
  await page.goto('/settings.php');
  await expect(page.locator('.move-up').first()).toHaveAttribute('type', 'submit');
  await page.locator('input[name="language"][value="th"]').check();
  await setAllSourceVisibility(page, false);
  await page.locator('button.primary').click();
  await expect(page).toHaveURL(/saved=1/);
  await page.goto('/');
  await expect(page).toHaveTitle(/ข่าวจากประเทศไทย/);
  await expect(page.locator('.source-section')).toHaveCount(0);
  await page.goto('/settings.php');
  await setAllSourceVisibility(page, true);
  await page.locator('button.primary').click();
  await context.close();
});

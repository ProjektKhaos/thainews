const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

test.beforeEach(async ({ page }, testInfo) => {
  if (testInfo.title.includes('first-visit guides')) return;
  await page.addInitScript(() => {
    localStorage.setItem('thai-news-tour-home-v1', 'complete');
    localStorage.setItem('thai-news-tour-settings-v1', 'complete');
  });
});

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
  const articleLayout = await page.locator('.source-section').first().evaluate(section => {
    const articles = [...section.querySelectorAll('.news-item')];
    const sectionRect = section.getBoundingClientRect();
    const firstRect = articles[0].getBoundingClientRect();
    return {
      fillsSection: Math.abs(sectionRect.left - firstRect.left) <= 2 && Math.abs(sectionRect.right - firstRect.right) <= 2,
      alternatingColors: getComputedStyle(articles[0]).backgroundColor !== getComputedStyle(articles[1]).backgroundColor,
    };
  });
  expect(articleLayout.fillsSection).toBeTruthy();
  expect(articleLayout.alternatingColors).toBeTruthy();
  const horizontalAlignment = await page.evaluate(() => {
    const logo = document.querySelector('.brand').getBoundingClientRect();
    const source = document.querySelector('.source-section').getBoundingClientRect();
    return Math.abs(logo.left - source.left);
  });
  expect(horizontalAlignment).toBeLessThanOrEqual(1);
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
  await expect(page.locator('html')).toHaveAttribute('data-font', 'inter');
  await expect(page.locator('html')).toHaveAttribute('data-font-size', 'medium');
  expect(await page.locator('html').evaluate(element => getComputedStyle(element).fontFamily)).toContain('Inter');
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
test('first-visit guides explain home and settings once', async ({ page }) => {
  await page.goto('/?lang=sv');
  await expect(page.locator('#tour-layer')).toBeVisible();
  await expect(page.locator('#tour-title')).toHaveText('Välkommen till Thai News');
  await expect(page.locator('#tour-counter')).toHaveText('Steg 1 av 5');
  const guideAccessibility = await new AxeBuilder({ page }).include('#tour-layer').analyze();
  expect(guideAccessibility.violations.filter(violation => ['serious', 'critical'].includes(violation.impact))).toEqual([]);
  const spotlight = await page.locator('#tour-spotlight').boundingBox();
  expect(spotlight?.width).toBeGreaterThan(0);
  expect(spotlight?.height).toBeGreaterThan(0);
  for (let step = 2; step <= 5; step += 1) {
    await page.locator('#tour-next').click();
    await expect(page.locator('#tour-counter')).toHaveText(`Steg ${step} av 5`);
  }
  await expect(page.locator('#tour-next')).toHaveText('Klart');
  await page.locator('#tour-next').click();
  await expect(page.locator('#tour-layer')).toBeHidden();
  expect(await page.evaluate(() => localStorage.getItem('thai-news-tour-home-v1'))).toBe('complete');
  await page.reload();
  await expect(page.locator('#tour-layer')).toBeHidden();

  await page.goto('/settings.php?lang=sv');
  await expect(page.locator('#tour-layer')).toBeVisible();
  await expect(page.locator('#tour-title')).toHaveText('Ljust eller mörkt tema');
  await page.locator('#tour-skip').click();
  await expect(page.locator('#tour-layer')).toBeHidden();
  expect(await page.evaluate(() => localStorage.getItem('thai-news-tour-settings-v1'))).toBe('complete');
  await page.reload();
  await expect(page.locator('#tour-layer')).toBeHidden();
  await expect(page.locator('#tour-restart')).toHaveAttribute('href', '#tour-dialog');
  await page.locator('#tour-restart').click();
  await expect(page.locator('#tour-layer')).toBeVisible();
  await page.locator('#tour-skip').click();
});
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
  await expect(page.locator('.brand img')).toHaveAttribute('src', /img\/logo_trans\.png/);
});
test('PWA manifest, service worker and install prompt are available', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('link[rel="manifest"]')).toHaveAttribute('href', '/manifest.webmanifest');
  await expect(page.locator('link[rel="apple-touch-icon"]')).toHaveAttribute('href', /img\/pwa\/apple-touch-icon\.png/);
  const manifest = await page.evaluate(() => fetch('/manifest.webmanifest').then(response => response.json()));
  expect(manifest.name).toBe('Thai News');
  expect(manifest.display).toBe('standalone');
  expect(manifest.icons.map(icon => `${icon.sizes}:${icon.purpose}`)).toEqual(expect.arrayContaining(['192x192:any', '512x512:any', '512x512:maskable']));
  const registration = await page.evaluate(async () => {
    const ready = await navigator.serviceWorker.ready;
    return { scope: ready.scope, active: Boolean(ready.active) };
  });
  expect(registration.scope).toBe(await page.evaluate(() => `${location.origin}/`));
  expect(registration.active).toBeTruthy();
  const cachedOfflinePage = await page.evaluate(async () => (await caches.match('/offline.html'))?.text());
  expect(cachedOfflinePage).toContain('Du är offline');
  await page.evaluate(() => {
    window.__pwaPromptCalled = false;
    const event = new Event('beforeinstallprompt');
    Object.defineProperty(event, 'prompt', { value: async () => { window.__pwaPromptCalled = true; } });
    Object.defineProperty(event, 'userChoice', { value: Promise.resolve({ outcome: 'accepted', platform: 'web' }) });
    window.dispatchEvent(event);
  });
  await expect(page.locator('#pwa-install-button')).toBeVisible();
  await page.locator('#pwa-install-button').click();
  expect(await page.evaluate(() => window.__pwaPromptCalled)).toBeTruthy();
  await expect(page.locator('#pwa-install-button')).toBeHidden();
});
test('typography preferences persist', async ({ page }) => {
  await page.goto('/settings.php');
  await expect(page.locator('#font-family')).toHaveValue('inter');
  await expect(page.locator('#font-size')).toHaveValue('medium');
  await page.locator('#font-family').selectOption('mono');
  await page.locator('#font-size').selectOption('large');
  await page.locator('button.primary[type="submit"]').click();
  await expect(page).toHaveURL(/saved=1/);
  await expect(page.locator('html')).toHaveAttribute('data-font', 'mono');
  await expect(page.locator('html')).toHaveAttribute('data-font-size', 'large');
  const typography = await page.locator('html').evaluate(element => ({
    family: getComputedStyle(element).fontFamily,
    size: getComputedStyle(element).fontSize,
  }));
  expect(typography.family).toContain('Consolas');
  expect(typography.size).toBe('18px');
  await page.goto('/');
  await expect(page.locator('html')).toHaveAttribute('data-font', 'mono');
  await expect(page.locator('html')).toHaveAttribute('data-font-size', 'large');
});
test('article cards do not render read-more links', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.read-more')).toHaveCount(0);
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

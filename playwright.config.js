const { defineConfig } = require('@playwright/test');
module.exports = defineConfig({
  testDir: './tests',
  testMatch: /.*\.spec\.js/,
  use: { baseURL: process.env.THAI_NEWS_BASE_URL || 'http://127.0.0.1:8080', trace: 'retain-on-failure' },
  projects: [
    { name: 'mobile', use: { viewport: { width: 360, height: 800 } } },
    { name: 'tablet', use: { viewport: { width: 768, height: 1024 } } },
    { name: 'desktop', use: { viewport: { width: 1440, height: 1000 } } }
  ]
});

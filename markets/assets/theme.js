(() => {
  'use strict';
  const button = document.querySelector('.theme-toggle');
  if (!button) return;
  const apply = theme => {
    document.documentElement.dataset.theme = theme;
    const dark = theme === 'dark';
    button.setAttribute('aria-pressed', String(dark));
    button.querySelector('.theme-icon').textContent = dark ? '☀' : '☾';
    const label = dark ? button.dataset.lightLabel : button.dataset.darkLabel;
    button.querySelector('.theme-label').textContent = label;
    button.setAttribute('aria-label', label);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#18312e' : '#eef3ec');
  };
  apply(document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');
  button.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    apply(next);
    try { localStorage.setItem('cm-markets-theme', next); } catch (error) {}
  });
})();

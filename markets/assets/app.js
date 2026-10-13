/**
 * Chiang Mai Market Journal | Version 1.00
 * Senast uppdaterad: 2026-10-13 11:00 | KlⒶssⓔ & Ⓐberg
 * Lättviktig vanilla JavaScript. Alla sidor fungerar även utan JS.
 */
(() => {
  'use strict';

  const search = document.getElementById('market-search');
  const cards = [...document.querySelectorAll('.market-card')];
  const filters = [...document.querySelectorAll('[data-filter]')];
  const counter = document.getElementById('market-count');
  const empty = document.getElementById('filter-empty');
  let selectedFilter = filters[0]?.dataset.filter || 'Alla';
  const allFilter = selectedFilter;
  const counterWords = document.getElementById('market-ui-translations');
  const uiText = counterWords ? JSON.parse(counterWords.textContent) : {one:'marknad', many:'marknader'};

  // Sök ord för ord så "billig mat" inte behöver stå i exakt följd.
  const normalize = (s) => String(s || '').toLocaleLowerCase('sv-SE').trim();
  const update = () => {
    if (!search || !cards.length) return;
    const words = normalize(search.value).split(/\s+/).filter(Boolean);
    let shown = 0;
    for (const card of cards) {
      const matchesCategory = selectedFilter === allFilter || card.dataset.category === selectedFilter;
      const searchable = normalize(card.dataset.search);
      const matchesWords = words.every(word => searchable.includes(word));
      const visible = matchesCategory && matchesWords;
      card.hidden = !visible;
      if (visible) shown++;
    }
    if (counter) counter.textContent = `${shown} ${shown === 1 ? uiText.one : uiText.many}`;
    if (empty) empty.hidden = shown > 0;
  };
  if (search) search.addEventListener('input', update);
  for (const filter of filters) filter.addEventListener('click', () => {
    selectedFilter = filter.dataset.filter;
    for (const button of filters) {
      button.classList.toggle('selected', button === filter);
      button.setAttribute('aria-pressed', String(button === filter));
    }
    update();
  });

  // Små förhandsvisningar innan bilder och videor laddas upp.
  const fileInput = document.querySelector('[data-photo-input]');
  const preview = document.getElementById('upload-preview');
  let objectUrls = [];
  if (fileInput && preview) fileInput.addEventListener('change', () => {
    objectUrls.forEach(URL.revokeObjectURL);
    objectUrls = [];
    preview.replaceChildren();
    const files = [...fileInput.files];
    const existing = Number(fileInput.dataset.existingCount || 0);
    const isVideo = file => file.type.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(file.name);
    const totalBytes = files.reduce((sum, file) => sum + file.size, 0);
    let warning = '';
    if (existing + files.length > uiText.maxMedia) warning = uiText.upload;
    else if (totalBytes > uiText.maxUploadBytes) warning = uiText.total;
    else if (files.some(file => isVideo(file) ? file.size > uiText.maxVideoBytes : file.size > uiText.maxImageBytes)) {
      warning = files.some(file => isVideo(file) && file.size > uiText.maxVideoBytes) ? uiText.video : uiText.image;
    }
    fileInput.setCustomValidity(warning);
    if (warning) {
      const p = document.createElement('p');
      p.className = 'upload-warning';
      p.textContent = warning;
      preview.append(p);
    }
    if (files.length > 12) {
      const p = document.createElement('p');
      p.className = 'upload-preview-note';
      p.textContent = uiText.previewMore;
      preview.append(p);
    }
    for (const file of files.slice(0, 12)) {
      const figure = document.createElement('figure');
      const visual = document.createElement(isVideo(file) ? 'video' : 'img');
      const figcaption = document.createElement('figcaption');
      const url = URL.createObjectURL(file);
      objectUrls.push(url);
      visual.src = url;
      if (isVideo(file)) {
        visual.controls = true;
        visual.muted = true;
        visual.preload = 'metadata';
        visual.playsInline = true;
      } else visual.alt = file.name;
      figcaption.textContent = file.name;
      figure.append(visual, figcaption);
      preview.append(figure);
    }
  });
})();

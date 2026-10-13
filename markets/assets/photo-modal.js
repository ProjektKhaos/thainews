(() => {
  const dialog = document.getElementById('market-photo-modal');
  if (!dialog || typeof dialog.showModal !== 'function') return;

  const items = [...document.querySelectorAll('[data-gallery-item]')];
  if (!items.length) return;
  const image = dialog.querySelector('.photo-modal-image');
  const video = dialog.querySelector('.photo-modal-video');
  const caption = dialog.querySelector('.photo-modal-caption');
  const count = dialog.querySelector('.photo-modal-count');
  const close = dialog.querySelector('[data-gallery-close]');
  const previous = dialog.querySelector('[data-gallery-prev]');
  const next = dialog.querySelector('[data-gallery-next]');
  let current = 0;
  let opener = null;

  const show = index => {
    video.pause();
    video.removeAttribute('src');
    video.load();
    image.removeAttribute('src');
    current = (index + items.length) % items.length;
    const item = items[current];
    const isVideo = item.dataset.mediaType === 'video';
    image.hidden = isVideo;
    video.hidden = !isVideo;
    if (isVideo) video.src = item.href;
    else {
      image.src = item.href;
      image.alt = item.querySelector('img')?.alt || '';
    }
    caption.textContent = item.dataset.caption || image.alt;
    count.textContent = `${current + 1} / ${items.length}`;
  };

  items.forEach((item, index) => item.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    opener = item;
    show(index);
    dialog.showModal();
  }));

  close.addEventListener('click', () => dialog.close());
  previous.addEventListener('click', () => show(current - 1));
  next.addEventListener('click', () => show(current + 1));
  dialog.addEventListener('keydown', event => {
    if (event.key === 'ArrowLeft') { event.preventDefault(); show(current - 1); }
    if (event.key === 'ArrowRight') { event.preventDefault(); show(current + 1); }
  });
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => {
    video.pause();
    video.removeAttribute('src');
    video.load();
    image.removeAttribute('src');
    opener?.focus();
  });
})();

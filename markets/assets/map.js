(() => {
  const mapElement = document.getElementById('market-map');
  const dataElement = document.getElementById('market-map-data');
  if (!mapElement || !dataElement || typeof L === 'undefined') return;

  const markets = JSON.parse(dataElement.textContent);
  const translations = JSON.parse(document.getElementById('map-translations')?.textContent || '{}');
  const map = L.map(mapElement, { scrollWheelZoom: false, zoomControl: true });
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
  }).addTo(map);

  const markers = new Map();
  const items = new Map();
  const list = document.getElementById('map-list');
  const count = document.getElementById('map-count');
  const empty = document.getElementById('map-empty');
  const search = document.getElementById('map-search');
  const filters = [...document.querySelectorAll('.map-filter')];
  let category = filters[0]?.dataset.category || 'Alla';
  const allCategory = category;

  const popup = (market) => {
    const root = document.createElement('div');
    root.className = 'map-popup';
    const image = document.createElement('img');
    image.src = market.image;
    image.alt = '';
    root.appendChild(image);
    const title = document.createElement('strong');
    title.textContent = market.name;
    root.appendChild(title);
    const details = document.createElement('small');
    details.textContent = `${market.area} · ${market.category}`;
    root.appendChild(details);
    if (market.approximate) {
      const note = document.createElement('small');
      note.className = 'map-popup-approx';
      note.textContent = translations.approximate || 'Ungefärlig plats';
      root.appendChild(note);
    }
    const link = document.createElement('a');
    link.href = market.url;
    link.textContent = translations.view || 'Visa marknaden ↗';
    root.appendChild(link);
    return root;
  };

  markets.forEach((market) => {
    const icon = L.divIcon({
      className: 'market-marker',
      html: `<span class="map-pin${market.approximate ? ' approximate' : ''}"></span>`,
      iconSize: [30, 30],
      iconAnchor: [15, 15]
    });
    const marker = L.marker([market.lat, market.lng], { icon }).bindPopup(popup(market), { maxWidth: 230 });
    marker.on('click', () => select(market.slug, false));
    marker.addTo(map);
    markers.set(market.slug, marker);
    items.set(market.slug, list.querySelector(`[data-slug="${market.slug}"]`));
  });

  function select(slug, moveMap) {
    const marker = markers.get(slug);
    if (!marker) return;
    document.querySelectorAll('.map-list-item.active').forEach((item) => item.classList.remove('active'));
    const item = items.get(slug);
    if (item) {
      item.classList.add('active');
      if (!moveMap) item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    if (moveMap) map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15), { animate: true });
    marker.openPopup();
  }

  function applyFilters(fitMap = true) {
    const term = search.value.trim().toLocaleLowerCase('sv');
    const visible = [];
    markets.forEach((market) => {
      const item = items.get(market.slug);
      const matches = (category === allCategory || market.category === category) && item.dataset.search.includes(term);
      item.hidden = !matches;
      const marker = markers.get(market.slug);
      if (matches) {
        marker.addTo(map);
        visible.push(marker.getLatLng());
      } else {
        map.removeLayer(marker);
        item.classList.remove('active');
      }
    });
    count.textContent = `${visible.length} ${visible.length === 1 ? (translations.place || 'plats') : (translations.places || 'platser')}`;
    empty.hidden = visible.length !== 0;
    if (fitMap && visible.length) {
      if (visible.length === 1) map.setView(visible[0], 15);
      else map.fitBounds(L.latLngBounds(visible), { padding: [24, 24], maxZoom: 14 });
    }
  }

  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-focus]');
    if (button) select(button.dataset.focus, true);
  });
  search.addEventListener('input', () => applyFilters());
  filters.forEach((button) => button.addEventListener('click', () => {
    category = button.dataset.category;
    filters.forEach((filter) => {
      const active = filter === button;
      filter.classList.toggle('selected', active);
      filter.setAttribute('aria-pressed', String(active));
    });
    applyFilters();
  }));

  applyFilters();
  const requested = new URLSearchParams(location.search).get('m');
  if (requested && markers.has(requested)) select(requested, true);
  window.addEventListener('resize', () => map.invalidateSize());
})();

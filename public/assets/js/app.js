// 1TAP shared scripts: menus, fetch helper, QR drawing, GPS beacon.
(function () {
  const meta = (n) => document.querySelector(`meta[name="${n}"]`)?.content || '';

  // Mobile menus
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-open-nav]')) document.body.classList.add('nav-open');
    if (e.target.closest('[data-close-nav]')) document.body.classList.remove('nav-open');
    const t = e.target.closest('[data-toggle-nav]');
    if (t) {
      const nav = document.getElementById('topnav');
      nav.classList.toggle('open');
      t.setAttribute('aria-expanded', nav.classList.contains('open'));
    }
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') document.body.classList.remove('nav-open'); });

  // Ask before destructive form posts (data-confirm="Are you sure?")
  document.addEventListener('submit', (e) => {
    const msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  const ONETAP = {
    csrf: meta('csrf'),
    base: meta('base'),

    async get(url) {
      const r = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      return r.json();
    },

    async post(url, body) {
      const r = await fetch(url, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF': ONETAP.csrf },
        body: JSON.stringify(body || {}),
      });
      return r.json();
    },

    /** Draws a QR into el as crisp SVG. Needs vendor/qrcode.js on the page. */
    drawQR(el, text) {
      if (!el) return;
      if (!text || typeof qrcode === 'undefined') { el.innerHTML = ''; return; }
      const qr = qrcode(0, 'M');
      qr.addData(text);
      qr.make();
      el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
      const svg = el.querySelector('svg');
      svg.setAttribute('role', 'img');
      svg.setAttribute('aria-label', 'Your ride QR code');
      svg.querySelectorAll('path, rect').forEach((p) => { if (p.getAttribute('fill') === 'black') p.setAttribute('fill', '#17154F'); });
    },

    /**
     * Keeps a ride QR fresh. Fetches a new code from the server whenever the
     * 30-second window ends, and counts down in the [data-qr-countdown] element.
     */
    liveQR(opts) {
      let left = 0;
      const show = () => document.querySelectorAll('[data-qr-countdown]').forEach((n) => { n.textContent = `Refreshes in 0:${String(left).padStart(2, '0')}`; });
      const tick = () => {
        if (left > 0 && left < 999) { left -= 1; if (left > 0) show(); }
        if (left <= 0) refresh();
      };
      const refresh = async () => {
        left = 999; // block double refresh while waiting
        try {
          const d = await ONETAP.get(opts.url);
          document.querySelectorAll(opts.target).forEach((el) => {
            el.closest('.qr-frame')?.classList.toggle('frozen', !d.payload);
            ONETAP.drawQR(el, d.payload || 'FROZEN');
          });
          document.querySelectorAll('[data-qr-text]').forEach((n) => { n.textContent = d.payload || ''; });
          left = d.payload ? d.expires_in : 3600;
          if (d.payload) show();
          if (!d.payload) document.querySelectorAll('[data-qr-countdown]').forEach((n) => { n.textContent = 'Frozen'; });
        } catch (err) {
          left = 5; // try again shortly
        }
      };
      refresh();
      setInterval(tick, 1000);
    },

    /** While a driver has a trip running, send the phone's location every 20 seconds. */
    gpsBeacon(url) {
      if (!('geolocation' in navigator)) return;
      let last = 0;
      navigator.geolocation.watchPosition((p) => {
        if (Date.now() - last < 20000) return;
        last = Date.now();
        ONETAP.post(url, { lat: p.coords.latitude, lng: p.coords.longitude }).catch(() => {});
      }, () => {}, { enableHighAccuracy: true, maximumAge: 15000 });
    },

    /** Leaflet map of the fleet. Shared by the passenger Track page and admin Tracking page. */
    fleetMap(elId, dataUrl, onData) {
      const el = document.getElementById(elId);
      if (!el) return;
      if (typeof L === 'undefined') {
        el.innerHTML = '<div class="map-note">The map library did not load.</div>';
        return;
      }
      el.innerHTML = '';
      const map = L.map(el, { scrollWheelZoom: false }).setView([10.672, 122.958], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18, attribution: '&copy; OpenStreetMap contributors',
      }).addTo(map);
      const colors = { on_trip: '#12A150', parked: '#5B52EE', breakdown: '#E0343F', offline: '#9A9CB4' };
      const busSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6v6"/><path d="M15 6v6"/><path d="M2 12h19.6"/><path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"/><circle cx="7" cy="18" r="2"/><path d="M9 18h5"/><circle cx="16" cy="18" r="2"/></svg>';
      const markers = {};
      let fitted = false;
      const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

      const load = async () => {
        let d;
        try { d = await ONETAP.get(dataUrl); } catch (e) { return; }
        const pts = [];
        d.units.forEach((u) => {
          if (u.latitude == null) return;
          const icon = L.divIcon({ className: '', iconSize: [30, 30], iconAnchor: [15, 15],
            html: `<div class="unit-marker" style="background:${colors[u.state] || '#5B52EE'}">${busSvg}</div>` });
          const seats = u.state === 'on_trip' ? `<br>${u.onboard}/${u.capacity} on board · ${u.seats_left} seats left` : '';
          const html = `<b>${esc(u.unit_code)}</b><br>${esc(u.route_name || u.state.replace('_', ' '))}${seats}<br><small>Last seen ${esc(u.seen)}</small>`;
          if (markers[u.unit_code]) markers[u.unit_code].setLatLng([u.latitude, u.longitude]).setIcon(icon).setPopupContent(html);
          else markers[u.unit_code] = L.marker([u.latitude, u.longitude], { icon, title: u.unit_code }).addTo(map).bindPopup(html);
          pts.push([u.latitude, u.longitude]);
        });
        if (!fitted && pts.length) { map.fitBounds(pts, { padding: [40, 40], maxZoom: 14 }); fitted = true; }
        if (onData) onData(d.units);
      };
      load();
      setInterval(load, 15000);
    },
  };

  window.ONETAP = ONETAP;
})();

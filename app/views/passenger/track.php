<?php View::partial('page-head', ['heading' => 'Track a jeepney', 'sub' => 'Where each unit is now, and how many seats are left']) ?>
<div class="row cols-main">
    <div class="card" style="padding:16px"><div class="map" id="map"><div class="map-note">Loading the map…</div></div></div>
    <div class="card">
        <h3>Units</h3>
        <div id="unitList"><p class="empty">Loading…</p></div>
        <p class="hint" style="margin-top:12px">Updates every 15 seconds. Units that are parked, broken down, or offline can't take fares.</p>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const list = document.getElementById('unitList');
  const tone = { on_trip: 'green', parked: 'brand', breakdown: 'red', offline: 'gray' };
  const label = { on_trip: 'On a trip', parked: 'Parked', breakdown: 'Breakdown', offline: 'Offline' };
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  ONETAP.fleetMap('map', '<?= e(url('/passenger/fleet')) ?>', (units) => {
    units.sort((a, b) => (a.state === 'on_trip' ? -1 : 1) - (b.state === 'on_trip' ? -1 : 1));
    list.innerHTML = units.map((u) => `<div class="list-row">
      <span class="dot ${tone[u.state] === 'brand' ? 'brand' : tone[u.state]}"></span>
      <div class="who" style="flex:1"><b>${esc(u.unit_code)}</b><span>${esc(u.route_name || 'Not on a route')}${u.state === 'on_trip' ? ' · ' + u.onboard + '/' + u.capacity : ''}</span></div>
      ${u.state === 'on_trip' ? `<span class="pill ${u.seats_left ? 'pill-green' : 'pill-amber'}">${u.seats_left ? u.seats_left + ' seats' : 'Full'}</span>` : `<span class="pill pill-${tone[u.state]}">${label[u.state]}</span>`}
    </div>`).join('');
  });
});
</script>

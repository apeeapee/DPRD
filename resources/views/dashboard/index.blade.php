@extends('layouts.app')

@section('title', 'Dashboard DPRD Jateng')
@section('page_title', 'Dashboard DPRD Jateng')
@section('page_subtitle', 'Peta wilayah + perolehan suara partai & calon')

@section('content')
<div class="kpi-grid" style="margin-bottom:14px;">
  <div class="card kpi-card">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
      <div>
        <div class="muted" style="font-weight:800; letter-spacing:.2px;">Total Suara Masuk</div>
        <div style="font-weight:900; margin-top:4px;">Akumulasi wilayah terpilih</div>
      </div>
      <div class="kpi-icon kpi-icon--blue">▦</div>
    </div>
    <div style="font-weight:900; font-size:34px; letter-spacing:.4px; margin-top:14px;" id="kpiTotalVotes">0</div>
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px;">
      <span class="chip" id="kpiDelta">+0%</span>
      <span class="muted" id="kpiDeltaText">vs update sebelumnya</span>
    </div>
  </div>

  <div class="card kpi-card">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
      <div>
        <div class="muted" style="font-weight:800; letter-spacing:.2px;">Progress Data Masuk</div>
        <div style="font-weight:900; margin-top:4px;">Suara masuk / DPT</div>
      </div>
      <div class="kpi-icon kpi-icon--green">✓</div>
    </div>
    <div style="font-weight:900; font-size:34px; letter-spacing:.4px; margin-top:14px;" id="kpiProgress">0%</div>
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px;">
      <span class="chip">Update berkala</span>
      <span class="muted">target 100%</span>
    </div>
  </div>

  <div class="card kpi-card">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
      <div>
        <div class="muted" style="font-weight:800; letter-spacing:.2px;">Partai Tertinggi</div>
        <div style="font-weight:900; margin-top:4px;">Suara terbanyak</div>
      </div>
      <div class="kpi-icon kpi-icon--amber">⚑</div>
    </div>
    <div style="font-weight:900; font-size:34px; letter-spacing:.4px; margin-top:14px;" id="kpiTopParty">—</div>
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px;">
      <span class="chip" id="kpiTopPartyRank">#1</span>
      <span class="muted" id="kpiTopPartyScope">wilayah terpilih</span>
    </div>
  </div>

  <div class="card kpi-card">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
      <div>
        <div class="muted" style="font-weight:800; letter-spacing:.2px;">Calon Tertinggi</div>
        <div style="font-weight:900; margin-top:4px;">Top calon (by suara)</div>
      </div>
      <div class="kpi-icon kpi-icon--rose">👤</div>
    </div>
    <div style="font-weight:900; font-size:28px; letter-spacing:.2px; margin-top:14px; line-height:1.15;" id="kpiTopCandidate">—</div>
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px;">
      <span class="chip" id="kpiTopCandidateBadge">Top</span>
      <span class="muted">wilayah terpilih</span>
    </div>
  </div>
</div>

<div class="grid">
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <div style="font-weight:700;">Peta Kab/Kota</div>
      <div class="muted">Klik marker untuk lihat detail</div>
    </div>
    <div style="height:12px"></div>
    <div id="map"></div>
  </div>

  <div>
    <div class="card" id="areaInfo">
      <div style="font-weight:700;" id="areaName">Pilih wilayah</div>
      <div class="muted" id="areaType">—</div>

      <div class="kpi">
        <div class="box">
          <div class="muted">DPT</div>
          <div class="val" id="kpiRegistered">0</div>
        </div>
        <div class="box">
          <div class="muted">Suara Masuk</div>
          <div class="val" id="kpiVotesCast">0</div>
        </div>
      </div>
    </div>

    <div style="height:14px"></div>

    <div class="card">
      <div style="font-weight:700; margin-bottom:10px;">Perolehan Suara Partai</div>
      <canvas id="partyChart" height="140"></canvas>
    </div>

    <div style="height:14px"></div>

    <div class="card">
      <div style="font-weight:700; margin-bottom:10px;">Top Calon (by suara)</div>
      <table>
        <thead>
          <tr>
            <th>Calon</th>
            <th>Partai</th>
            <th>Suara</th>
          </tr>
        </thead>
        <tbody id="candidateTable">
          <tr><td colspan="3" class="muted">Klik wilayah dulu…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .kpi-grid{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
  }
  .kpi-card{ min-height: 170px; }
  .kpi-icon{
    width: 44px;
    height: 44px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,.06);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight: 900;
    color: var(--text);
  }
  .kpi-icon--blue{ background: rgba(37,99,235,.16); border-color: rgba(37,99,235,.22); }
  .kpi-icon--green{ background: rgba(34,197,94,.14); border-color: rgba(34,197,94,.22); }
  .kpi-icon--amber{ background: rgba(245,158,11,.14); border-color: rgba(245,158,11,.22); }
  .kpi-icon--rose{ background: rgba(244,63,94,.14); border-color: rgba(244,63,94,.22); }

  @media (max-width: 1200px){
    .kpi-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 640px){
    .kpi-grid{ grid-template-columns: 1fr; }
  }

  /* Marker + popup style (mendekati referensi KPU) */
  .area-marker__dot{
    width: var(--s, 18px);
    height: var(--s, 18px);
    display:block;
    background: var(--c, #ef4444);
    border: 2px solid #fff;
    border-radius: 999px;
    box-shadow: 0 6px 16px rgba(15, 27, 51, .18);
  }

  .leaflet-popup-content{ margin: 10px 12px; }
  .leaflet-popup-content-wrapper{ border-radius: 12px; }

  .area-popup{ min-width: 180px; }
  .area-popup__label{ font-size: 12px; color:#6b7280; margin-bottom: 2px; }
  .area-popup__name{ font-weight: 800; color:#0f1b33; margin-bottom: 6px; }
  .area-popup__meta{ font-size: 12px; color:#374151; margin-bottom: 8px; line-height: 1.35; }
  .area-popup__btn{
    width: 100%;
    border: 0;
    border-radius: 10px;
    padding: 8px 10px;
    background: var(--accent);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
  }
  .area-popup__btn:hover{ filter: brightness(.95); }

  .map-legend{
    background: rgba(255,255,255,.95);
    padding: 10px 10px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(15, 27, 51, .10);
    font-size: 12px;
    color: #111827;
  }
  .map-legend__title{ font-weight: 800; margin-bottom: 6px; }
  .map-legend__row{ display:flex; align-items:center; gap:8px; margin: 4px 0; }
  .map-legend__swatch{ width: 12px; height: 12px; border-radius: 3px; border:1px solid rgba(0,0,0,.08); }
</style>

@endpush

@push('scripts')

<script>
  const fmt = new Intl.NumberFormat('id-ID');

  const DEFAULT_YEAR = 2024;

  const areaName = document.getElementById('areaName');
  const areaType = document.getElementById('areaType');
  const kpiRegistered = document.getElementById('kpiRegistered');
  const kpiVotesCast = document.getElementById('kpiVotesCast');
  const candidateTable = document.getElementById('candidateTable');

  const kpiTotalVotes = document.getElementById('kpiTotalVotes');
  const kpiDelta = document.getElementById('kpiDelta');
  const kpiProgress = document.getElementById('kpiProgress');
  const kpiTopParty = document.getElementById('kpiTopParty');
  const kpiTopPartyRank = document.getElementById('kpiTopPartyRank');
  const kpiTopCandidate = document.getElementById('kpiTopCandidate');

  // Map init - fokus hanya 3 kabupaten
  const TARGET_REGENCIES = [
    'Kabupaten Karanganyar',
    'Kabupaten Sragen',
    'Kabupaten Wonogiri',
  ];

  // Bounding box sekitar Karanganyar, Sragen, Wonogiri
  const MAP_BOUNDS = L.latLngBounds(
    [-8.10, 110.70],
    [-7.20, 111.35]
  );

  const map = L.map('map', {
    maxBounds: MAP_BOUNDS,
    maxBoundsViscosity: 1.0,
    minZoom: 9
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    minZoom: 9,
    noWrap: true
  }).addTo(map);

  function lockMinZoomToBounds(bounds) {
    // Kunci zoom-out agar tidak melebar keluar area target.
    const z = map.getBoundsZoom(bounds, false, [12, 12]);
    if (Number.isFinite(z)) {
      map.setMinZoom(Math.max(map.getMinZoom() ?? 0, z));
    }
  }

  map.fitBounds(MAP_BOUNDS, { padding: [12, 12] });
  lockMinZoomToBounds(MAP_BOUNDS);

  let partyChart;
  function initPartyChart() {
    const ctx = document.getElementById('partyChart');
    partyChart = new Chart(ctx, {
      type: 'bar',
      data: { labels: [], datasets: [{ label: 'Suara', data: [] }] },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }
  initPartyChart();

  // featureGroup punya getBounds() => bisa fit ke marker yang tampil
  let markersLayer = L.featureGroup().addTo(map);
  let areasGeoLayer;
  let lastAreas = [];

  function escapeHtml(str) {
    return String(str ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  // Skala warna ala choropleth (berdasarkan DPT)
  function getDptColor(v) {
    const n = Number(v ?? 0);
    if (n >= 1000000) return '#7f1d1d';
    if (n >= 600000) return '#991b1b';
    if (n >= 400000) return '#b91c1c';
    if (n >= 200000) return '#ef4444';
    if (n >= 100000) return '#fb7185';
    if (n >= 50000) return '#fdba74';
    return '#fed7aa';
  }

  function getMarkerSize(v) {
    const n = Number(v ?? 0);
    if (n >= 1000000) return 24;
    if (n >= 600000) return 22;
    if (n >= 400000) return 20;
    if (n >= 200000) return 18;
    if (n >= 100000) return 16;
    if (n >= 50000) return 14;
    return 12;
  }

  // Legend (DPT)
  const legend = L.control({ position: 'bottomright' });
  legend.onAdd = function () {
    const div = L.DomUtil.create('div', 'map-legend');
    const rows = [
      { label: '> 1.000.000', color: getDptColor(1000000) },
      { label: '600.000 – 1.000.000', color: getDptColor(600000) },
      { label: '400.000 – 600.000', color: getDptColor(400000) },
      { label: '200.000 – 400.000', color: getDptColor(200000) },
      { label: '100.000 – 200.000', color: getDptColor(100000) },
      { label: '50.000 – 100.000', color: getDptColor(50000) },
      { label: '< 50.000', color: getDptColor(0) }
    ];

    div.innerHTML = `
      <div class="map-legend__title">DPT (orang)</div>
      ${rows.map(r => `
        <div class="map-legend__row">
          <span class="map-legend__swatch" style="background:${r.color}"></span>
          <span>${r.label}</span>
        </div>
      `).join('')}
    `;
    return div;
  };
  legend.addTo(map);

  // Delegasi klik tombol di popup
  map.on('popupopen', (e) => {
    const el = e.popup?.getElement?.();
    const btn = el?.querySelector?.('button[data-area-id]');
    if (!btn) return;
    btn.addEventListener('click', () => {
      const areaId = btn.getAttribute('data-area-id');
      if (!areaId) return;
      loadAreaDetail(areaId);
      map.closePopup();
    }, { once: true });
  });

  function normalizeAreaName(name) {
    return String(name ?? '')
      .toLowerCase()
      .replaceAll('kabupaten', '')
      .replaceAll('kab.', '')
      .replaceAll('kota', '')
      .replaceAll('.', '')
      .replaceAll('-', ' ')
      .replaceAll('_', ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function getFeatureName(props) {
    const p = props || {};
    return (
      p.name ??
      p.shapeName ??
      p.NAME ??
      p.WADMKK ??
      p.NAMOBJ ??
      p.KAB_KOTA ??
      p.KABKOT ??
      p.ADM2_NAME ??
      p.ADM2 ??
      p.NAME_2 ??
      p.NAME_3 ??
      p.NAMA ??
      p.Nama ??
      ''
    );
  }

  function clearAreaLayers() {
    markersLayer.clearLayers();
    if (areasGeoLayer) {
      areasGeoLayer.remove();
      areasGeoLayer = undefined;
    }
  }

  function openAreaPopup(latlng, area) {
    const dpt = area?.summary?.registered_voters ?? 0;
    const votesCast = area?.summary?.votes_cast ?? 0;
    const content = `
      <div class="area-popup">
        <div class="area-popup__label">Kabupaten/Kota</div>
        <div class="area-popup__name">${escapeHtml(area?.name ?? '—')}</div>
        <div class="area-popup__meta">
          DPT: <b>${fmt.format(dpt)}</b><br/>
          Suara Masuk: <b>${fmt.format(votesCast)}</b>
        </div>
        <button type="button" class="area-popup__btn" data-area-id="${escapeHtml(area?.id)}">Masuk ke Kab/Kota</button>
      </div>
    `;
    L.popup({ closeButton: true, autoPan: true })
      .setLatLng(latlng)
      .setContent(content)
      .openOn(map);
  }

  function renderChoropleth(geojson, areas) {
    const byName = new Map();
    (areas || []).forEach(a => {
      byName.set(normalizeAreaName(a.name), a);
    });

    // Filter GeoJSON hanya untuk 3 kabupaten target
    const filteredFeatures = (geojson?.features || []).filter(feature => {
      const rawName = getFeatureName(feature?.properties);
      return TARGET_REGENCIES.some(target =>
        normalizeAreaName(rawName) === normalizeAreaName(target)
      );
    });

    const baseStyle = (feature) => {
      const nm = normalizeAreaName(getFeatureName(feature?.properties));
      const a = byName.get(nm);
      const dpt = a?.summary?.registered_voters ?? 0;
      return {
        color: '#ffffff',
        weight: 1,
        opacity: 1,
        fillColor: getDptColor(dpt),
        fillOpacity: 0.85,
      };
    };

    const highlightStyle = {
      weight: 2,
      color: '#111827',
      fillOpacity: 0.95,
    };

    areasGeoLayer = L.geoJSON({ type: 'FeatureCollection', features: filteredFeatures }, {
      style: baseStyle,
      onEachFeature: (feature, layer) => {
        const rawName = getFeatureName(feature?.properties);
        const nm = normalizeAreaName(rawName);
        const a = byName.get(nm);

        const tooltipName = String(rawName || a?.name || '').trim();
        if (tooltipName) {
          layer.bindTooltip(escapeHtml(tooltipName), {
            sticky: true,
            direction: 'auto',
            opacity: 0.9,
          });
        }

        layer.on('mouseover', () => layer.setStyle(highlightStyle));
        layer.on('mouseout', () => areasGeoLayer?.resetStyle?.(layer));
        layer.on('click', (e) => {
          if (!a?.id) {
            // Jika tidak match, tetap tampilkan nama wilayahnya
            L.popup({ closeButton: true, autoPan: true })
              .setLatLng(e.latlng)
              .setContent(`<div class="area-popup"><div class="area-popup__name">${escapeHtml(rawName || 'Wilayah')}</div><div class="area-popup__meta">Wilayah ini belum terhubung dengan data.</div></div>`)
              .openOn(map);
            return;
          }
          openAreaPopup(e.latlng, a);
        });
      }
    }).addTo(map);

    // Fit ke batas layer
    try {
      const b = areasGeoLayer.getBounds();
      if (b.isValid()) {
        map.fitBounds(b, { padding: [12, 12] });
        lockMinZoomToBounds(b);
      }
    } catch (_) {}
  }

  async function loadAreas() {
    const year = DEFAULT_YEAR;
    clearAreaLayers();

    const res = await fetch(`/api/areas?year=${encodeURIComponent(year)}`);
    const json = await res.json();

    // Filter hanya 3 kabupaten target
    const areas = (json.data || []).filter(a =>
      TARGET_REGENCIES.some(target =>
        normalizeAreaName(a.name) === normalizeAreaName(target)
      )
    );

    lastAreas = areas;

    // 1) Coba load batas kab/kota (GeoJSON) lalu render choropleth.
    // Simpan file GeoJSON di: public/geo/jateng_kabkota.geojson
    try {
      const geoRes = await fetch('/geo/jateng_kabkota.geojson', { cache: 'no-store' });
      if (!geoRes.ok) throw new Error(`GeoJSON HTTP ${geoRes.status}`);
      const geo = await geoRes.json();
      if (!geo?.features?.length) throw new Error('GeoJSON kosong');
      renderChoropleth(geo, areas);
      if (areas[0]?.id) await loadAreaDetail(areas[0].id);
      return;
    } catch (err) {
      console.warn('Gagal load GeoJSON, fallback ke marker:', err);
    }

    // 2) Fallback marker jika GeoJSON belum tersedia
    areas.forEach(a => {
      const lat = a.lat ?? a.latitude ?? -7.1;
      const lng = a.lng ?? a.longitude ?? 110.2;

      const dpt = a.summary?.registered_voters ?? 0;
      const votesCast = a.summary?.votes_cast ?? 0;
      const color = getDptColor(dpt);
      const size = getMarkerSize(dpt);

      const icon = L.divIcon({
        className: '',
        html: `<span class="area-marker__dot" style="--c:${color};--s:${size}px"></span>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -size / 2]
      });

      const m = L.marker([lat, lng], { icon }).addTo(markersLayer);
      m.bindPopup(`
        <div class="area-popup">
          <div class="area-popup__label">Kabupaten/Kota</div>
          <div class="area-popup__name">${escapeHtml(a.name)}</div>
          <div class="area-popup__meta">
            DPT: <b>${fmt.format(dpt)}</b><br/>
            Suara Masuk: <b>${fmt.format(votesCast)}</b>
          </div>
          <button type="button" class="area-popup__btn" data-area-id="${escapeHtml(a.id)}">Masuk ke Kab/Kota</button>
        </div>
      `, { closeButton: true, autoPan: true });
    });

    // Fit ke marker yang tampil lalu lock minZoom
    try {
      const b = markersLayer.getBounds();
      if (b.isValid()) {
        map.fitBounds(b, { padding: [12, 12] });
        lockMinZoomToBounds(b);
      }
    } catch (_) {}

    if (areas[0]?.id) await loadAreaDetail(areas[0].id);
  }

  async function loadAreaDetail(areaId) {
    const year = DEFAULT_YEAR;

    const res = await fetch(`/api/areas/${areaId}?year=${encodeURIComponent(year)}`);
    const json = await res.json();

    areaName.textContent = json.area?.name ?? '—';
    areaType.textContent = json.area?.type ?? '—';
    const registered = Number(json.summary?.registered_voters ?? 0);
    const votesCast = Number(json.summary?.votes_cast ?? 0);

    kpiRegistered.textContent = fmt.format(registered);
    kpiVotesCast.textContent = fmt.format(votesCast);

    if (kpiTotalVotes) kpiTotalVotes.textContent = fmt.format(votesCast);
    if (kpiDelta) kpiDelta.textContent = '+0%';
    if (kpiProgress) {
      const pct = registered > 0 ? Math.round((votesCast / registered) * 100) : 0;
      kpiProgress.textContent = `${Math.max(0, Math.min(100, pct))}%`;
    }

    // Party chart
    const pr = json.party_results || [];
    partyChart.data.labels = pr.map(x => x.party_name);
    partyChart.data.datasets[0].data = pr.map(x => x.votes);
    partyChart.update();

    // Top party
    if (kpiTopParty) {
      const topParty = pr.reduce((acc, cur) => {
        if (!acc) return cur;
        return (Number(cur.votes ?? 0) > Number(acc.votes ?? 0)) ? cur : acc;
      }, null);
      kpiTopParty.textContent = topParty?.party_name ?? '—';
      if (kpiTopPartyRank) kpiTopPartyRank.textContent = '#1';
    }

    // Candidate table (Top 10)
    const cr = (json.candidate_results || []).slice(0, 10);
    if (cr.length === 0) {
      candidateTable.innerHTML = `<tr><td colspan="3" class="muted">Tidak ada data calon.</td></tr>`;
      if (kpiTopCandidate) kpiTopCandidate.textContent = '—';
      return;
    }

    // Top candidate
    if (kpiTopCandidate) {
      const top = cr[0];
      kpiTopCandidate.textContent = top?.candidate_name ?? '—';
    }
    candidateTable.innerHTML = cr.map(r => `
      <tr>
        <td>${r.candidate_name}</td>
        <td>${r.party_name}</td>
        <td>${fmt.format(r.votes)}</td>
      </tr>
    `).join('');
  }

  // start
  loadAreas();
</script>
@endpush

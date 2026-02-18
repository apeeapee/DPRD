@extends('layouts.app')

@section('title', 'Dashboard User')
@section('hide_sidebar', '1')
@section('hide_topbar', '1')

@section('content')
  <div class="user-shell">
    <div class="user-topsearch card">
      <input class="user-topsearch__input" type="search" placeholder="Cari kabupaten, partai, caleg" />
      <select class="user-topsearch__select" aria-label="Wilayah">
        <option value="all">Wilayah: Semua wilayah</option>
      </select>

      <div class="user-pill">
        <span class="user-pill__dot"></span>
        <span class="user-pill__text">{{ auth()->user()->name ?? 'User' }}</span>
      </div>

      <form method="POST" action="{{ route('logout') }}" style="margin:0;">
        @csrf
        <button class="btn btn--ghost" type="submit">Logout</button>
      </form>
    </div>

    <div class="user-hero">
      <div class="user-hero__left">
        <div class="user-hero__title">Selamat datang, {{ auth()->user()->name ?? 'User' }}</div>
        <div class="muted">Ringkasan perolehan suara berdasarkan data terbaru.</div>
      </div>
      <div class="user-hero__right">
        <span class="chip">Light theme</span>
        <span class="chip">Update realtime</span>
      </div>
    </div>

    <div class="user-kpi-grid">
      <div class="card user-kpi">
        <div class="user-kpi__label">Total Wilayah</div>
        <div class="user-kpi__value">{{ number_format($kpi['areas_total'] ?? 0, 0, ',', '.') }}</div>
        <div class="muted">Karanganyar, Sragen, Wonogiri</div>
      </div>

      <div class="card user-kpi">
        <div class="user-kpi__label">Total Partai</div>
        <div class="user-kpi__value">{{ number_format($kpi['parties_total'] ?? 0, 0, ',', '.') }}</div>
        <div class="muted">Data master partai</div>
      </div>

      <div class="card user-kpi">
        <div class="user-kpi__label">Total Calon</div>
        <div class="user-kpi__value">{{ number_format($kpi['candidates_total'] ?? 0, 0, ',', '.') }}</div>
        <div class="muted">Data master calon</div>
      </div>

      <div class="card user-kpi">
        <div class="user-kpi__label">Progress Suara Masuk</div>
        <div class="user-kpi__value">{{ number_format($kpi['progress_pct'] ?? 0, 0, ',', '.') }}%</div>
        <div class="muted">{{ number_format($kpi['votes_cast'] ?? 0, 0, ',', '.') }} / {{ number_format($kpi['registered_voters'] ?? 0, 0, ',', '.') }}</div>
      </div>
    </div>

    <div class="user-grid">
      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
          <div>
            <div style="font-weight:900;">Peta Kab/Kota</div>
            <div class="muted" style="margin-top:6px;">Klik wilayah untuk lihat detail</div>
          </div>
          <span class="chip">Jateng (3 kabupaten)</span>
        </div>

        <div style="height:12px"></div>
        <div id="userMap" class="user-map"></div>
      </div>

      <div class="card">
        <div style="font-weight:900;" id="userAreaName">Pilih wilayah</div>
        <div class="muted" style="margin-top:6px;" id="userAreaType">—</div>

        <div class="user-area-kpi">
          <div class="user-area-kpi__box">
            <div class="muted">DPT</div>
            <div class="user-area-kpi__val" id="userKpiRegistered">0</div>
          </div>
          <div class="user-area-kpi__box">
            <div class="muted">Suara Masuk</div>
            <div class="user-area-kpi__val" id="userKpiVotesCast">0</div>
          </div>
        </div>

        <div style="height:14px"></div>

        <div style="font-weight:900;">Distribusi Suara Partai</div>
        <div class="muted" style="margin-top:6px;" id="userPartyScope">Akumulasi 3 kabupaten target</div>
        <div style="height:10px"></div>
        <canvas id="userPartyChart" height="140"></canvas>

        <div style="height:14px"></div>

        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
          <div style="font-weight:900;">Top Calon (by suara)</div>
          <span class="chip" id="userCandidateScope">wilayah terpilih</span>
        </div>
        <div style="height:10px"></div>
        <canvas id="userCandidateChart" height="220"></canvas>
      </div>
    </div>

    <div style="height:14px"></div>

    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
        <div>
          <div style="font-weight:900;">Detail Suara per TPS</div>
          <div class="muted" style="margin-top:6px;">Partai → Caleg → TPS (dinamis) + Akumulasi</div>
        </div>
        <span class="chip">Tips: gunakan sticky header untuk tabel panjang</span>
      </div>

      <div style="height:12px"></div>

      <div class="user-village-filter">
        <select id="userSubdistrictSelect" class="user-village-filter__select" aria-label="Kecamatan">
          <option value="">Pilih Kecamatan</option>
        </select>
        <select id="userVillageSelect" class="user-village-filter__select" aria-label="Desa" disabled>
          <option value="">Pilih Desa</option>
        </select>
      </div>

      <div style="height:8px"></div>
      <div class="muted" id="userVillageChartHint">Pilih kecamatan dan desa untuk memuat tabel.</div>
      <div style="height:12px"></div>

      <div class="user-table-wrap" id="userVillageTpsWrap" style="display:none;"></div>
    </div>
  </div>
@endsection

@push('styles')
<style>
  .user-shell{ max-width: 1180px; margin: 0 auto; }

  .user-topsearch{
    display:flex;
    gap: 10px;
    align-items:center;
    padding: 10px;
  }
  .user-topsearch__input{ flex: 1; min-width: 260px; }
  .user-topsearch__select{ min-width: 220px; }

  .user-pill{
    display:inline-flex;
    align-items:center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: rgba(2, 6, 23, .02);
    font-weight: 900;
    color: var(--text);
    white-space: nowrap;
  }
  .user-pill__dot{
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: rgba(37,99,235,.35);
    border: 1px solid rgba(37,99,235,.22);
    display:inline-block;
  }

  .user-hero{ display:flex; justify-content:space-between; align-items:flex-start; gap: 14px; margin: 14px 0; }
  .user-hero__title{ font-weight: 950; font-size: 22px; letter-spacing: .2px; line-height: 1.2; }
  .user-hero__right{ display:flex; gap: 10px; align-items:center; flex-wrap:wrap; justify-content:flex-end; }

  .user-kpi-grid{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
  }
  .user-kpi{ padding: 14px; }
  .user-kpi__label{ font-weight: 900; font-size: 12px; letter-spacing: .3px; color: var(--muted); }
  .user-kpi__value{ font-weight: 950; font-size: 28px; margin-top: 8px; letter-spacing: .2px; line-height: 1.1; }

  .user-grid{ display:grid; grid-template-columns: 1.15fr .85fr; gap: 14px; align-items:start; }

  .user-map{
    height: 440px;
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid var(--border);
  }

  .user-area-kpi{ display:flex; gap: 10px; margin-top: 12px; }
  .user-area-kpi__box{
    flex: 1;
    background: linear-gradient(180deg, rgba(37,99,235,.06), rgba(249,115,22,.03));
    border: 1px solid rgba(15,23,42,.06);
    border-radius: 14px;
    padding: 10px;
  }
  .user-area-kpi__val{ font-weight: 950; font-size: 18px; letter-spacing: .2px; margin-top: 2px; }

  .user-village-filter{ display:grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .user-village-filter__select{ width:100%; }

  .user-table-wrap{
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow:auto;
    max-height: 420px;
    background: rgba(2, 6, 23, .02);
  }
  .user-tps-table{ width:100%; border-collapse: separate; border-spacing: 0; min-width: 680px; }
  .user-tps-table th, .user-tps-table td{ padding: 10px 12px; border-bottom: 1px solid rgba(15, 23, 42, .06); vertical-align: middle; }
  .user-tps-table thead th{
    position: sticky;
    top: 0;
    z-index: 2;
    background: rgba(15, 23, 42, .08);
    backdrop-filter: blur(6px);
    text-align: left;
    font-weight: 900;
    color: var(--text);
    white-space: nowrap;
  }
  .user-tps-table td{ color: var(--text); }
  .user-tps-num{ text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .user-tps-acc{ font-weight: 950; }
  .user-party-pill{
    display:inline-flex;
    align-items:center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, .10);
    background: rgba(37, 99, 235, .08);
    font-weight: 900;
    white-space: nowrap;
  }
  .user-party-pill__dot{ width:10px; height:10px; border-radius:999px; background: rgba(220, 38, 38, .85); display:inline-block; }

  .user-list{ display:flex; flex-direction:column; gap: 10px; }
  .user-list__item{ padding: 10px; border-radius: 14px; border: 1px solid rgba(15, 23, 42, .06); background: rgba(2, 6, 23, .02); }
  .user-list__title{ font-weight: 900; }

  @media (max-width: 1100px){
    .user-kpi-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .user-grid{ grid-template-columns: 1fr; }
    .user-map{ height: 380px; }
  }
  @media (max-width: 640px){
    .user-topsearch{ flex-wrap:wrap; }
    .user-topsearch__input{ min-width: 100%; }
    .user-topsearch__select{ min-width: 100%; }
    .user-kpi-grid{ grid-template-columns: 1fr; }
    .user-village-filter{ grid-template-columns: 1fr; }
  }
</style>
@endpush

@push('scripts')
<script>
  const fmt = new Intl.NumberFormat('id-ID');
  const DEFAULT_YEAR = 2024;
  const TARGET_REGENCIES = [
    'Kabupaten Karanganyar',
    'Kabupaten Sragen',
    'Kabupaten Wonogiri',
  ];

  const partyLabels = @json($partyChart['labels'] ?? []);
  const partyValues = @json($partyChart['values'] ?? []);
  const initialCandidates = @json($recentCandidates ?? []);

  const elAreaName = document.getElementById('userAreaName');
  const elAreaType = document.getElementById('userAreaType');
  const elRegistered = document.getElementById('userKpiRegistered');
  const elVotesCast = document.getElementById('userKpiVotesCast');
  const elPartyScope = document.getElementById('userPartyScope');
  const elCandidateScope = document.getElementById('userCandidateScope');
  const elCandidateCanvas = document.getElementById('userCandidateChart');

  // Chart init (will be updated on area click)
  let partyChart;
  let candidateChart;
  const ctx = document.getElementById('userPartyChart');
  if (ctx) {
    partyChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: partyLabels,
        datasets: [{ label: 'Suara', data: partyValues }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  function renderVillageTpsTable(data) {
    const wrap = document.getElementById('userVillageTpsWrap');
    if (!wrap) return;

    const tps = data?.tps || [];
    const parties = data?.parties || [];

    if (!tps.length || !parties.length) {
      wrap.style.display = 'none';
      wrap.innerHTML = '';
      return;
    }

    const tpsIds = tps.map(t => String(t.id));
    const tpsCodes = tps.map(t => String(t.code ?? 'TPS'));

    const thead = `
      <thead>
        <tr>
          <th style="min-width:170px;">Partai</th>
          <th style="min-width:220px;">Caleg</th>
          ${tpsCodes.map(c => `<th class="user-tps-num">${escapeHtml(c)}</th>`).join('')}
          <th class="user-tps-num">Akumulasi</th>
        </tr>
      </thead>
    `;

    const rows = [];
    for (const p of parties) {
      const partyName = p.party_name ?? '—';
      const candidates = p.candidates || [];
      for (const c of candidates) {
        const votesByTps = c.votes_by_tps || {};
        const votesCells = tpsIds.map(tid => {
          const v = Number(votesByTps[tid] ?? votesByTps[Number(tid)] ?? 0);
          return `<td class="user-tps-num">${fmt.format(v)}</td>`;
        }).join('');

        const total = Number(c.total ?? 0);
        rows.push(`
          <tr>
            <td>
              <span class="user-party-pill"><span class="user-party-pill__dot"></span>${escapeHtml(partyName)}</span>
            </td>
            <td style="font-weight:800;">${escapeHtml(c.candidate_name ?? '—')}</td>
            ${votesCells}
            <td class="user-tps-num user-tps-acc">${fmt.format(total)}</td>
          </tr>
        `);
      }
    }

    wrap.innerHTML = `
      <table class="user-tps-table">
        ${thead}
        <tbody>${rows.join('')}</tbody>
      </table>
    `;
    wrap.style.display = '';
  }

  function renderCandidateChart(rows) {
    const items = (rows || []).slice(0, 10);
    const labels = items.map(r => {
      const name = (r.candidate_name ?? r.name ?? '—');
      const party = (r.party_name ?? r.party ?? '—');
      return `${name} (${party})`;
    });
    const values = items.map(r => Number(r.votes ?? 0));

    if (!elCandidateCanvas) return;
    if (!candidateChart) {
      candidateChart = new Chart(elCandidateCanvas, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Suara', data: values }] },
        options: {
          indexAxis: 'y',
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (ctx) => ` ${fmt.format(Number(ctx.parsed.x ?? 0))} suara`
              }
            }
          },
          scales: {
            x: { beginAtZero: true, ticks: { callback: (v) => fmt.format(v) } },
            y: { ticks: { autoSkip: false } }
          }
        }
      });
      return;
    }

    candidateChart.data.labels = labels;
    candidateChart.data.datasets[0].data = values;
    candidateChart.update();
  }

  async function initVillageVoteChartFilters() {
    const elSubdistrict = document.getElementById('userSubdistrictSelect');
    const elVillage = document.getElementById('userVillageSelect');
    const elHint = document.getElementById('userVillageChartHint');
    if (!elSubdistrict || !elVillage) return;

    function setHint(text) {
      if (elHint) elHint.textContent = text;
    }

    function resetVillageSelect() {
      elVillage.innerHTML = '<option value="">Pilih Desa</option>';
      elVillage.disabled = true;
    }

    const wrap = document.getElementById('userVillageTpsWrap');
    if (wrap) {
      wrap.style.display = 'none';
      wrap.innerHTML = '';
    }

    resetVillageSelect();
    let activeRegencyName = null;

    async function loadSubdistricts(regencyName) {
      activeRegencyName = regencyName || null;
      resetVillageSelect();
      if (wrap) { wrap.style.display = 'none'; wrap.innerHTML = ''; }

      const regenciesList = activeRegencyName ? [activeRegencyName] : TARGET_REGENCIES;
      const label = activeRegencyName ? `Memuat daftar kecamatan (${activeRegencyName})…` : 'Memuat daftar kecamatan…';
      setHint(label);

      const regenciesParam = encodeURIComponent(regenciesList.join(','));
      const res = await fetch(`/api/subdistricts?regencies=${regenciesParam}`);
      const json = await res.json();
      const items = json.data || [];

      const placeholder = activeRegencyName ? `Pilih Kecamatan (${activeRegencyName})` : 'Pilih Kecamatan';
      elSubdistrict.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>`;
      items.forEach(sd => {
        const opt = document.createElement('option');
        opt.value = String(sd.id);
        opt.textContent = activeRegencyName ? sd.name : `${sd.regency_name} — ${sd.name}`;
        elSubdistrict.appendChild(opt);
      });

      setHint('Pilih kecamatan dan desa untuk memuat tabel.');
    }

    // Called from map selection
    window.setVillageRegencyFilter = async (regencyName) => {
      const cleaned = String(regencyName || '').trim();
      if (!cleaned) {
        await loadSubdistricts(null);
        return;
      }

      const ok = TARGET_REGENCIES.some(t => normalizeAreaName(t) === normalizeAreaName(cleaned));
      await loadSubdistricts(ok ? cleaned : null);
    };

    await loadSubdistricts(null);

    elSubdistrict.addEventListener('change', async () => {
      const subdistrictId = elSubdistrict.value;
      resetVillageSelect();
      if (wrap) { wrap.style.display = 'none'; wrap.innerHTML = ''; }

      if (!subdistrictId) {
        setHint('Pilih kecamatan dan desa untuk memuat tabel.');
        return;
      }

      setHint('Memuat daftar desa…');
      const vRes = await fetch(`/api/villages?subdistrict_id=${encodeURIComponent(subdistrictId)}`);
      const vJson = await vRes.json();
      const villages = vJson.data || [];

      villages.forEach(v => {
        const opt = document.createElement('option');
        opt.value = String(v.id);
        opt.textContent = v.name;
        elVillage.appendChild(opt);
      });
      elVillage.disabled = villages.length === 0;

      setHint(villages.length ? 'Pilih desa untuk memuat tabel.' : 'Tidak ada desa untuk kecamatan ini.');
    });

    elVillage.addEventListener('change', async () => {
      const villageId = elVillage.value;
      if (wrap) { wrap.style.display = 'none'; wrap.innerHTML = ''; }
      if (!villageId) {
        setHint('Pilih desa untuk memuat tabel.');
        return;
      }

      setHint('Memuat detail suara per TPS…');
      const r = await fetch(`/api/village-tps-results?year=${encodeURIComponent(DEFAULT_YEAR)}&village_id=${encodeURIComponent(villageId)}`);
      const j = await r.json();

      renderVillageTpsTable(j.data);

      const grandTotal = Number(j.data?.grand_total ?? 0);
      setHint(`Total suara masuk desa: ${fmt.format(grandTotal)}`);
    });
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

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

  // Map init
  const MAP_BOUNDS = L.latLngBounds(
    [-8.10, 110.70],
    [-7.20, 111.35]
  );

  const map = L.map('userMap', {
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
    const z = map.getBoundsZoom(bounds, false, [12, 12]);
    if (Number.isFinite(z)) {
      map.setMinZoom(Math.max(map.getMinZoom() ?? 0, z));
    }
  }

  map.fitBounds(MAP_BOUNDS, { padding: [12, 12] });
  lockMinZoomToBounds(MAP_BOUNDS);

  let markersLayer = L.featureGroup().addTo(map);
  let areasGeoLayer;

  function clearAreaLayers() {
    markersLayer.clearLayers();
    if (areasGeoLayer) {
      areasGeoLayer.remove();
      areasGeoLayer = undefined;
    }
  }

  function openAreaPopup(latlng, area) {
    try {
      if (area?.name && typeof window.setVillageRegencyFilter === 'function') {
        window.setVillageRegencyFilter(area.name);
      }
    } catch (_) {}

    const dpt = area?.summary?.registered_voters ?? 0;
    const votesCast = area?.summary?.votes_cast ?? 0;
    const content = `
      <div style="min-width:180px;">
        <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">Kabupaten/Kota</div>
        <div style="font-weight:900; color:#0f1b33; margin-bottom:6px;">${escapeHtml(area?.name ?? '—')}</div>
        <div style="font-size:12px; color:#374151; margin-bottom:8px; line-height:1.35;">
          DPT: <b>${fmt.format(dpt)}</b><br/>
          Suara Masuk: <b>${fmt.format(votesCast)}</b>
        </div>
        <button type="button" data-area-id="${escapeHtml(area?.id)}" style="width:100%; border:0; border-radius:10px; padding:8px 10px; background: var(--accent); color:#fff; font-weight:800; cursor:pointer;">Lihat detail</button>
      </div>
    `;
    L.popup({ closeButton: true, autoPan: true })
      .setLatLng(latlng)
      .setContent(content)
      .openOn(map);
  }

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

  function renderChoropleth(geojson, areas) {
    const byName = new Map();
    (areas || []).forEach(a => byName.set(normalizeAreaName(a.name), a));

    const filteredFeatures = (geojson?.features || []).filter(feature => {
      const rawName = getFeatureName(feature?.properties);
      return TARGET_REGENCIES.some(target => normalizeAreaName(rawName) === normalizeAreaName(target));
    });

    const baseStyle = (feature) => {
      const nm = normalizeAreaName(getFeatureName(feature?.properties));
      const a = byName.get(nm);
      const dpt = a?.summary?.registered_voters ?? 0;
      return { color: '#ffffff', weight: 1, opacity: 1, fillColor: getDptColor(dpt), fillOpacity: 0.85 };
    };

    const highlightStyle = { weight: 2, color: '#111827', fillOpacity: 0.95 };

    areasGeoLayer = L.geoJSON({ type: 'FeatureCollection', features: filteredFeatures }, {
      style: baseStyle,
      onEachFeature: (feature, layer) => {
        const rawName = getFeatureName(feature?.properties);
        const nm = normalizeAreaName(rawName);
        const a = byName.get(nm);

        const tooltipName = String(rawName || a?.name || '').trim();
        if (tooltipName) {
          layer.bindTooltip(escapeHtml(tooltipName), { sticky: true, direction: 'auto', opacity: 0.9 });
        }

        layer.on('mouseover', () => layer.setStyle(highlightStyle));
        layer.on('mouseout', () => areasGeoLayer?.resetStyle?.(layer));
        layer.on('click', (e) => {
          if (!a?.id) return;
          openAreaPopup(e.latlng, a);
        });
      }
    }).addTo(map);

    try {
      const b = areasGeoLayer.getBounds();
      if (b.isValid()) {
        map.fitBounds(b, { padding: [12, 12] });
        lockMinZoomToBounds(b);
      }
    } catch (_) {}
  }

  async function loadAreaDetail(areaId) {
    const res = await fetch(`/api/areas/${areaId}?year=${encodeURIComponent(DEFAULT_YEAR)}`);
    const json = await res.json();

    try {
      if (json.area?.name && typeof window.setVillageRegencyFilter === 'function') {
        window.setVillageRegencyFilter(json.area.name);
      }
    } catch (_) {}

    if (elAreaName) elAreaName.textContent = json.area?.name ?? '—';
    if (elAreaType) elAreaType.textContent = json.area?.type ?? '—';

    const registered = Number(json.summary?.registered_voters ?? 0);
    const votesCast = Number(json.summary?.votes_cast ?? 0);
    if (elRegistered) elRegistered.textContent = fmt.format(registered);
    if (elVotesCast) elVotesCast.textContent = fmt.format(votesCast);

    if (elPartyScope) elPartyScope.textContent = `Wilayah: ${json.area?.name ?? '—'}`;
    if (elCandidateScope) elCandidateScope.textContent = `wilayah: ${json.area?.name ?? '—'}`;

    // Update chart
    const pr = json.party_results || [];
    if (partyChart) {
      partyChart.data.labels = pr.map(x => x.party_name);
      partyChart.data.datasets[0].data = pr.map(x => x.votes);
      partyChart.update();
    }

    // Update candidates
    renderCandidateChart(json.candidate_results || []);
  }

  async function loadAreas() {
    clearAreaLayers();

    const res = await fetch(`/api/areas?year=${encodeURIComponent(DEFAULT_YEAR)}`);
    const json = await res.json();
    const areas = (json.data || []).filter(a => TARGET_REGENCIES.some(t => normalizeAreaName(a.name) === normalizeAreaName(t)));

    try {
      const geoRes = await fetch('/geo/jateng_kabkota.geojson', { cache: 'no-store' });
      if (!geoRes.ok) throw new Error(`GeoJSON HTTP ${geoRes.status}`);
      const geo = await geoRes.json();
      if (!geo?.features?.length) throw new Error('GeoJSON kosong');
      renderChoropleth(geo, areas);
    } catch (_) {
      // Fallback markers
      areas.forEach(a => {
        const lat = a.lat ?? a.latitude ?? -7.1;
        const lng = a.lng ?? a.longitude ?? 110.2;
        const dpt = a.summary?.registered_voters ?? 0;
        const color = getDptColor(dpt);
        const size = getMarkerSize(dpt);

        const icon = L.divIcon({
          className: '',
          html: `<span style="width:${size}px;height:${size}px;display:block;background:${color};border:2px solid #fff;border-radius:999px;box-shadow:0 6px 16px rgba(15,27,51,.18);"></span>`,
          iconSize: [size, size],
          iconAnchor: [size / 2, size / 2],
          popupAnchor: [0, -size / 2]
        });

        const m = L.marker([lat, lng], { icon }).addTo(markersLayer);
        m.on('click', (e) => openAreaPopup(e.latlng, a));
      });

      try {
        const b = markersLayer.getBounds();
        if (b.isValid()) {
          map.fitBounds(b, { padding: [12, 12] });
          lockMinZoomToBounds(b);
        }
      } catch (_) {}
    }

    if (areas?.[0]?.id) {
      await loadAreaDetail(areas[0].id);
    }
  }

  loadAreas();

  // village chart filter
  initVillageVoteChartFilters();

  // initial render for candidate chart (aggregated)
  renderCandidateChart(initialCandidates);
</script>
@endpush

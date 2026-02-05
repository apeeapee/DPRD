@extends('layouts.app')

@section('title', 'Dashboard DPRD Jateng')
@section('page_title', 'Dashboard DPRD Jateng')
@section('page_subtitle', 'Peta wilayah + perolehan suara partai & calon')

@section('content')
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

@push('scripts')
<script>
  const fmt = new Intl.NumberFormat('id-ID');

  const yearSelect = document.getElementById('yearSelect');
  const areaName = document.getElementById('areaName');
  const areaType = document.getElementById('areaType');
  const kpiRegistered = document.getElementById('kpiRegistered');
  const kpiVotesCast = document.getElementById('kpiVotesCast');
  const candidateTable = document.getElementById('candidateTable');

  // Map init (center Jateng)
  const map = L.map('map').setView([-7.15, 110.2], 8);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18
  }).addTo(map);

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

  let markersLayer = L.layerGroup().addTo(map);

  async function loadAreas() {
    const year = yearSelect.value;
    markersLayer.clearLayers();

    const res = await fetch(`/api/areas?year=${encodeURIComponent(year)}`);
    const json = await res.json();

    (json.data || []).forEach(a => {
      const lat = a.lat ?? -7.1;
      const lng = a.lng ?? 110.2;

      const m = L.marker([lat, lng]).addTo(markersLayer);
      m.bindPopup(`
        <b>${a.name}</b><br/>
        DPT: ${fmt.format(a.summary.registered_voters)}<br/>
        Suara Masuk: ${fmt.format(a.summary.votes_cast)}<br/>
        <small>Klik marker untuk detail</small>
      `);

      m.on('click', () => loadAreaDetail(a.id));
    });
  }

  async function loadAreaDetail(areaId) {
    const year = yearSelect.value;

    const res = await fetch(`/api/areas/${areaId}?year=${encodeURIComponent(year)}`);
    const json = await res.json();

    areaName.textContent = json.area?.name ?? '—';
    areaType.textContent = json.area?.type ?? '—';
    kpiRegistered.textContent = fmt.format(json.summary?.registered_voters ?? 0);
    kpiVotesCast.textContent = fmt.format(json.summary?.votes_cast ?? 0);

    // Party chart
    const pr = json.party_results || [];
    partyChart.data.labels = pr.map(x => x.party_name);
    partyChart.data.datasets[0].data = pr.map(x => x.votes);
    partyChart.update();

    // Candidate table (Top 10)
    const cr = (json.candidate_results || []).slice(0, 10);
    if (cr.length === 0) {
      candidateTable.innerHTML = `<tr><td colspan="3" class="muted">Tidak ada data calon.</td></tr>`;
      return;
    }
    candidateTable.innerHTML = cr.map(r => `
      <tr>
        <td>${r.candidate_name}</td>
        <td>${r.party_name}</td>
        <td>${fmt.format(r.votes)}</td>
      </tr>
    `).join('');
  }

  yearSelect.addEventListener('change', () => {
    loadAreas();
    // reset kanan
    areaName.textContent = 'Pilih wilayah';
    areaType.textContent = '—';
    kpiRegistered.textContent = '0';
    kpiVotesCast.textContent = '0';
    partyChart.data.labels = [];
    partyChart.data.datasets[0].data = [];
    partyChart.update();
    candidateTable.innerHTML = `<tr><td colspan="3" class="muted">Klik wilayah dulu…</td></tr>`;
  });

  // start
  loadAreas();
</script>
@endpush

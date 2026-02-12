@extends('layouts.app')

@section('title', 'Suara Masuk Desa')
@section('page_title', 'Suara Masuk Desa')
@section('page_subtitle', 'Karanganyar, Sragen, Wonogiri')

@section('content')
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
      <div>
        <div class="muted" style="margin-bottom:6px;">Kecamatan</div>
        <select id="subdistrictSelect" style="min-width:320px;">
          <option value="">-- Pilih Kecamatan --</option>
        </select>
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Desa</div>
        <select id="villageSelect" style="min-width:320px;" disabled>
          <option value="">-- Pilih Desa --</option>
        </select>
      </div>

      <div style="flex:1;"></div>
      <div class="muted" id="statusText">Pilih kecamatan untuk melihat daftar desa.</div>
    </div>
  </div>

  <div class="card">
    <div style="overflow:auto;" id="tableWrap"></div>
  </div>
@endsection

@push('scripts')
<script>
  const fmt = new Intl.NumberFormat('id-ID');

  const TARGET_REGENCIES = [
    'Kabupaten Karanganyar',
    'Kabupaten Sragen',
    'Kabupaten Wonogiri',
  ];

  const subdistrictSelect = document.getElementById('subdistrictSelect');
  const villageSelect = document.getElementById('villageSelect');
  const tableWrap = document.getElementById('tableWrap');
  const statusText = document.getElementById('statusText');

  function renderPlaceholder(text) {
    tableWrap.innerHTML = `
      <div class="muted" style="padding: 6px 2px;">${text}</div>
    `;
  }

  function renderTpsGrid(data) {
    const tps = data?.tps || [];
    const parties = data?.parties || [];

    if (tps.length === 0) {
      tableWrap.innerHTML = `
        <table>
          <thead>
            <tr>
              <th>Partai</th>
              <th>Caleg</th>
              <th class="muted">(Belum ada TPS)</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="muted">Belum ada TPS untuk desa ini. Tambahkan TPS & input suara terlebih dulu (dummy seeder juga bisa).</td></tr>
          </tbody>
        </table>
      `;
      return;
    }

    const tpsCols = tps.map(x => ({ id: String(x.id), code: x.code }));
    const header = `
      <tr>
        <th>Partai</th>
        <th>Caleg</th>
        ${tpsCols.map(c => `<th>${c.code}</th>`).join('')}
        <th>Akumulasi</th>
      </tr>
    `;

    const bodyParts = [];

    parties.forEach(p => {
      const candidates = p.candidates || [];

      candidates.forEach((c, idx) => {
        const votesByTps = c.votes_by_tps || {};
        const partyCell = idx === 0 ? `<td style="font-weight:800;">${p.party_name}</td>` : `<td class="muted"></td>`;
        bodyParts.push(`
          <tr>
            ${partyCell}
            <td>${c.candidate_name}</td>
            ${tpsCols.map(col => `<td>${fmt.format(Number(votesByTps[col.id] || 0))}</td>`).join('')}
            <td style="font-weight:800;">${fmt.format(Number(c.total || 0))}</td>
          </tr>
        `);
      });

      const totalsByTps = p.totals_by_tps || {};
      bodyParts.push(`
        <tr>
          <td style="font-weight:900;">${p.party_name}</td>
          <td style="font-weight:900;">TOTAL PARTAI</td>
          ${tpsCols.map(col => `<td style="font-weight:900;">${fmt.format(Number(totalsByTps[col.id] || 0))}</td>`).join('')}
          <td style="font-weight:900;">${fmt.format(Number(p.total || 0))}</td>
        </tr>
      `);
    });

    const grandByTps = data?.grand_totals_by_tps || {};
    bodyParts.push(`
      <tr>
        <td style="font-weight:900;">TOTAL</td>
        <td style="font-weight:900;">SEMUA PARTAI</td>
        ${tpsCols.map(col => `<td style="font-weight:900;">${fmt.format(Number(grandByTps[col.id] || 0))}</td>`).join('')}
        <td style="font-weight:900;">${fmt.format(Number(data?.grand_total || 0))}</td>
      </tr>
    `);

    tableWrap.innerHTML = `
      <table>
        <thead>${header}</thead>
        <tbody>${bodyParts.join('')}</tbody>
      </table>
    `;
  }

  async function loadSubdistricts() {
    const url = `/api/subdistricts?regencies=${encodeURIComponent(TARGET_REGENCIES.join(','))}`;
    const res = await fetch(url);
    const json = await res.json();

    const items = json.data || [];
    subdistrictSelect.innerHTML = `<option value="">-- Pilih Kecamatan --</option>` + items.map(s => {
      const label = `${s.name} — ${s.regency_name}`;
      return `<option value="${s.id}">${label}</option>`;
    }).join('');
  }

  async function loadVillages(subdistrictId) {
    villageSelect.disabled = true;
    villageSelect.innerHTML = `<option value="">-- Pilih Desa --</option>`;

    if (!subdistrictId) {
      return;
    }

    const res = await fetch(`/api/villages?subdistrict_id=${encodeURIComponent(subdistrictId)}`);
    const json = await res.json();
    const items = json.data || [];

    villageSelect.innerHTML = `<option value="">-- Semua Desa (dalam kecamatan) --</option>` + items.map(v => {
      return `<option value="${v.id}">${v.name}</option>`;
    }).join('');

    villageSelect.disabled = false;
  }

  async function loadData() {
    const year = 2024;
    const subdistrictId = subdistrictSelect.value;
    const villageId = villageSelect.value;

    if (!subdistrictId) {
      statusText.textContent = 'Pilih kecamatan untuk melihat daftar desa.';
      renderPlaceholder('Belum ada data ditampilkan.');
      return;
    }

    if (!villageId) {
      statusText.textContent = 'Pilih desa untuk melihat detail TPS.';
      renderPlaceholder('Silakan pilih desa.');
      return;
    }

    statusText.textContent = 'Memuat data…';

    // Detail TPS (spreadsheet)
    const params = new URLSearchParams();
    params.set('year', year);
    params.set('village_id', villageId);

    const res = await fetch(`/api/village-tps-results?${params.toString()}`);
    const json = await res.json();
    statusText.textContent = 'Menampilkan detail TPS';
    renderTpsGrid(json.data);
  }

  subdistrictSelect.addEventListener('change', async () => {
    await loadVillages(subdistrictSelect.value);
    await loadData();
  });

  villageSelect.addEventListener('change', async () => {
    await loadData();
  });

  // init
  loadSubdistricts();
  renderPlaceholder('Belum ada data ditampilkan.');
</script>
@endpush

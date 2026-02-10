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
    <div style="overflow:auto;">
      <table>
        <thead>
          <tr>
            <th>Kabupaten</th>
            <th>Kecamatan</th>
            <th>Desa</th>
            <th>Suara Masuk</th>
          </tr>
        </thead>
        <tbody id="rows">
          <tr><td colspan="4" class="muted">Belum ada data.</td></tr>
        </tbody>
      </table>
    </div>
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

  const yearSelect = document.getElementById('yearSelect');
  const subdistrictSelect = document.getElementById('subdistrictSelect');
  const villageSelect = document.getElementById('villageSelect');
  const rows = document.getElementById('rows');
  const statusText = document.getElementById('statusText');

  function setRows(items) {
    if (!items || items.length === 0) {
      rows.innerHTML = `<tr><td colspan="4" class="muted">Belum ada data.</td></tr>`;
      return;
    }

    rows.innerHTML = items.map(r => {
      return `
        <tr>
          <td>${r.regency_name}</td>
          <td>${r.subdistrict_name}</td>
          <td>${r.village_name}</td>
          <td style="font-weight:800;">${fmt.format(Number(r.votes_cast || 0))}</td>
        </tr>
      `;
    }).join('');
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

  async function loadVotes() {
    const year = yearSelect?.value || 2024;
    const subdistrictId = subdistrictSelect.value;
    const villageId = villageSelect.value;

    if (!subdistrictId) {
      statusText.textContent = 'Pilih kecamatan untuk melihat daftar desa.';
      setRows([]);
      return;
    }

    statusText.textContent = 'Memuat data…';

    const params = new URLSearchParams();
    params.set('year', year);
    params.set('subdistrict_id', subdistrictId);
    if (villageId) params.set('village_id', villageId);

    const res = await fetch(`/api/village-votes?${params.toString()}`);
    const json = await res.json();

    const items = json.data || [];
    statusText.textContent = `Menampilkan ${items.length} desa`;
    setRows(items);
  }

  subdistrictSelect.addEventListener('change', async () => {
    await loadVillages(subdistrictSelect.value);
    await loadVotes();
  });

  villageSelect.addEventListener('change', async () => {
    await loadVotes();
  });

  yearSelect?.addEventListener('change', async () => {
    await loadVotes();
  });

  // init
  loadSubdistricts();
</script>
@endpush

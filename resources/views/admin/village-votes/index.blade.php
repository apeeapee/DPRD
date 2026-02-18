@extends('layouts.app')

@section('title', 'Admin - Suara Masuk Desa')
@section('page_title', 'Admin: Suara Masuk Desa')
@section('page_subtitle', 'Detail suara per TPS')

@section('content')
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-end; flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.village-votes.index') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <div>
          <div class="muted" style="margin-bottom:6px;">Tahun</div>
          <select name="year" style="width:auto;">
            @foreach($years as $y)
              <option value="{{ $y->year }}" @selected($y->year == $year)>{{ $y->year }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <div class="muted" style="margin-bottom:6px;">Kecamatan</div>
          <select name="subdistrict_id" onchange="this.form.submit()" style="min-width:280px;">
            <option value="">-- Semua Kecamatan --</option>
            @foreach($subdistricts as $s)
              <option value="{{ $s->id }}" @selected((string)$s->id === (string)$subdistrictId)>
                {{ $s->name }} — {{ $s->regency_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <div class="muted" style="margin-bottom:6px;">Desa</div>
          <select name="village_id" style="min-width:240px;" @disabled(!$subdistrictId)>
            <option value="">-- Semua Desa --</option>
            @foreach($villages as $v)
              <option value="{{ $v->id }}" @selected((string)$v->id === (string)$villageId)>{{ $v->name }}</option>
            @endforeach
          </select>
        </div>

        <button type="submit" class="area-popup__btn" style="width:auto; padding: 8px 12px;">Terapkan</button>
      </form>
    </div>

    @if (session('status'))
      <div class="card" style="margin-top:12px; background: rgba(34,197,94,.06); border-color: rgba(34,197,94,.18);">
        <div class="muted" style="color:#166534; font-weight:700;">{{ session('status') }}</div>
      </div>
    @endif
  </div>

  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
      <div>
        <div style="font-weight:800;">Detail Suara per TPS</div>
        <div class="muted">Pilih desa untuk menampilkan grid TPS</div>
      </div>
      <div class="muted" id="tpsStatus">@if($villageId) Memuat… @else — @endif</div>
    </div>

    @if(!$villageId)
      <div class="muted">Silakan pilih <b>Kecamatan</b> lalu <b>Desa</b>.</div>
    @endif

    @if($villageId)
      <div style="display:flex; gap:10px; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; margin: 10px 0 12px;">
        <form method="POST" action="{{ route('admin.village-tps.store') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
          @csrf
          <input type="hidden" name="election_year_id" value="{{ $yearRow?->id }}" />
          <input type="hidden" name="village_id" value="{{ $villageId }}" />

          <div>
            <div class="muted" style="font-size:12px; margin-bottom:6px;">Tambah TPS</div>
            <input class="input" type="text" name="tps_code" placeholder="TPS 01" style="width:140px;" @disabled(!$yearRow) />
          </div>
          <div>
            <div class="muted" style="font-size:12px; margin-bottom:6px;">Urutan</div>
            <input class="input" type="number" min="0" name="sort_order" placeholder="0" style="width:100px; text-align:right;" @disabled(!$yearRow) />
          </div>
          <button type="submit" class="btn btn--primary" @disabled(!$yearRow)>Tambah</button>
        </form>
        <div class="muted" style="font-size:12px;">
          Isi angka per TPS lalu klik <b>Simpan Suara TPS</b>.
        </div>
      </div>
    @endif

    <div style="overflow:auto;" id="tpsGridWrap"></div>
  </div>
@endsection

@push('scripts')
<script>
  const fmt = new Intl.NumberFormat('id-ID');

  const adminVillageId = @json($villageId);
  const adminYear = @json($year);
  const adminElectionYearId = @json($yearRow?->id);
  const adminSubdistrictId = @json($subdistrictId);
  const csrf = @json(csrf_token());
  const bulkSaveAction = @json(route('admin.village-tps-votes.bulk'));
  const deleteTpsBase = @json(url('/admin/village-tps'));
  const tpsGridWrap = document.getElementById('tpsGridWrap');
  const tpsStatus = document.getElementById('tpsStatus');

  function renderTpsGrid(data) {
    const tps = data?.tps || [];
    const parties = data?.parties || [];

    if (!tpsGridWrap) return;

    if (tps.length === 0) {
      tpsGridWrap.innerHTML = `
        <table>
          <thead>
            <tr>
              <th>Partai</th>
              <th>Caleg</th>
              <th class="muted">(Belum ada TPS)</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="muted">Belum ada TPS untuk desa ini.</td></tr>
          </tbody>
        </table>
      `;
      return;
    }

    const tpsCols = tps.map(x => ({ id: String(x.id), code: x.code }));

    const actionQs = new URLSearchParams();
    if (adminYear) actionQs.set('year', adminYear);
    if (adminSubdistrictId) actionQs.set('subdistrict_id', adminSubdistrictId);
    if (adminVillageId) actionQs.set('village_id', adminVillageId);
    const actionSuffix = actionQs.toString() ? `?${actionQs.toString()}` : '';

    const header = `
      <tr>
        <th>Partai</th>
        <th>Caleg</th>
        ${tpsCols.map(c => `
          <th style="min-width:120px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
              <div style="font-weight:900;">${c.code}</div>
              <form method="POST" action="${deleteTpsBase}/${c.id}${actionSuffix}" onsubmit="return confirm('Hapus ${c.code}?')">
                <input type="hidden" name="_token" value="${csrf}" />
                <input type="hidden" name="_method" value="DELETE" />
                <input type="hidden" name="election_year_id" value="${adminElectionYearId || ''}" />
                <button type="submit" class="btn btn--danger" style="padding:6px 8px;" ${!adminElectionYearId ? 'disabled' : ''}>Hapus</button>
              </form>
            </div>
          </th>
        `).join('')}
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
            ${tpsCols.map(col => `
              <td style="text-align:right;">
                <input
                  class="input"
                  type="number"
                  min="0"
                  name="votes[${col.id}][${c.candidate_id}]"
                  value="${Number(votesByTps[col.id] || 0)}"
                  style="width:92px; text-align:right;"
                  ${!adminElectionYearId ? 'disabled' : ''}
                />
              </td>
            `).join('')}
            <td style="font-weight:800; text-align:right;">${fmt.format(Number(c.total || 0))}</td>
          </tr>
        `);
      });

      const totalsByTps = p.totals_by_tps || {};
      bodyParts.push(`
        <tr>
          <td style="font-weight:900;">${p.party_name}</td>
          <td style="font-weight:900;">TOTAL PARTAI</td>
          ${tpsCols.map(col => `<td style="font-weight:900; text-align:right;">${fmt.format(Number(totalsByTps[col.id] || 0))}</td>`).join('')}
          <td style="font-weight:900; text-align:right;">${fmt.format(Number(p.total || 0))}</td>
        </tr>
      `);
    });

    const grandByTps = data?.grand_totals_by_tps || {};
    bodyParts.push(`
      <tr>
        <td style="font-weight:900;">TOTAL</td>
        <td style="font-weight:900;">SEMUA PARTAI</td>
        ${tpsCols.map(col => `<td style="font-weight:900; text-align:right;">${fmt.format(Number(grandByTps[col.id] || 0))}</td>`).join('')}
        <td style="font-weight:900; text-align:right;">${fmt.format(Number(data?.grand_total || 0))}</td>
      </tr>
    `);

    tpsGridWrap.innerHTML = `
      <form method="POST" action="${bulkSaveAction}${actionSuffix}">
        <input type="hidden" name="_token" value="${csrf}" />
        <input type="hidden" name="election_year_id" value="${adminElectionYearId || ''}" />
        <input type="hidden" name="village_id" value="${adminVillageId || ''}" />

        <div style="display:flex; gap:10px; align-items:center; justify-content:flex-end; margin: 0 0 10px;">
          <button type="submit" class="btn btn--primary" ${!adminElectionYearId ? 'disabled' : ''}>Simpan Suara TPS</button>
        </div>

        <table>
          <thead>${header}</thead>
          <tbody>${bodyParts.join('')}</tbody>
        </table>

        <div style="display:flex; gap:10px; align-items:center; justify-content:flex-end; margin: 10px 0 0;">
          <button type="submit" class="btn btn--primary" ${!adminElectionYearId ? 'disabled' : ''}>Simpan Suara TPS</button>
        </div>
      </form>
    `;
  }

  async function loadAdminTpsGrid() {
    if (!adminVillageId || !tpsGridWrap) return;
    try {
      if (tpsStatus) {
        tpsStatus.style.visibility = 'visible';
        tpsStatus.textContent = 'Memuat…';
      }
      const params = new URLSearchParams();
      params.set('year', adminYear || 2024);
      params.set('village_id', adminVillageId);
      const res = await fetch(`/api/village-tps-results?${params.toString()}`);
      const json = await res.json();
      renderTpsGrid(json.data);
      if (tpsStatus) {
        tpsStatus.textContent = '';
        tpsStatus.style.visibility = 'hidden';
      }
    } catch (e) {
      if (tpsStatus) {
        tpsStatus.style.visibility = 'visible';
        tpsStatus.textContent = 'Gagal memuat';
      }
      tpsGridWrap.innerHTML = `<div class="muted">Gagal memuat detail TPS.</div>`;
    }
  }

  loadAdminTpsGrid();
</script>
@endpush

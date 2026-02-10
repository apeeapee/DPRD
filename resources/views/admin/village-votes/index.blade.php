@extends('layouts.app')

@section('title', 'Admin - Suara Masuk Desa')
@section('page_title', 'Admin: Suara Masuk Desa')
@section('page_subtitle', 'CRUD suara masuk per desa')

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

      <a href="{{ route('admin.village-votes.create', ['year' => $year]) }}" class="area-popup__btn" style="width:auto; display:inline-block; text-decoration:none; padding: 8px 12px;">
        + Input Suara Desa
      </a>
    </div>

    @if (session('status'))
      <div class="card" style="margin-top:12px; background: rgba(34,197,94,.06); border-color: rgba(34,197,94,.18);">
        <div class="muted" style="color:#166534; font-weight:700;">{{ session('status') }}</div>
      </div>
    @endif
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
            <th style="width:160px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td>{{ $r->regency_name }}</td>
              <td>{{ $r->subdistrict_name }}</td>
              <td>{{ $r->village_name }}</td>
              <td style="font-weight:800;">{{ number_format((int)$r->votes_cast, 0, ',', '.') }}</td>
              <td>
                <div style="display:flex; gap:8px;">
                  @if((int)$r->vote_id > 0)
                    <a href="{{ route('admin.village-votes.edit', (int)$r->vote_id) }}" class="btn btn--primary" style="padding:6px 10px;">Edit</a>
                    <form method="POST" action="{{ route('admin.village-votes.destroy', (int)$r->vote_id) }}" onsubmit="return confirm('Hapus data suara desa ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn--danger" style="padding:6px 10px;">Hapus</button>
                    </form>
                  @else
                    <a href="{{ route('admin.village-votes.create', ['year' => $year]) }}" class="btn btn--primary" style="padding:6px 10px;">Input</a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="muted">Belum ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:12px;">
      {{ $rows->links() }}
    </div>
  </div>

  @if($villageId)
    <div class="card" style="margin-top:14px;">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
        <div>
          <div style="font-weight:800;">Detail Suara per TPS</div>
          <div class="muted">Partai → Caleg → TPS (dinamis) + Akumulasi</div>
        </div>
        <div class="muted" id="tpsStatus">Memuat…</div>
      </div>

      <div style="overflow:auto;" id="tpsGridWrap"></div>
    </div>
  @endif
@endsection

@push('scripts')
<script>
  const fmt = new Intl.NumberFormat('id-ID');

  const adminVillageId = @json($villageId);
  const adminYear = @json($year);
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

    tpsGridWrap.innerHTML = `
      <table>
        <thead>${header}</thead>
        <tbody>${bodyParts.join('')}</tbody>
      </table>
    `;
  }

  async function loadAdminTpsGrid() {
    if (!adminVillageId || !tpsGridWrap) return;
    try {
      if (tpsStatus) tpsStatus.textContent = 'Memuat…';
      const params = new URLSearchParams();
      params.set('year', adminYear || 2024);
      params.set('village_id', adminVillageId);
      const res = await fetch(`/api/village-tps-results?${params.toString()}`);
      const json = await res.json();
      renderTpsGrid(json.data);
      if (tpsStatus) tpsStatus.textContent = 'OK';
    } catch (e) {
      if (tpsStatus) tpsStatus.textContent = 'Gagal memuat';
      tpsGridWrap.innerHTML = `<div class="muted">Gagal memuat detail TPS.</div>`;
    }
  }

  loadAdminTpsGrid();
</script>
@endpush

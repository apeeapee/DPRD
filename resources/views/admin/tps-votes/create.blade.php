@extends('layouts.app')

@section('title', 'Admin - Input Suara TPS')
@section('page_title', 'Input Suara TPS')
@section('page_subtitle', 'Buat TPS & input suara per caleg')

@section('content')
  <div class="card" style="max-width:980px; margin: 0 auto;">
    @if(isset($area) && $area)
      <div class="card" style="background: rgba(15,23,42,.03); border-color: rgba(15,23,42,.08); margin-bottom:12px;">
        <div class="muted" style="margin-bottom:6px;">Filter caleg</div>
        <div style="font-weight:900;">{{ $area->name }}</div>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.tps-votes.store') }}" style="display:grid; gap:12px;">
      @csrf

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:start;">
        <div>
          <div class="muted" style="margin-bottom:6px;">Tahun</div>
          <select name="election_year_id" required style="width:100%;">
            @foreach($years as $y)
              <option value="{{ $y->id }}" @selected($y->year == $year)>{{ $y->year }}</option>
            @endforeach
          </select>
          @error('election_year_id')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
        </div>

        <div>
          <div class="muted" style="margin-bottom:6px;">Kode TPS</div>
          <input name="tps_code" type="text" value="{{ old('tps_code', 'TPS 01') }}" required
            style="width:100%; border-radius:12px; border:1px solid rgba(15,23,42,.10); padding:10px 12px;" />
          @error('tps_code')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:start;">
        <div>
          <div class="muted" style="margin-bottom:6px;">Kecamatan</div>
          <select id="subdistrictSelect" style="width:100%;" required>
            <option value="">-- Pilih Kecamatan --</option>
            @foreach($subdistricts as $s)
              <option value="{{ $s->id }}" @selected((string)$s->id === (string)$subdistrictId)>
                {{ $s->name }} — {{ $s->regency_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <div class="muted" style="margin-bottom:6px;">Desa</div>
          <select id="villageSelect" name="village_id" style="width:100%;" required @disabled(!$subdistrictId)>
            <option value="">-- Pilih Desa --</option>
            @foreach($villages as $v)
              <option value="{{ $v->id }}" @selected((string)$v->id === (string)$villageId)>{{ $v->name }}</option>
            @endforeach
          </select>
          @error('village_id')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
        </div>
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Urutan TPS (opsional)</div>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}"
          style="width:100%; max-width:220px; border-radius:12px; border:1px solid rgba(15,23,42,.10); padding:10px 12px;" />
        @error('sort_order')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div class="card" style="background: rgba(15,23,42,.03); border-color: rgba(15,23,42,.08);">
        <div style="font-weight:900;">Input Suara per Caleg</div>
        <div class="muted" style="margin-top:6px;">Isi angka suara untuk caleg di TPS ini. Nilai 0 akan dianggap kosong.</div>
        <div style="height:10px"></div>

        <div style="overflow:auto;">
          <table>
            <thead>
              <tr>
                <th style="width:220px;">Partai</th>
                <th>Caleg</th>
                <th style="width:140px;">No</th>
                <th style="width:160px;">Suara</th>
              </tr>
            </thead>
            <tbody>
              @php
                $lastParty = null;
              @endphp
              @forelse($candidates as $c)
                @php
                  $partyName = $c->party?->name ?? '—';
                  $showParty = $lastParty !== $partyName;
                  if ($showParty) $lastParty = $partyName;
                @endphp
                <tr>
                  <td style="font-weight:{{ $showParty ? '900' : '600' }};" class="{{ $showParty ? '' : 'muted' }}">{{ $showParty ? $partyName : '—' }}</td>
                  <td>{{ $c->name }}</td>
                  <td class="muted">{{ $c->number ?? '—' }}</td>
                  <td>
                    <input name="votes[{{ $c->id }}]" type="number" min="0" value="{{ old('votes.'.$c->id, 0) }}"
                      style="width:140px; border-radius:12px; border:1px solid rgba(15,23,42,.10); padding:8px 10px;" />
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="muted">Belum ada data caleg untuk kabupaten ini. Silakan isi di menu <b>Data Calon</b> (pilih Kabupaten).</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="area-popup__btn" style="width:auto; padding: 8px 12px;">Simpan</button>
        <a href="{{ route('admin.tps-votes.index', ['year' => $year, 'subdistrict_id' => $subdistrictId, 'village_id' => $villageId]) }}" class="muted" style="text-decoration:none;">Batal</a>
      </div>
    </form>
  </div>
@endsection

@push('scripts')
<script>
  const subdistrictSelect = document.getElementById('subdistrictSelect');
  const villageSelect = document.getElementById('villageSelect');

  async function loadVillages(subdistrictId) {
    villageSelect.disabled = true;
    villageSelect.innerHTML = `<option value="">-- Pilih Desa --</option>`;
    if (!subdistrictId) return;

    const res = await fetch(`/api/villages?subdistrict_id=${encodeURIComponent(subdistrictId)}`);
    const json = await res.json();
    const items = json.data || [];

    villageSelect.innerHTML = `<option value="">-- Pilih Desa --</option>` + items.map(v => {
      return `<option value="${v.id}">${v.name}</option>`;
    }).join('');

    villageSelect.disabled = false;
  }

  subdistrictSelect.addEventListener('change', () => {
    loadVillages(subdistrictSelect.value);
  });

  villageSelect.addEventListener('change', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('subdistrict_id', subdistrictSelect.value || '');
    url.searchParams.set('village_id', villageSelect.value || '');
    window.location.href = url.toString();
  });
</script>
@endpush

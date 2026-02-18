@extends('layouts.app')

@section('title', 'Admin - Edit Suara TPS')
@section('page_title', 'Edit Suara TPS')
@section('page_subtitle', 'Perbarui suara per caleg di TPS')

@section('content')
  <div class="card" style="max-width:980px; margin: 0 auto;">
    @if(isset($area) && $area)
      <div class="card" style="background: rgba(15,23,42,.03); border-color: rgba(15,23,42,.08); margin-bottom:12px;">
        <div class="muted" style="margin-bottom:6px;">Filter caleg</div>
        <div style="font-weight:900;">{{ $area->name }}</div>
      </div>
    @endif

    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
      <div>
        <div style="font-weight:950; font-size:18px;">{{ $pollingStation->code }}</div>
        <div class="muted" style="margin-top:6px;">
          {{ $village->regency_name ?? '—' }} / {{ $village->subdistrict_name ?? '—' }} / {{ $village->village_name ?? '—' }}
        </div>
      </div>

      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <form method="POST" action="{{ route('admin.tps-votes.destroy', $pollingStation) }}" onsubmit="return confirm('Hapus TPS ini? Semua suara (semua tahun) ikut terhapus.')" style="margin:0;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn--danger" style="padding:6px 10px;">Hapus TPS</button>
        </form>
        <a class="btn btn--ghost" style="text-decoration:none;" href="{{ route('admin.tps-votes.index', ['year' => $year, 'subdistrict_id' => $village->subdistrict_id ?? null, 'village_id' => $pollingStation->village_id]) }}">Kembali</a>
      </div>
    </div>

    @if (session('status'))
      <div class="card" style="margin-bottom:12px; background: rgba(34,197,94,.06); border-color: rgba(34,197,94,.18);">
        <div class="muted" style="color:#166534; font-weight:700;">{{ session('status') }}</div>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.tps-votes.update', $pollingStation) }}" style="display:grid; gap:12px;">
      @csrf
      @method('PUT')

      <div>
        <div class="muted" style="margin-bottom:6px;">Tahun</div>
        <select name="election_year_id" required style="width:100%; max-width:240px;">
          @foreach($years as $y)
            <option value="{{ $y->id }}" @selected($y->year == $year)>{{ $y->year }}</option>
          @endforeach
        </select>
        @error('election_year_id')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div class="card" style="background: rgba(15,23,42,.03); border-color: rgba(15,23,42,.08);">
        <div style="font-weight:900;">Input Suara per Caleg</div>
        <div class="muted" style="margin-top:6px;">Nilai 0 akan dianggap kosong.</div>
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
                  $val = (int) ($votes[$c->id] ?? 0);
                @endphp
                <tr>
                  <td style="font-weight:{{ $showParty ? '900' : '600' }};" class="{{ $showParty ? '' : 'muted' }}">{{ $showParty ? $partyName : '—' }}</td>
                  <td>{{ $c->name }}</td>
                  <td class="muted">{{ $c->number ?? '—' }}</td>
                  <td>
                    <input name="votes[{{ $c->id }}]" type="number" min="0" value="{{ old('votes.'.$c->id, $val) }}"
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
      </div>
    </form>
  </div>
@endsection

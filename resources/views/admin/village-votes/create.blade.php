@extends('layouts.app')

@section('title', 'Admin - Input Suara Desa')
@section('page_title', 'Input Suara Desa')
@section('page_subtitle', 'Tambah / update suara masuk per desa')

@section('content')
  <div class="card" style="max-width:760px; margin: 0 auto;">
    <form method="POST" action="{{ route('admin.village-votes.store') }}" style="display:grid; gap:12px;">
      @csrf

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
        <div class="muted" style="margin-bottom:6px;">Kecamatan</div>
        <select id="subdistrictSelect" style="width:100%;" required>
          <option value="">-- Pilih Kecamatan --</option>
          @foreach($subdistricts as $s)
            <option value="{{ $s->id }}">{{ $s->name }} — {{ $s->regency_name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Desa</div>
        <select id="villageSelect" name="village_id" style="width:100%;" required disabled>
          <option value="">-- Pilih Desa --</option>
        </select>
        @error('village_id')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Suara Masuk</div>
        <input name="votes_cast" type="number" min="0" value="{{ old('votes_cast', 0) }}" required
          style="width:100%; border-radius:12px; border:1px solid rgba(15,23,42,.10); padding:10px 12px;" />
        @error('votes_cast')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="area-popup__btn" style="width:auto; padding: 8px 12px;">Simpan</button>
        <a href="{{ route('admin.village-votes.index', ['year' => $year]) }}" class="muted" style="text-decoration:none;">Batal</a>
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
</script>
@endpush

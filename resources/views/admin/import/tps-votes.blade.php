@extends('layouts.app')

@section('title', 'Admin - Import Suara TPS')
@section('page_title', 'Import Suara TPS (Excel)')
@section('page_subtitle', 'Upload 1 file .xlsx (banyak sheet = banyak desa) untuk input cepat suara per TPS')

@section('content')
  <div class="card" style="display:grid; gap:14px;">
    <div class="muted" style="line-height:1.5;">
      Format yang didukung:
      <ul style="margin:8px 0 0 18px;">
        <li>1 file Excel (.xlsx) berisi banyak sheet/tab.</li>
        <li>Nama sheet = nama Desa (harus sama dengan data Desa di sistem).</li>
        <li>Di dalam sheet ada kolom <b>Caleg</b> dan kolom TPS: <b>TPS 01</b>, <b>TPS 02</b>, dst (boleh juga TPS01).</li>
        <li>Baris <b>Suara Partai</b>/TOTAL akan di-skip.</li>
      </ul>
    </div>

    @if(session('import_result'))
      @php($r = session('import_result'))
      <div class="card" style="background: rgba(34,197,94,.06); border-color: rgba(34,197,94,.18);">
        <div style="font-weight:900;">Hasil Import</div>
        <div class="muted" style="margin-top:6px; display:grid; gap:4px;">
          <div>Tahun: <b>{{ $r['year'] }}</b></div>
          <div>Sheet diproses: <b>{{ $r['sheets_total'] }}</b></div>
          <div>Desa sukses: <b>{{ $r['villages_ok'] }}</b> | gagal: <b>{{ $r['villages_failed'] }}</b></div>
          <div>Baris terbaca: <b>{{ $r['rows_imported'] }}</b></div>
          <div>Sel suara tersimpan: <b>{{ $r['cells_imported'] }}</b></div>
          <div>TPS dibuat otomatis: <b>{{ $r['tps_created'] }}</b></div>
          <div>Partai dibuat otomatis: <b>{{ $r['parties_created'] ?? 0 }}</b></div>
          <div>Calon dibuat otomatis: <b>{{ $r['candidates_created'] ?? 0 }}</b></div>
        </div>

        @if(!empty($r['errors']))
          <div style="height:10px"></div>
          <div style="font-weight:800;">Catatan</div>
          <ul class="muted" style="margin:8px 0 0 18px; line-height:1.5;">
            @foreach($r['errors'] as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        @endif
      </div>
    @endif

    <form method="POST" action="{{ route('admin.import.tps-votes.store') }}" enctype="multipart/form-data" style="display:grid; gap:12px;">
      @csrf

      <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div>
          <div class="muted" style="font-size:12px; margin-bottom:6px;">Kabupaten/Kota</div>
          <select name="area_id" class="input" style="min-width:260px;">
            @foreach($areas as $a)
              <option value="{{ $a->id }}" @selected((string)old('area_id', $areaId) === (string)$a->id)>
                {{ $a->name }} ({{ $a->type }})
              </option>
            @endforeach
          </select>
          @error('area_id')
            <div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <div class="muted" style="font-size:12px; margin-bottom:6px;">Tahun</div>
          <select name="year" class="input" style="min-width:140px;">
            @foreach($years as $y)
              <option value="{{ $y->year }}" @selected((int)$year === (int)$y->year)>{{ $y->year }}</option>
            @endforeach
          </select>
          @error('year')
            <div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <div class="muted" style="font-size:12px; margin-bottom:6px;">Kecamatan (pilih jika sudah ada)</div>
          <select name="subdistrict_id" class="input" style="min-width:280px;">
            <option value="">-- Semua Kecamatan --</option>
            @foreach($subdistricts as $s)
              <option value="{{ $s->id }}" @selected((string)$subdistrictId === (string)$s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
          @error('subdistrict_id')
            <div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <div class="muted" style="font-size:12px; margin-bottom:6px;">Atau isi nama Kecamatan (kalau belum ada di sistem)</div>
          <input
            class="input"
            type="text"
            name="subdistrict_name"
            value="{{ old('subdistrict_name') }}"
            placeholder="contoh: JENAWI"
            style="min-width:220px;"
          />
          @error('subdistrict_name')
            <div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div>
        <div class="muted" style="font-size:12px; margin-bottom:6px;">File Excel (.xlsx)</div>
        <input class="input" type="file" name="file" accept=".xlsx,.xls,.csv" />
        @error('file')
          <div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>
        @enderror
      </div>

      <label style="display:flex; gap:10px; align-items:center;">
        <input type="checkbox" name="create_missing_tps" value="1" />
        <span class="muted">Buat TPS otomatis jika kolom TPS belum ada di sistem</span>
      </label>

      <label style="display:flex; gap:10px; align-items:center;">
        <input type="checkbox" name="create_missing_subdistrict_villages" value="1" />
        <span class="muted">Buat Kecamatan & Desa otomatis jika belum ada (Desa diambil dari nama sheet)</span>
      </label>

      <label style="display:flex; gap:10px; align-items:center;">
        <input type="checkbox" name="create_missing_parties_candidates" value="1" />
        <span class="muted">Buat Partai & Calon otomatis jika nama di Excel belum ada di sistem</span>
      </label>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="btn btn--primary">Import</button>
        <a href="{{ route('admin.village-votes.index', ['year' => $year]) }}" class="btn" style="text-decoration:none;">Kembali</a>
      </div>
    </form>
  </div>
@endsection

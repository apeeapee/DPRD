@extends('layouts.app')

@section('title', 'Admin - Pengaturan DPT')
@section('page_title', 'Pengaturan DPT')
@section('page_subtitle', 'Kelola DPT (DPT) per kabupaten dan sinkronkan suara masuk dari TPS')

@section('content')
  <div class="card">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.dpt.index') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <div>
          <div class="muted" style="font-size:12px; margin-bottom:6px;">Tahun</div>
          <select name="year" class="input" style="min-width:140px;">
            @foreach($years as $y)
              <option value="{{ $y->year }}" @selected((int)$year === (int)$y->year)>{{ $y->year }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn--primary">Terapkan</button>
      </form>

      <form method="POST" action="{{ route('admin.dpt.sync') }}">
        @csrf
        <input type="hidden" name="election_year_id" value="{{ $yearRow?->id }}" />
        <button type="submit" class="btn" @disabled(!$yearRow)>Sinkronkan Suara Masuk</button>
      </form>
    </div>

    <div style="height:14px"></div>

    @if(session('status'))
      <div class="muted" style="padding:10px 12px; border:1px solid rgba(0,0,0,.08); border-radius:10px;">
        {{ session('status') }}
      </div>
      <div style="height:12px"></div>
    @endif

    <form method="POST" action="{{ route('admin.dpt.update') }}" style="display:grid; gap:12px;">
      @csrf
      @method('PUT')

      <input type="hidden" name="election_year_id" value="{{ $yearRow?->id }}" />

      <div style="overflow:auto; border:1px solid rgba(0,0,0,.08); border-radius:12px;">
        <table class="table" style="min-width:720px;">
          <thead>
            <tr>
              <th style="text-align:left;">Kabupaten</th>
              <th style="text-align:right; width:200px;">DPT</th>
              <th style="text-align:right; width:200px;">Suara Masuk (akumulasi)</th>
              <th style="text-align:right; width:140px;">Progress</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              <tr>
                <td style="font-weight:800;">{{ $r['area_name'] }}</td>
                <td style="text-align:right;">
                  <input
                    class="input"
                    type="number"
                    min="0"
                    name="dpt[{{ $r['area_id'] }}]"
                    value="{{ old('dpt.' . $r['area_id'], $r['registered_voters']) }}"
                    style="width:180px; text-align:right;"
                    @disabled(!$yearRow)
                  />
                </td>
                <td style="text-align:right;">{{ number_format($r['votes_cast'], 0, ',', '.') }}</td>
                <td style="text-align:right;">{{ $r['progress_pct'] }}%</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="muted">Tidak ada data kabupaten.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @error('dpt')
        <div class="muted" style="color:#b91c1c;">{{ $message }}</div>
      @enderror

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="btn btn--primary" @disabled(!$yearRow)>Simpan DPT</button>
        <div class="muted" style="font-size:12px;">
          Suara masuk dihitung dari total input TPS per desa.
        </div>
      </div>
    </form>
  </div>
@endsection

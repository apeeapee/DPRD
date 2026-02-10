@extends('layouts.app')

@section('title', 'Admin - Edit Suara Desa')
@section('page_title', 'Edit Suara Desa')
@section('page_subtitle', 'Perbarui suara masuk')

@section('content')
  <div class="card" style="max-width:760px; margin: 0 auto;">
    <div class="card" style="background: rgba(15,23,42,.03); border-color: rgba(15,23,42,.08); margin-bottom:12px;">
      <div class="muted" style="margin-bottom:6px;">Tahun</div>
      <div style="font-weight:900;">{{ $year?->year ?? '—' }}</div>

      <div style="height:10px"></div>
      <div class="muted" style="margin-bottom:6px;">Wilayah</div>
      <div style="font-weight:800;">
        {{ $village->regency_name ?? '—' }} / {{ $village->subdistrict_name ?? '—' }} / {{ $village->village_name ?? '—' }}
      </div>
    </div>

    <form method="POST" action="{{ route('admin.village-votes.update', $villageVote) }}" style="display:grid; gap:12px;">
      @csrf
      @method('PUT')

      <div>
        <div class="muted" style="margin-bottom:6px;">Suara Masuk</div>
        <input name="votes_cast" type="number" min="0" value="{{ old('votes_cast', $villageVote->votes_cast) }}" required
          style="width:100%; border-radius:12px; border:1px solid rgba(15,23,42,.10); padding:10px 12px;" />
        @error('votes_cast')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="area-popup__btn" style="width:auto; padding: 8px 12px;">Simpan</button>
        <a href="{{ route('admin.village-votes.index', ['year' => $year?->year]) }}" class="muted" style="text-decoration:none;">Batal</a>
      </div>
    </form>
  </div>
@endsection

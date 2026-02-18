@extends('layouts.app')

@section('title', 'Admin - Edit Calon')
@section('page_title', 'Edit Calon')
@section('page_subtitle', 'Perbarui data calon')

@section('content')
  <div class="card" style="max-width:760px; margin: 0 auto;">
    <form method="POST" action="{{ route('admin.candidates.update', $candidate) }}" style="display:grid; gap:12px;">
      @csrf
      @method('PUT')

      <div>
        <div class="muted" style="margin-bottom:6px;">Partai</div>
        <select name="party_id" required style="width:100%;">
          <option value="">-- Pilih Partai --</option>
          @foreach($parties as $p)
            <option value="{{ $p->id }}" @selected((string)old('party_id', $candidate->party_id) === (string)$p->id)>
              {{ $p->code }} — {{ $p->name }}
            </option>
          @endforeach
        </select>
        @error('party_id')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Kabupaten</div>
        <select name="area_id" required style="width:100%;">
          <option value="">-- Pilih Kabupaten --</option>
          @foreach($areas as $a)
            <option value="{{ $a->id }}" @selected((string)old('area_id', $candidate->area_id) === (string)$a->id)>
              {{ $a->name }}
            </option>
          @endforeach
        </select>
        @error('area_id')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">No Urut (opsional)</div>
        <input name="number" type="number" min="1" value="{{ old('number', $candidate->number) }}" placeholder="1" style="width:100%;" />
        @error('number')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Nama Calon</div>
        <input name="name" type="text" value="{{ old('name', $candidate->name) }}" required style="width:100%;" />
        @error('name')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="btn btn--primary">Simpan</button>
        <a href="{{ route('admin.candidates.index', ['party_id' => $candidate->party_id]) }}" class="btn btn--ghost btn--muted">Batal</a>
      </div>
    </form>
  </div>
@endsection

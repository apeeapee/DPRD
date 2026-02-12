@extends('layouts.app')

@section('title', 'Admin - Edit Partai')
@section('page_title', 'Edit Partai')
@section('page_subtitle', 'Perbarui data partai')

@section('content')
  <div class="card" style="max-width:760px; margin: 0 auto;">
    <form method="POST" action="{{ route('admin.parties.update', $party) }}" style="display:grid; gap:12px;">
      @csrf
      @method('PUT')

      <div>
        <div class="muted" style="margin-bottom:6px;">Kode</div>
        <input name="code" type="text" value="{{ old('code', $party->code) }}" required style="width:100%;" />
        @error('code')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Nama</div>
        <input name="name" type="text" value="{{ old('name', $party->name) }}" required style="width:100%;" />
        @error('name')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div>
        <div class="muted" style="margin-bottom:6px;">Warna (opsional)</div>
        <input name="color" type="text" value="{{ old('color', $party->color) }}" placeholder="#ff0000" style="width:100%;" />
        <div class="muted" style="margin-top:6px;">Format hex 6 digit. Boleh tanpa tanda #.</div>
        @error('color')<div class="muted" style="color:#b91c1c; margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <button type="submit" class="btn btn--primary">Simpan</button>
        <a href="{{ route('admin.parties.index') }}" class="btn btn--ghost btn--muted">Batal</a>
      </div>
    </form>
  </div>
@endsection

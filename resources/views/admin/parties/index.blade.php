@extends('layouts.app')

@section('title', 'Admin - Data Partai')
@section('page_title', 'Admin: Data Partai')
@section('page_subtitle', 'Kelola partai (kode, nama, warna)')

@section('content')
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-end; flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.parties.index') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <div>
          <div class="muted" style="margin-bottom:6px;">Cari</div>
          <input type="text" name="q" value="{{ $q }}" placeholder="Kode / nama partai" style="min-width:320px;" />
        </div>
        <button type="submit" class="btn btn--primary">Terapkan</button>
        <a href="{{ route('admin.parties.index') }}" class="btn btn--ghost btn--muted">Reset</a>
      </form>

      <a href="{{ route('admin.parties.create') }}" class="btn btn--primary">+ Tambah Partai</a>
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
            <th style="width:120px;">Kode</th>
            <th>Nama</th>
            <th style="width:160px;">Warna</th>
            <th style="width:160px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td style="font-weight:800;">{{ $r->code }}</td>
              <td>{{ $r->name }}</td>
              <td>
                <div style="display:flex; gap:10px; align-items:center;">
                  <span style="display:inline-block; width:14px; height:14px; border-radius:4px; border:1px solid rgba(15,23,42,.10); background: {{ $r->color ?: 'rgba(15,23,42,.08)' }};"></span>
                  <span class="muted">{{ $r->color ?: '—' }}</span>
                </div>
              </td>
              <td>
                <div style="display:flex; gap:8px;">
                  <a href="{{ route('admin.parties.edit', $r) }}" class="btn btn--primary" style="padding:6px 10px;">Edit</a>
                  <form method="POST" action="{{ route('admin.parties.destroy', $r) }}" onsubmit="return confirm('Hapus partai ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger" style="padding:6px 10px;">Hapus</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">Belum ada data partai.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:12px;">
      {{ $rows->links() }}
    </div>
  </div>
@endsection

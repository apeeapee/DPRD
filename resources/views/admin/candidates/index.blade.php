@extends('layouts.app')

@section('title', 'Admin - Data Calon')
@section('page_title', 'Admin: Data Calon')
@section('page_subtitle', 'Kelola calon per partai')

@section('content')
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-end; flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.candidates.index') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <div>
          <div class="muted" style="margin-bottom:6px;">Partai</div>
          <select name="party_id" style="min-width:280px;">
            <option value="">-- Semua Partai --</option>
            @foreach($parties as $p)
              <option value="{{ $p->id }}" @selected((string)$p->id === (string)$partyId)>
                {{ $p->code }} — {{ $p->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <div class="muted" style="margin-bottom:6px;">Cari</div>
          <input type="text" name="q" value="{{ $q }}" placeholder="Nama calon" style="min-width:320px;" />
        </div>

        <button type="submit" class="btn btn--primary">Terapkan</button>
        <a href="{{ route('admin.candidates.index') }}" class="btn btn--ghost btn--muted">Reset</a>
      </form>

      <a href="{{ route('admin.candidates.create') }}" class="btn btn--primary">+ Tambah Calon</a>
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
            <th>Partai</th>
            <th style="width:120px;">No</th>
            <th>Nama Calon</th>
            <th style="width:160px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td>
                <div style="display:flex; gap:10px; align-items:center;">
                  <span style="font-weight:900;">{{ $r->party?->code }}</span>
                  <span class="muted">{{ $r->party?->name }}</span>
                </div>
              </td>
              <td style="font-weight:800;">{{ $r->number ?? '—' }}</td>
              <td>{{ $r->name }}</td>
              <td>
                <div style="display:flex; gap:8px;">
                  <a href="{{ route('admin.candidates.edit', $r) }}" class="btn btn--primary" style="padding:6px 10px;">Edit</a>
                  <form method="POST" action="{{ route('admin.candidates.destroy', $r) }}" onsubmit="return confirm('Hapus calon ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger" style="padding:6px 10px;">Hapus</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">Belum ada data calon.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:12px;">
      {{ $rows->links() }}
    </div>
  </div>
@endsection

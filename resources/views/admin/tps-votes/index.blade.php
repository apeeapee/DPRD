@extends('layouts.app')

@section('title', 'Admin - Input Suara TPS')
@section('page_title', 'Admin: Input Suara TPS')
@section('page_subtitle', 'Input suara per TPS (per caleg)')

@section('content')
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-end; flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.tps-votes.index') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <div>
          <div class="muted" style="margin-bottom:6px;">Tahun</div>
          <select name="year" style="width:auto;">
            @foreach($years as $y)
              <option value="{{ $y->year }}" @selected($y->year == $year)>{{ $y->year }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <div class="muted" style="margin-bottom:6px;">Kecamatan</div>
          <select name="subdistrict_id" onchange="this.form.submit()" style="min-width:280px;">
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
          <select name="village_id" style="min-width:240px;" @disabled(!$subdistrictId)>
            <option value="">-- Pilih Desa --</option>
            @foreach($villages as $v)
              <option value="{{ $v->id }}" @selected((string)$v->id === (string)$villageId)>{{ $v->name }}</option>
            @endforeach
          </select>
        </div>

        <button type="submit" class="area-popup__btn" style="width:auto; padding: 8px 12px;">Terapkan</button>
      </form>

      <a href="{{ route('admin.tps-votes.create', ['year' => $year, 'subdistrict_id' => $subdistrictId, 'village_id' => $villageId]) }}" class="btn btn--primary" style="text-decoration:none;">+ Input Suara TPS</a>
    </div>

    @if (session('status'))
      <div class="card" style="margin-top:12px; background: rgba(34,197,94,.06); border-color: rgba(34,197,94,.18);">
        <div class="muted" style="color:#166534; font-weight:700;">{{ session('status') }}</div>
      </div>
    @endif
  </div>

  <div class="card">
    @if(!$villageId)
      <div class="muted">Pilih <b>Kecamatan</b> lalu <b>Desa</b> untuk melihat daftar TPS.</div>
    @else
      @if($tps->isEmpty())
        <div class="muted">Belum ada TPS untuk desa ini. Klik <b>+ Input Suara TPS</b> untuk membuat TPS dan mengisi suara.</div>
      @else
        <div style="overflow:auto;">
          <table>
            <thead>
              <tr>
                <th style="width:160px;">TPS</th>
                <th style="width:140px;">Total Suara</th>
                <th style="width:160px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($tps as $ps)
                @php
                  $totalRow = $totalsByTps->get($ps->id);
                  $totalVotes = (int) ($totalRow->total_votes ?? 0);
                @endphp
                <tr>
                  <td style="font-weight:900;">{{ $ps->code }}</td>
                  <td style="font-variant-numeric: tabular-nums;">{{ number_format($totalVotes, 0, ',', '.') }}</td>
                  <td>
                    <a class="btn btn--primary" style="padding:6px 10px; text-decoration:none;" href="{{ route('admin.tps-votes.edit', [$ps, 'year' => $year]) }}">Edit</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    @endif
  </div>
@endsection

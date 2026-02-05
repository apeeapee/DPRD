<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Dashboard DPRD Jateng')</title>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

  <style>
    body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background:#f5f7fb; }
    .wrap { display:flex; min-height:100vh; }
    .sidebar { width:260px; background:#0f1b33; color:#fff; padding:18px; }
    .sidebar h2 { margin:0 0 10px; font-size:16px; }
    .sidebar a { color:#cfe2ff; text-decoration:none; display:block; padding:10px 8px; border-radius:8px; }
    .sidebar a:hover { background:rgba(255,255,255,.08); }

    .main { flex:1; padding:18px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    .card { background:#fff; border-radius:14px; padding:14px; box-shadow:0 6px 20px rgba(15, 27, 51, .06); }
    .grid { display:grid; grid-template-columns: 1.2fr .8fr; gap:14px; }
    #map { height: 520px; border-radius: 14px; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:8px; border-bottom:1px solid #eee; text-align:left; font-size:14px; }
    .muted { color:#666; font-size:13px; }
    .kpi { display:flex; gap:10px; margin-top:10px; }
    .kpi .box { flex:1; background:#f5f7fb; border-radius:12px; padding:10px; }
    .kpi .val { font-weight:700; font-size:18px; }
  </style>
</head>
<body>
  <div class="wrap">
    <aside class="sidebar">
      <h2>Dashboard Pemilu DPRD</h2>
      <div class="muted">Jawa Tengah</div>
      <div style="height:12px"></div>
      <a href="{{ route('dashboard') }}">Dashboard</a>
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <div style="font-weight:700; font-size:18px;">@yield('page_title','Dashboard')</div>
          <div class="muted">@yield('page_subtitle','Visualisasi ringkasan & hasil per wilayah')</div>
        </div>

        <div class="card" style="padding:10px 12px;">
          Tahun:
          <select id="yearSelect">
            <option value="2024">2024</option>
          </select>
        </div>
      </div>

      @yield('content')
    </main>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  @stack('scripts')
</body>
</html>

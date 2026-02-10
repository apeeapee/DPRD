<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Dashboard DPRD Jateng')</title>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

  <style>
    :root{
      /* Dashboard tone (bright) */
      --bg: #f6f8fc;
      --surface: rgba(255,255,255,.86);
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --border: rgba(15, 23, 42, .08);
      --shadow: 0 10px 30px rgba(2, 6, 23, .10);
      --shadow-soft: 0 8px 20px rgba(15, 23, 42, .08);
      --primary: #2563eb;
      --primary-2: #60a5fa;
      --accent: #f97316;
      --radius: 16px;
    }

    *{ box-sizing: border-box; }

    body{
      margin: 0;
      color: var(--text);
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background:
        radial-gradient(1200px 600px at 10% 0%, rgba(37, 99, 235, .10), transparent 55%),
        radial-gradient(1000px 600px at 90% 10%, rgba(249, 115, 22, .10), transparent 55%),
        var(--bg);
    }

    .wrap{ display:flex; min-height:100vh; }

    .sidebar{
      width: 280px;
      padding: 18px;
      color: #e5e7eb;
      background:
        radial-gradient(1200px 600px at 30% -10%, rgba(96, 165, 250, .28), transparent 50%),
        linear-gradient(180deg, #0b1020, #0f1b33 60%, #0b1020);
      border-right: 1px solid rgba(255,255,255,.08);
    }

    .sidebar h2{
      margin: 0;
      font-size: 14px;
      letter-spacing: .3px;
      text-transform: uppercase;
      opacity: .95;
    }

    .sidebar .muted{ color: rgba(226,232,240,.75); }

    .sidebar a{
      color: rgba(226,232,240,.92);
      text-decoration:none;
      display:flex;
      gap:10px;
      align-items:center;
      padding: 10px 10px;
      border-radius: 12px;
      border: 1px solid transparent;
      transition: all .15s ease;
    }
    .sidebar a:hover{
      background: rgba(255,255,255,.06);
      border-color: rgba(255,255,255,.08);
      transform: translateY(-1px);
    }

    .main{ flex:1; padding: 18px; max-width: 1400px; width:100%; margin: 0 auto; }

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap: 14px;
      margin-bottom: 14px;
    }

    .card{
      background: var(--card);
      border-radius: var(--radius);
      padding: 14px;
      border: 1px solid var(--border);
      box-shadow: var(--shadow-soft);
      backdrop-filter: blur(8px);
    }

    .grid{ display:grid; grid-template-columns: 1.25fr .75fr; gap: 14px; align-items:start; }

    #map{
      height: 560px;
      border-radius: var(--radius);
      overflow: hidden;
      border: 1px solid var(--border);
    }

    /* Select styling */
    select{
      appearance: none;
      border-radius: 12px;
      border: 1px solid var(--border);
      padding: 9px 34px 9px 10px;
      background:
        linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.86));
      color: var(--text);
      font-weight: 600;
      outline: none;
    }
    select:focus{ border-color: rgba(37, 99, 235, .35); box-shadow: 0 0 0 4px rgba(37, 99, 235, .12); }

    input[type="text"], input[type="search"], input[type="number"]{
      border-radius: 12px;
      border: 1px solid var(--border);
      padding: 9px 12px;
      background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.86));
      color: var(--text);
      outline: none;
    }
    input[type="text"]:focus, input[type="search"]:focus, input[type="number"]:focus{
      border-color: rgba(37, 99, 235, .35);
      box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    .btn{
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: rgba(2, 6, 23, .03);
      color: var(--text);
      padding: 9px 12px;
      font-weight: 800;
      cursor: pointer;
      text-decoration: none;
      transition: transform .12s ease, filter .12s ease, background .12s ease;
      user-select: none;
      white-space: nowrap;
    }
    .btn:hover{ filter: brightness(1.06); transform: translateY(-1px); }
    .btn:active{ transform: translateY(0px); }
    .btn--primary{ background: rgba(37,99,235,.10); border-color: rgba(37,99,235,.18); }
    .btn--danger{ background: rgba(185,28,28,.10); border-color: rgba(185,28,28,.18); }
    .btn--ghost{ background: rgba(2, 6, 23, .02); }
    .btn--muted{ color: var(--muted); font-weight: 700; }

    .toolbar{
      display:flex;
      gap:10px;
      align-items:center;
      flex-wrap:wrap;
    }

    .filters{
      display:flex;
      gap:10px;
      align-items:flex-end;
      flex-wrap:wrap;
    }

    .chip{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: rgba(2, 6, 23, .03);
      color: var(--muted);
      font-weight: 800;
      font-size: 12px;
      letter-spacing: .25px;
    }

    table{ width:100%; border-collapse: separate; border-spacing: 0; }
    thead th{
      position: sticky;
      top: 0;
      z-index: 1;
      background: #fff;
      border-bottom: 1px solid var(--border);
      color: #0b1220;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .45px;
    }
    th, td{ padding: 10px 10px; text-align:left; font-size: 14px; }
    tbody tr{ transition: background .12s ease; }
    tbody tr:hover{ background: rgba(2, 6, 23, .03); }
    tbody td{ border-bottom: 1px solid rgba(15, 23, 42, .06); }

    .muted{ color: var(--muted); font-size: 13px; }

    .kpi{ display:flex; gap: 10px; margin-top: 10px; }
    .kpi .box{
      flex: 1;
      background: linear-gradient(180deg, rgba(37,99,235,.06), rgba(249,115,22,.03));
      border: 1px solid rgba(15,23,42,.06);
      border-radius: 14px;
      padding: 10px;
    }
    .kpi .val{ font-weight: 800; font-size: 20px; letter-spacing: .2px; }

    /* Better spacing on small screens */
    @media (max-width: 980px){
      .wrap{ display:block; }
      .sidebar{ width:auto; border-right:0; border-bottom: 1px solid rgba(255,255,255,.08); }
      .grid{ grid-template-columns: 1fr; }
      #map{ height: 460px; }
      .topbar{ flex-direction: column; align-items: flex-start; }
    }
  </style>

  @stack('styles')
</head>
<body>
  <div class="wrap">
    <aside class="sidebar">
      <h2>Dashboard Pemilu DPRD</h2>
      <div class="muted">Jawa Tengah</div>
      <div style="height:12px"></div>
      @auth
        @if(auth()->user()->is_admin)
          <a href="{{ route('admin.dashboard') }}">Dashboard Admin</a>
          <a href="{{ route('admin.village-votes.index') }}">Suara Masuk Desa</a>
        @else
          <a href="{{ route('user.dashboard') }}">Dashboard User</a>
          <a href="{{ route('village-votes') }}">Suara Masuk Desa</a>
        @endif
        <div style="height:10px"></div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" style="width:100%; border:1px solid rgba(255,255,255,.10); background: rgba(255,255,255,.06); color: rgba(226,232,240,.92); padding:10px 10px; border-radius:12px; font-weight:800; cursor:pointer;">Logout</button>
        </form>
      @else
        <a href="{{ route('login') }}">Login</a>
      @endauth
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <div style="font-weight:800; font-size:18px; letter-spacing:.2px;">@yield('page_title','Dashboard')</div>
          <div class="muted" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:6px;">
            <span>@yield('page_subtitle','Visualisasi ringkasan & hasil per wilayah')</span>
            <span class="chip">Update: {{ now()->format('d M Y, H:i') }}</span>
          </div>
        </div>

        <div class="toolbar">
          <input type="search" placeholder="Cari kabupaten/kecamatan/desa, partai, caleg" style="min-width: 340px;" />
          <a class="btn btn--ghost" href="#" onclick="return false;">Export Excel</a>
          <a class="btn btn--ghost" href="#" onclick="return false;">Export PDF</a>
          <button class="btn btn--primary" type="button" onclick="window.location.reload();">Refresh</button>
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

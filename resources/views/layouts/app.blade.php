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
      display: flex;
      flex-direction: column;
      background:
        radial-gradient(1200px 600px at 30% -10%, rgba(96, 165, 250, .28), transparent 50%),
        linear-gradient(180deg, #0b1020, #0f1b33 60%, #0b1020);
      border-right: 1px solid rgba(255,255,255,.08);
    }

    .sidebar__nav{ display:flex; flex-direction:column; }
    .sidebar__spacer{ flex: 1; }
    .sidebar__footer{ margin-top: 12px; }

    .sidebar__profile{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 10px;
      padding: 10px;
      border-radius: 14px;
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.06);
    }
    .sidebar__profile-left{ display:flex; align-items:center; gap: 10px; min-width: 0; }
    .sidebar__avatar{
      width: 36px;
      height: 36px;
      border-radius: 999px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight: 950;
      color: rgba(226,232,240,.92);
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.10);
      flex: 0 0 auto;
    }
    .sidebar__who{ min-width: 0; }
    .sidebar__name{ font-weight: 950; font-size: 14px; color: rgba(226,232,240,.92); line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sidebar__email{ margin-top: 2px; font-size: 11px; color: rgba(226,232,240,.70); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sidebar__logoutBtn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap: 8px;
      padding: 8px 10px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.08);
      color: rgba(226,232,240,.92);
      font-weight: 950;
      cursor: pointer;
      white-space: nowrap;
    }
    .sidebar__logoutBtn:hover{ filter: brightness(1.08); transform: translateY(-1px); }
    .sidebar__logoutBtn:active{ transform: translateY(0px); }

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
      align-items:flex-start;
      gap: 14px;
      margin-bottom: 14px;
    }

    .main__scroll{ width: 100%; }

    /* Fixed chrome (admin-like): only content scrolls */
    html, body{ height: 100%; }
    body.chrome-fixed{ height: 100vh; overflow: hidden; }
    body.chrome-fixed .wrap{ height: 100vh; min-height: 100vh; }
    body.chrome-fixed .sidebar{ position: sticky; top: 0; height: 100vh; overflow: auto; }
    body.chrome-fixed .main{ height: 100vh; padding: 0; max-width: none; margin: 0; overflow: hidden; }
    body.chrome-fixed .main__scroll{
      height: 100vh;
      overflow: auto;
      padding: 0 18px 18px;
      max-width: 1400px;
      width: 100%;
      margin: 0 auto;
    }
    body.chrome-fixed .topbar{
      position: sticky;
      top: 0;
      z-index: 10;
      padding: 14px 0;
      background: var(--bg);
      border-bottom: 1px solid var(--border);
      backdrop-filter: none;
    }

    .topbar__left{ flex: 1; min-width: 280px; }
    .topbar__title{ font-weight:800; font-size:22px; letter-spacing:.2px; line-height: 1.2; }
    .topbar__meta{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:6px; }

    .topbar__right{ flex: 1; display:flex; justify-content:flex-end; }
    .toolbar{
      width:100%;
      display:flex;
      justify-content:flex-end;
      gap:10px;
      align-items:center;
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

    /* Laravel pagination (default Tailwind view) – keep icons compact even without Tailwind */
    nav[role="navigation"][aria-label*="Pagination"] > div:first-child{ display:none; }
    nav[role="navigation"][aria-label*="Pagination"] > div:last-child{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      flex-wrap:wrap;
    }
    nav[role="navigation"][aria-label*="Pagination"] svg{
      width: 18px;
      height: 18px;
      display:block;
    }
    nav[role="navigation"][aria-label*="Pagination"] a,
    nav[role="navigation"][aria-label*="Pagination"] span{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap: 8px;
    }
    @media (max-width: 640px){
      nav[role="navigation"][aria-label*="Pagination"] > div:first-child{ display:flex; gap:10px; justify-content:space-between; }
      nav[role="navigation"][aria-label*="Pagination"] > div:last-child{ display:none; }
    }

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
      .topbar{ flex-direction: column; align-items: stretch; }
      .topbar__right{ justify-content:flex-start; }
      .toolbar{ display:flex; justify-content:flex-start; }
      .toolbar input[type="search"]{ max-width: 100%; }

      body.chrome-fixed{ height:auto; overflow:auto; }
      body.chrome-fixed .wrap{ height:auto; min-height:100vh; }
      body.chrome-fixed .sidebar{ position: relative; height:auto; overflow: visible; }
      body.chrome-fixed .main{ height:auto; overflow: visible; }
      body.chrome-fixed .main__scroll{ height:auto; overflow: visible; padding: 18px; max-width: 1400px; margin: 0 auto; }
      body.chrome-fixed .topbar{ position: static; background: transparent; backdrop-filter: none; padding-top: 0; }
    }
  </style>

  @stack('styles')
</head>
@php
  $hideSidebar = trim($__env->yieldContent('hide_sidebar')) === '1';
  $hideTopbar = trim($__env->yieldContent('hide_topbar')) === '1';
  $fixedChrome = (!$hideSidebar) && (!$hideTopbar);
@endphp
<body class="{{ $fixedChrome ? 'chrome-fixed' : '' }}">
  <div class="wrap">
    @unless($hideSidebar)
      <aside class="sidebar">
        <h2>Dashboard Pemilu DPRD</h2>
        <div class="muted">Jawa Tengah</div>
        <div style="height:12px"></div>
        @auth
          <div class="sidebar__nav">
            @if(auth()->user()->is_admin)
              <a href="{{ route('admin.dashboard') }}">Dashboard Admin</a>
              <a href="{{ route('admin.village-votes.index') }}">Suara Masuk Desa</a>
              <a href="{{ route('admin.tps-votes.index') }}">Input Suara TPS</a>
              <a href="{{ route('admin.candidates.index') }}">Data Calon</a>
              <a href="{{ route('admin.parties.index') }}">Data Partai</a>
            @else
              <a href="{{ route('user.dashboard') }}">Dashboard</a>
            @endif
          </div>

          <div class="sidebar__spacer"></div>

          <div class="sidebar__footer">
            <div class="sidebar__profile">
              <div class="sidebar__profile-left">
                @php
                  $uName = auth()->user()->name ?? 'User';
                  $uEmail = auth()->user()->email ?? '';
                  $initial = strtoupper(mb_substr($uName, 0, 1));
                @endphp
                <div class="sidebar__avatar" aria-hidden="true">{{ $initial }}</div>
                <div class="sidebar__who">
                  <div class="sidebar__name">{{ $uName }}</div>
                  <div class="sidebar__email">{{ $uEmail }}</div>
                </div>
              </div>

              <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button class="sidebar__logoutBtn" type="submit">Logout</button>
              </form>
            </div>
          </div>
        @else
          <div class="sidebar__nav">
            <a href="{{ route('login') }}">Login</a>
          </div>
        @endauth
      </aside>
    @endunless

    <main class="main">
      <div class="main__scroll">
        @unless($hideTopbar)
          <div class="topbar">
            <div class="topbar__left">
              <div class="topbar__title">@yield('page_title','Dashboard')</div>
              <div class="muted topbar__meta">
                <span>@yield('page_subtitle','Visualisasi ringkasan & hasil per wilayah')</span>
                <span class="chip">Update: {{ now()->format('d M Y, H:i') }}</span>
              </div>
            </div>

            <div class="topbar__right">
              <div class="toolbar">
                @yield('topbar_right')
              </div>
            </div>
          </div>
        @endunless

        @yield('content')
      </div>
    </main>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  @stack('scripts')
</body>
</html>

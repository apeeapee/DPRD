<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Login')</title>
  <style>
    :root{
      --bg: #f6f8fc;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --border: rgba(15, 23, 42, .08);
      --shadow-soft: 0 8px 20px rgba(15, 23, 42, .08);
      --primary: #2563eb;
      --radius: 16px;
    }
    *{ box-sizing: border-box; }
    body{
      margin: 0;
      min-height: 100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 20px;
      background:
        radial-gradient(1200px 600px at 10% 0%, rgba(37, 99, 235, .10), transparent 55%),
        radial-gradient(1000px 600px at 90% 10%, rgba(249, 115, 22, .10), transparent 55%),
        var(--bg);
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      color: var(--text);
    }
    .card{
      width: 100%;
      max-width: 420px;
      background: var(--card);
      border-radius: var(--radius);
      padding: 16px;
      border: 1px solid var(--border);
      box-shadow: var(--shadow-soft);
    }
    .muted{ color: var(--muted); font-size: 13px; }
    label{ display:block; font-weight:700; font-size: 13px; margin-bottom: 6px; }
    input{
      width: 100%;
      border-radius: 12px;
      border: 1px solid var(--border);
      padding: 10px 12px;
      outline: none;
    }
    input:focus{ border-color: rgba(37, 99, 235, .35); box-shadow: 0 0 0 4px rgba(37, 99, 235, .12); }
    button{
      width: 100%;
      border: 0;
      border-radius: 12px;
      padding: 10px 12px;
      background: var(--primary);
      color:#fff;
      font-weight: 800;
      cursor: pointer;
    }
    .err{ color:#b91c1c; font-size: 13px; margin-top: 6px; }
  </style>
</head>
<body>
  @yield('content')
</body>
</html>

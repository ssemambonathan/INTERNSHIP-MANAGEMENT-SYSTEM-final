<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102a43">
    <title>@yield('code') · @yield('title') · InternTrack</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 18px;
            background: radial-gradient(1100px 550px at 15% -10%, #e9eff7 0%, transparent 60%), #f4f6f9;
            color: #243b53;
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
        }
        .shell { width: 100%; max-width: 560px; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; color: #102a43; }
        .brand-mark {
            width: 40px; height: 40px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            background: #102a43; color: #d8b45a; font-weight: 700; font-size: 18px;
            box-shadow: 0 6px 18px rgba(16, 42, 67, .18);
        }
        .brand-name { font-weight: 700; letter-spacing: .2px; font-size: 1.02rem; }
        .brand-sub { display: block; font-weight: 500; color: #64748b; font-size: .72rem; letter-spacing: .3px; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 34px 32px 30px;
            box-shadow: 0 18px 40px rgba(15, 35, 58, .08);
        }
        .icon-wrap {
            width: 58px; height: 58px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            background: #eef3f9; color: #1d4b7a; margin-bottom: 18px;
        }
        .code {
            font-size: .74rem; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: #a67c2e; margin-bottom: 6px;
        }
        h1 { margin: 0 0 10px; font-size: 1.5rem; line-height: 1.25; color: #102a43; }
        p { margin: 0 0 10px; font-size: .9rem; line-height: 1.65; color: #52667a; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 10px 18px; border-radius: 10px; text-decoration: none;
            font-size: .84rem; font-weight: 600; border: 1px solid transparent;
            transition: background .15s ease, border-color .15s ease;
        }
        .btn-primary { background: #102a43; color: #fff; }
        .btn-primary:hover { background: #173d5f; }
        .btn-outline { border-color: #cbd5e1; color: #334e68; background: #fff; }
        .btn-outline:hover { border-color: #94a3b8; background: #f8fafc; }
        .footer { margin-top: 20px; text-align: center; font-size: .72rem; color: #829ab1; line-height: 1.6; }
        .footer strong { color: #52667a; font-weight: 600; }
        @media (max-width: 480px) {
            .card { padding: 26px 22px 24px; border-radius: 14px; }
            h1 { font-size: 1.28rem; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="brand">
            <span class="brand-mark">IT</span>
            <span class="brand-name">InternTrack
                <span class="brand-sub">Internship Monitoring &amp; Assessment</span>
            </span>
        </div>
        <div class="card">
            <div class="icon-wrap">
                @yield('icon')
            </div>
            <div class="code">Error @yield('code')</div>
            <h1>@yield('title')</h1>
            @yield('message')
            <div class="actions">
                @yield('actions')
                <a class="btn btn-outline" href="{{ url('/') }}">Home</a>
            </div>
        </div>
        <div class="footer">
            <strong>InternTrack v1.0</strong><br>
            If the problem persists, contact the internship coordinator or your system administrator.
        </div>
    </div>
</body>
</html>

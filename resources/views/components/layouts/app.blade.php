<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Z-Syst' }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f8fafc;
            color: #111827;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; }
        a { color: inherit; text-decoration: none; }
        .page { max-width: 1200px; margin: 0 auto; padding: 32px 24px 72px; }
        .header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 32px; }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; letter-spacing: -.04em; font-size: 1.05rem; }
        .brand-mark { width: 38px; height: 38px; border-radius: 14px; background: #2563eb; display: grid; place-items: center; color: white; font-weight: 700; }
        .nav { display: flex; gap: 14px; flex-wrap: wrap; }
        .nav a { color: #2563eb; font-weight: 600; }
        .content { background: #ffffff; border-radius: 32px; padding: 36px; box-shadow: 0 24px 80px rgba(15, 23, 42, 0.08); }
        .footer { margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #4b5563; font-size: .95rem; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 14px 24px; border-radius: 999px; background: #2563eb; color: #ffffff; font-weight: 700; transition: transform .16s ease, background-color .16s ease; }
        .button:hover { transform: translateY(-1px); background: #1d4ed8; }
        @media (max-width: 700px) { .page { padding: 24px 16px 48px; } }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <a class="brand" href="/">
                <span class="brand-mark">Z</span>
                <span>Z-Syst</span>
            </a>
            <nav class="nav">
                <a href="/">Home</a>
                <a href="/features">Features</a>
                <a href="/api/info">API Info</a>
            </nav>
        </header>
        <section class="content">
            {{ $slot }}
        </section>
        <footer class="footer">Built for modern Egyptian pharmacies that need offline resilience, multi-branch control, and intelligent pharmacy operations.</footer>
    </main>
</body>
</html>

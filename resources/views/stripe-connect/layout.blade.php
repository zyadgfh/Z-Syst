<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stripe Connect') — {{ config('app.name', 'Z-Syst') }}</title>
    <style>
        /* Clean, Apple-inspired styling matching Z-Syst's existing design system.
           Self-contained inline CSS satisfies Z-Syst's strict Content-Security-Policy
           (style-src 'self' 'unsafe-inline') without external CDN dependencies. */
        :root {
            --ap-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --ap-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            --ap-bg: #f5f5f7;
            --ap-surface: #ffffff;
            --ap-surface-muted: #fafafa;
            --ap-text: #1d1d1f;
            --ap-text-2: #6e6e73;
            --ap-hairline: rgba(0, 0, 0, 0.08);
            --ap-accent: #0071e3;
            --ap-accent-hover: #005bb5;
            --ap-green: #248a3d;
            --ap-green-bg: #eaf8ee;
            --ap-amber: #9a6700;
            --ap-amber-bg: #fff6e0;
            --ap-red: #c92a2a;
            --ap-red-bg: #ffecec;
            --ap-radius: 18px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: var(--ap-font);
            background: var(--ap-bg);
            color: var(--ap-text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid var(--ap-hairline);
        }

        .topbar-inner {
            max-width: 1080px;
            margin: 0 auto;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
            color: var(--ap-text);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: var(--ap-text-2);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 980px;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .nav-link:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--ap-text);
        }

        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 28px 20px 64px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0 0 6px;
            font-size: 1.85rem;
            letter-spacing: -0.025em;
            font-weight: 700;
        }

        .page-header p {
            margin: 0;
            color: var(--ap-text-2);
            font-size: 0.98rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }

        .col-12 { grid-column: span 12; }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }

        @media (max-width: 820px) {
            .col-6, .col-4 { grid-column: span 12; }
        }

        .card {
            background: var(--ap-surface);
            border: 1px solid var(--ap-hairline);
            border-radius: var(--ap-radius);
            padding: 22px 24px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
        }

        .card + .card {
            margin-top: 20px;
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .card h2 {
            margin: 0 0 4px;
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: -0.015em;
        }

        .card h3 {
            margin: 0 0 8px;
            font-size: 1rem;
            font-weight: 600;
        }

        .muted {
            color: var(--ap-text-2);
            font-size: 0.9rem;
        }

        .mono {
            font-family: var(--ap-mono);
            font-size: 0.85rem;
            background: #f0f0f5;
            padding: 2px 6px;
            border-radius: 6px;
            word-break: break-all;
        }

        pre.code-block {
            margin: 10px 0 0;
            padding: 12px 14px;
            background: #1d1d1f;
            color: #f5f5f7;
            border-radius: 12px;
            font-family: var(--ap-mono);
            font-size: 0.8rem;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 0.92rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: var(--ap-green-bg);
            color: var(--ap-green);
            border-color: rgba(36, 138, 61, 0.2);
        }

        .alert-warning {
            background: var(--ap-amber-bg);
            color: var(--ap-amber);
            border-color: rgba(154, 103, 0, 0.2);
        }

        .alert-error {
            background: var(--ap-red-bg);
            color: var(--ap-red);
            border-color: rgba(201, 42, 42, 0.2);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 980px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-green { background: var(--ap-green-bg); color: var(--ap-green); }
        .badge-amber { background: var(--ap-amber-bg); color: var(--ap-amber); }
        .badge-red { background: var(--ap-red-bg); color: var(--ap-red); }
        .badge-neutral { background: #ececf1; color: var(--ap-text-2); }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 980px;
            border: 1px solid transparent;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease, transform 0.12s ease, opacity 0.15s ease;
        }

        .btn:active { transform: scale(0.98); }
        .btn:disabled { opacity: 0.55; cursor: not-allowed; }

        .btn-primary {
            background: var(--ap-accent);
            color: #ffffff;
        }
        .btn-primary:hover { background: var(--ap-accent-hover); }

        .btn-success {
            background: #28a745;
            color: #ffffff;
        }
        .btn-success:hover { background: #218838; }

        .btn-secondary {
            background: #ffffff;
            color: var(--ap-text);
            border-color: rgba(0, 0, 0, 0.16);
        }
        .btn-secondary:hover { background: #f2f2f7; }

        .btn-sm {
            padding: 7px 13px;
            font-size: 0.83rem;
        }

        .btn-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-field.full { grid-column: 1 / -1; }

        .form-field label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ap-text);
        }

        .form-field input,
        .form-field select,
        .form-field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 11px;
            font-family: inherit;
            font-size: 0.93rem;
            color: var(--ap-text);
            background: #ffffff;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            outline: 2px solid rgba(0, 113, 227, 0.25);
            border-color: var(--ap-accent);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin: 14px 0;
        }

        .stat-box {
            background: var(--ap-surface-muted);
            border: 1px solid var(--ap-hairline);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .stat-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--ap-text-2);
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 0.96rem;
            font-weight: 600;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }

        .product-card {
            background: var(--ap-surface);
            border: 1px solid var(--ap-hairline);
            border-radius: var(--ap-radius);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .price-tag {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--ap-text);
        }

        .list-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .list-table th,
        .list-table td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid var(--ap-hairline);
        }

        .list-table th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--ap-text-2);
        }

        .inline-error {
            display: none;
            margin-top: 10px;
        }

        .inline-error.visible {
            display: block;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('stripe-connect.dashboard') }}">
                <span>Stripe Connect</span>
                <span class="badge badge-neutral">V2 Sample</span>
            </a>
            <nav class="nav-links">
                <a class="nav-link" href="{{ route('stripe-connect.dashboard') }}">Connect Dashboard</a>
                @yield('nav_extra')
                @auth
                    <a class="nav-link" href="{{ route('admin.dashboard.index') }}">Z-Syst Admin</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 6px 0 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="js-stripe-error" class="alert alert-error inline-error" role="alert"></div>

        @yield('content')
    </main>

    <script>
        // Progressive enhancement for forms that redirect to Stripe-hosted pages
        // (Account Links, Hosted Checkout, Billing Portal). Submitting via fetch()
        // and navigating with window.location.assign(data.url) avoids Chromium
        // blocking cross-origin redirects under Z-Syst's CSP `form-action 'self'`.
        document.querySelectorAll('form[data-stripe-redirect-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                var errorBox = document.getElementById('js-stripe-error');
                if (errorBox) {
                    errorBox.classList.remove('visible');
                    errorBox.textContent = '';
                }

                var submitBtn = form.querySelector('button[type="submit"]');
                var originalLabel = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = submitBtn.getAttribute('data-loading-text') || 'Redirecting to Stripe...';
                }

                try {
                    var formData = new FormData(form);
                    var csrf = document.querySelector('meta[name="csrf-token"]');
                    var response = await fetch(form.action, {
                        method: form.method || 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''
                        },
                        body: formData
                    });

                    var payload = await response.json().catch(function () { return {}; });

                    if (!response.ok || !payload.url) {
                        var msg = payload.error || payload.message || 'Stripe request failed (HTTP ' + response.status + ').';
                        if (errorBox) {
                            errorBox.textContent = msg;
                            errorBox.classList.add('visible');
                            errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }

                    window.location.assign(payload.url);
                } catch (err) {
                    if (errorBox) {
                        errorBox.textContent = 'Network error while contacting server: ' + err.message;
                        errorBox.classList.add('visible');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalLabel;
                    }
                }
            });
        });
    </script>
    @yield('scripts')
</body>
</html>

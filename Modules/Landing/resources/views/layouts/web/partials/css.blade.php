<link rel="shortcut icon" type="image/x-icon" href="{{ asset(get_option('general')['favicon'] ?? 'assets/images/logo/logo.png')}}">
<link rel="stylesheet" href="{{ asset('assets/web/css/bootstrap.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/swiper-bundle.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/fonts/fontawesome/css/all.min.css') }}" />
<!-- Slick Slider -->
<link rel="stylesheet" href="{{ asset('assets/web/css/slick.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/slick-theme.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/aos.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/toastr.min.css') }}">
<!-- Custom Css -->
<link rel="stylesheet" href="{{ asset('assets/web/css/styles.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/responsive.css') }}" />

@stack('css')

{{-- Language Switcher & SEO Styles (Apple Design) --}}
<style>
    /* ── Language Switcher: Fluid, Physical Apple-style Toggle ── */
    .lang-switcher-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.08);
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(12px) saturate(180%);
        -webkit-backdrop-filter: blur(12px) saturate(180%);
        color: #1d1d1f;
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 0.01em;
        cursor: pointer;
        transition: transform 180ms cubic-bezier(0.25, 0.46, 0.45, 0.94),
                    background 200ms ease,
                    box-shadow 200ms ease;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        position: relative;
        overflow: hidden;
    }
    .lang-switcher-btn:hover {
        background: rgba(255,255,255,0.9);
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .lang-switcher-btn:active {
        transform: scale(0.95);
        transition: transform 80ms ease-out;
    }
    .lang-switcher-btn svg {
        flex-shrink: 0;
        opacity: 0.7;
    }
    .lang-label {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    @media (prefers-reduced-motion: reduce) {
        .lang-switcher-btn { transition: none; }
        .lang-switcher-btn:active { transform: none; }
    }
    /* Mobile offcanvas language button */
    .lang-switcher-btn-mobile {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 16px;
        border-radius: 12px;
        border: 1px solid rgba(0,0,0,0.06);
        background: #f5f5f7;
        color: #1d1d1f;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: transform 150ms ease, background 150ms ease;
    }
    .lang-switcher-btn-mobile:active {
        transform: scale(0.97);
    }
    .lang-switcher-btn-mobile svg {
        opacity: 0.6;
    }
</style>

/* ==========================================================================
   ap-theme.js — Apple-style light/dark mode for Z-Syst
   - First visit: follow the system preference.
   - Toggle persists in localStorage ('ap-theme': 'light' | 'dark').
   - Sets <html data-ap-theme="..."> and the meta theme-color.
   - Creates the floating toggle pill if .ap-theme-toggle-mount exists
     (or appends to body as a last resort).
   ========================================================================== */
(function () {
    'use strict';

    const KEY = 'ap-theme';
    const LIGHT = '#f5f5f7';
    const DARK = '#1c1c1e';

    function stored() {
        try { return localStorage.getItem(KEY); } catch { return null; }
    }

    function systemDark() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-ap-theme', theme);
        let meta = document.querySelector('meta[name="theme-color"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', 'theme-color');
            document.head.appendChild(meta);
        }
        meta.setAttribute('content', theme === 'dark' ? DARK : LIGHT);
    }

    apply(stored() || (systemDark() ? 'dark' : 'light'));

    // Follow the OS when the user has not chosen explicitly.
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', (e) => {
                if (!stored()) apply(e.matches ? 'dark' : 'light');
            });
    }

    function icon(theme) {
        // Minimal glyphs: moon for "switch to dark", sun for "switch to light".
        return theme === 'dark'
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M5 5l1.6 1.6M17.4 17.4L19 19M19 5l-1.6 1.6M6.6 17.4L5 19"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5Z"/></svg>';
    }

    function mountToggle() {
        const btn = document.createElement('button');
        btn.className = 'ap-theme-toggle';
        btn.type = 'button';
        btn.setAttribute('aria-label', 'تبديل الوضع الداكن');
        btn.title = 'الوضع الداكن/الفاتح';

        const render = () => {
            const current = document.documentElement.getAttribute('data-ap-theme') || 'light';
            btn.innerHTML = icon(current);
        };
        render();

        btn.addEventListener('click', () => {
            const next = (document.documentElement.getAttribute('data-ap-theme') === 'dark') ? 'light' : 'dark';
            try { localStorage.setItem(KEY, next); } catch {}
            apply(next);
            render();
        });

        const host = document.querySelector('.ap-theme-toggle-mount');
        if (host) host.appendChild(btn);
        else document.body.appendChild(btn);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountToggle);
    } else {
        mountToggle();
    }
})();

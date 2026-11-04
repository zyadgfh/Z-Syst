/* ==========================================================================
   ap-live.js — live "today" strip for the Z-Syst dashboard
   - Polls GET /api/v1/live/summary every 30s (pauses when tab hidden).
   - Updates any element with data-ap-live="<key>":
       sales_count | sales_total | due_total | low_stock | expiring_soon
   - Animates number changes with a quick count-up (skipped under
     prefers-reduced-motion).
   - Reads the bearer token from <meta name="api-token"> if present.
   ========================================================================== */
(function () {
    'use strict';

    const ENDPOINT = '/api/v1/live/summary';
    const INTERVAL = 30000;
    const reduceMotion = window.matchMedia
        && window.matchMedia('(prefers-color-scheme: reduced-motion)').matches;

    function token() {
        const meta = document.querySelector('meta[name="api-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function format(value, el) {
        const suffix = el.getAttribute('data-ap-live-suffix') || '';
        const n = Number(value) || 0;
        return n.toLocaleString('ar-EG', { maximumFractionDigits: 2 }) + suffix;
    }

    function animate(el, target) {
        const start = Number(el.getAttribute('data-ap-live-value') || 0);
        el.setAttribute('data-ap-live-value', target);
        if (reduceMotion || Math.abs(target - start) < 0.01) {
            el.textContent = format(target, el);
            return;
        }
        const dur = 500;
        const t0 = performance.now();
        function step(t) {
            const p = Math.min(1, (t - t0) / dur);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = format(start + (target - start) * eased, el);
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    async function refresh() {
        if (document.hidden) return;
        try {
            const headers = { 'Accept': 'application/json' };
            const tok = token();
            if (tok) headers['Authorization'] = 'Bearer ' + tok;
            const res = await fetch(ENDPOINT, { headers });
            if (!res.ok) return;
            const body = await res.json();
            const data = body.data || body;
            document.querySelectorAll('[data-ap-live]').forEach((el) => {
                const key = el.getAttribute('data-ap-live');
                if (key in data) animate(el, data[key]);
            });
            const dot = document.querySelector('[data-ap-live-dot]');
            if (dot) dot.classList.add('on');
        } catch { /* offline: keep last values */ }
    }

    setInterval(refresh, INTERVAL);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refresh);
    } else {
        refresh();
    }
})();

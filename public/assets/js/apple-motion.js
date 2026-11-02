/* ==========================================================================
 * apple-motion.js — spring/momentum interaction layer (apple-design skill)
 *
 * - Springs: critically damped (damping 1.0), response 0.30–0.40s.
 * - Press: engage on pointerdown, hysteresis ~10px cancels, settle on
 *   pointerup; velocity-aware release.
 * - Momentum projection for release gestures: x' = x + (v/1000)·d/(1−d),
 *   d ≈ 0.998 — used only for scroll-linked parallax decay.
 * - prefers-reduced-motion: everything becomes a crossfade; springs off.
 * ========================================================================== */
(function () {
    'use strict';

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---- tiny spring (semi-implicit Euler, critically damped) ------------- */
    function Spring(response, initial) {
        this.x = initial || 0;
        this.v = 0;
        this.target = initial || 0;
        this.response = response || 0.35;
        this._raf = null;
    }
    Spring.prototype.step = function (dt) {
        var omega = 2 * Math.PI / this.response;
        var x = this.x, v = this.v, t = this.target;
        // critically damped analytic step
        var exp = Math.exp(-omega * dt);
        var xNext = t + (exp * ((x - t) + (v + omega * (x - t)) * dt));
        var vNext = (v * exp) - (omega * exp * (v * dt + omega * (x - t) * dt));
        this.x = xNext; this.v = vNext;
        return Math.abs(xNext - t) < 0.001 && Math.abs(vNext) < 0.01;
    };
    Spring.prototype.set = function (target) {
        this.target = target;
        if (!this._raf && !reduced) this._run();
        else if (reduced) this.x = target;
    };
    Spring.prototype._run = function () {
        var self = this;
        var last = performance.now();
        this._raf = requestAnimationFrame(function frame(now) {
            var dt = Math.min((now - last) / 1000, 1 / 30);
            last = now;
            var settled = self.step(dt);
            if (self.onUpdate) self.onUpdate(self.x);
            if (settled) { self._raf = null; }
            else self._raf = requestAnimationFrame(frame);
        });
    };

    /* ---- press with hysteresis -------------------------------------------- */
    document.addEventListener('pointerdown', function (e) {
        if (reduced) return;
        var el = e.target.closest('.btn, .ap-card, .ap-press');
        if (!el) return;
        var sx = e.clientX, sy = e.clientY;
        var spring = new Spring(0.22, 1);
        spring.onUpdate = function (x) { el.style.transform = 'scale(' + x.toFixed(4) + ')'; };
        spring.set(0.965);
        el._apPress = spring;

        function cancel() {
            if (!el._apPress) return;
            el._apPress.set(1);
            el._apPress = null;
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', done);
            window.removeEventListener('pointercancel', cancel);
        }
        function onMove(ev) {
            if (Math.hypot(ev.clientX - sx, ev.clientY - sy) > 10) cancel(); // hysteresis
        }
        function done() {
            if (el._apPress) { el._apPress.set(1); el._apPress = null; }
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', done);
            window.removeEventListener('pointercancel', cancel);
        }
        window.addEventListener('pointermove', onMove, { passive: true });
        window.addEventListener('pointerup', done, { passive: true });
        window.addEventListener('pointercancel', cancel, { passive: true });
    }, { passive: true });

    /* ---- reveal choreography ---------------------------------------------- */
    function initReveals() {
        var items = document.querySelectorAll('.ap-reveal');
        if (!items.length) return;
        if (reduced || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('ap-in'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('ap-in');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
        items.forEach(function (el, i) {
            if (!el.style.getPropertyValue('--ap-delay')) {
                el.style.setProperty('--ap-delay', Math.min(i * 45, 270) + 'ms');
            }
            io.observe(el);
        });
    }

    /* ---- glass header weight on scroll ------------------------------------ */
    function initHeader() {
        var nav = document.querySelector('.navbar.ap-nav');
        if (!nav) return;
        var lastY = -1, ticking = false;
        function onScroll() {
            var y = window.scrollY;
            if (y === lastY) return;
            lastY = y;
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(function () {
                    nav.classList.toggle('ap-scrolled', lastY > 8);
                    ticking = false;
                });
            }
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ---- subtle pointer-follow tilt on cards (skip when reduced) ---------- */
    function initTilt() {
        if (reduced) return;
        document.querySelectorAll('.ap-card').forEach(function (card) {
            var rx = new Spring(0.4, 0), ry = new Spring(0.4, 0);
            rx.onUpdate = ry.onUpdate = function () {
                card.style.transform =
                    'translateY(' + card._lift + 'px) perspective(900px)' +
                    ' rotateX(' + rx.x.toFixed(3) + 'deg) rotateY(' + ry.x.toFixed(3) + 'deg)';
            };
            card._lift = 0;
            card.addEventListener('pointerenter', function () { card._lift = -4; });
            card.addEventListener('pointerleave', function () {
                card._lift = 0; rx.set(0); ry.set(0);
            });
            card.addEventListener('pointermove', function (e) {
                var r = card.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width - 0.5;
                var py = (e.clientY - r.top) / r.height - 0.5;
                rx.set(py * -3.2); ry.set(px * 3.2);
            }, { passive: true });
        });
    }

    function boot() {
        document.body.classList.add('ap-theme');
        initReveals();
        initHeader();
        initTilt();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

////////////////////////////////////////////////////////////////////////////////////////////////////
// zs-motion.js — GSAP setup for Z-Syst: single registration point, project defaults,
// and the reduced-motion guard. Exposes window.zsMotion. No jQuery dependency.
//
// Requires gsap.min.js to be loaded BEFORE this file (ScrollTrigger.min.js optional).
// Loaded from two places, because resources/views/web/storefront.blade.php is a
// standalone page that does not extend a layout and never sees the shared partial:
//   1. resources/views/layouts/partials/script.blade.php   (admin, auth, web masters)
//   2. resources/views/web/storefront.blade.php            (public storefront)
//
// GSAP is vendored in public/assets/js/vendor/gsap/ because this app has no runtime
// bundler (no @vite in any view) and the Content-Security-Policy in
// app/Http/Middleware/SecurityHeaders.php blocks cdnjs.cloudflare.com — the CDN
// GSAP's own docs recommend. Local files satisfy script-src 'self'.
//
// Authoritative guidance lives in the official GreenSock skills installed in this
// repository — read them before writing animation code:
//   .agents/skills/gsap/SKILL.md            router + this repo's asset/CSP rules
//   .agents/skills/gsap-core/SKILL.md       tweens, easing, stagger, matchMedia()
//   .agents/skills/gsap-scrolltrigger/      scrub, pinning, refresh(), cleanup
//   .agents/skills/gsap-plugins/            Flip, Draggable, SplitText, ...
//
// Do NOT call gsap.registerPlugin() for the plugins registered here anywhere else —
// not in a loop, not in an AJAX callback, not in a re-rendered panel. Opt-in plugins
// (Flip.min.js, ScrollToPlugin.min.js) load after this file, so the page that loads
// them registers its own once, at the top level of that page's script.
//
// This file performs NO animation on its own. Motion is opt-in per page via
// window.zsMotion, so having GSAP installed changes nothing visually.
////////////////////////////////////////////////////////////////////////////////////////////////////

(function () {
    "use strict";

    if (typeof window.gsap === "undefined") {
        return; // page that does not load GSAP — stay silent, do not throw
    }

    var gsap = window.gsap;

    // Register once, here.
    if (typeof window.ScrollTrigger !== "undefined") {
        gsap.registerPlugin(window.ScrollTrigger);
    }

    // Defaults for an admin panel and a keyboard-driven POS: short, calm, and never
    // fighting the user. Cinematic motion belongs on the storefront/landing pages,
    // which may override duration/ease per tween.
    gsap.defaults({ duration: 0.22, ease: "power2.out", overwrite: "auto" });

    // One shared matchMedia context so reduced-motion is honoured everywhere and
    // reverting is a single call. gsap.matchMedia() reverts animations it created
    // automatically when a query stops matching.
    var mm = gsap.matchMedia();
    var reduceQuery = "(prefers-reduced-motion: reduce)";
    var reducedNow =
        window.matchMedia && window.matchMedia(reduceQuery).matches === true;

    /**
     * window.zsMotion — the only supported way to start motion from a page script.
     */
    window.zsMotion = {
        gsap: gsap,
        matchMedia: mm,

        /** True when the user asked the OS to reduce motion. Skip motion then. */
        reduced: function () {
            if (typeof window.matchMedia !== "function") return reducedNow;
            return window.matchMedia(reduceQuery).matches === true;
        },

        /**
         * Run `setup` only for users who did not opt out of motion. Returns the
         * matchMedia context so callers can revert it. `setup` may return a cleanup
         * function; matchMedia will call it when the query stops matching.
         *
         *   zsMotion.whenMotion(function () {
         *       return gsap.from(".product-card", { y: 12, autoAlpha: 0, stagger: 0.04 });
         *   });
         */
        whenMotion: function (setup) {
            if (typeof setup !== "function") return mm;
            var conditions = {};
            conditions.motion = "(prefers-reduced-motion: no-preference)";
            mm.add(conditions, function (context) {
                if (!context.conditions.motion) return;
                return setup(context);
            });
            return mm;
        },

        /**
         * Safe entrance: fade+rise a set of elements. No-op under reduced motion, and
         * a no-op when the target is missing (GSAP would only warn and animate nothing).
         */
        enter: function (targets, vars) {
            var nodes = gsap.utils.toArray(targets || []);
            if (!nodes.length || this.reduced()) return null;
            var opts = vars || {};
            return gsap.from(nodes, {
                y: typeof opts.y === "number" ? opts.y : 12,
                autoAlpha: 0,
                duration: typeof opts.duration === "number" ? opts.duration : 0.22,
                ease: opts.ease || "power2.out",
                stagger: typeof opts.stagger === "number" ? opts.stagger : 0.04,
                delay: opts.delay || 0,
                clearProps: "transform,visibility",
                onComplete: opts.onComplete
            });
        },

        /**
         * Call after injecting DOM that changes layout (AJAX-loaded report panels,
         * modals, tab switches) or ScrollTrigger keeps stale measurements.
         */
        refresh: function () {
            if (typeof window.ScrollTrigger !== "undefined") {
                window.ScrollTrigger.refresh();
            }
        },

        /**
         * Kill everything created inside `scope` (an element/selector), or every
         * ScrollTrigger when called with no argument. Use before re-initialising a
         * re-rendered panel — POS rows and report tables are replaced in place, so a
         * naive re-init stacks duplicate tweens.
         */
        kill: function (scope) {
            if (typeof window.ScrollTrigger !== "undefined") {
                var triggers = window.ScrollTrigger.getAll();
                for (var i = 0; i < triggers.length; i++) {
                    if (!scope || (triggers[i].trigger && gsap.utils.toArray(scope).indexOf(triggers[i].trigger) !== -1)) {
                        triggers[i].kill();
                    }
                }
            }
            if (scope) {
                gsap.killTweensOf(gsap.utils.toArray(scope));
            }
        },

        /** Revert the shared matchMedia context (page teardown). */
        revert: function () {
            mm.revert();
        }
    };

    // Keep `reducedNow` honest if the user changes the OS setting mid-session, and
    // let matchMedia re-run its handlers.
    if (typeof window.matchMedia === "function") {
        var mq = window.matchMedia(reduceQuery);
        var onChange = function () {
            reducedNow = mq.matches === true;
            if (typeof gsap.matchMediaRefresh === "function") {
                gsap.matchMediaRefresh();
            }
        };
        if (typeof mq.addEventListener === "function") mq.addEventListener("change", onChange);
        else if (typeof mq.addListener === "function") mq.addListener(onChange); // Safari < 14
    }
})();


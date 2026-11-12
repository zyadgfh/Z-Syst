# GSAP 3.15.1 — vendored UMD builds

Local copies of the official GSAP files. **Do not edit them.** See `LICENSE.txt`
for version, source and license (GSAP is free for commercial use, but it is *not*
MIT).

| File | Loaded on every page? | What it is for |
|---|---|---|
| `gsap.min.js` (73 KB) | ✅ yes — `layouts/partials/script.blade.php` | Core tweens, easing, stagger, timelines, `gsap.matchMedia()`. Includes CSSPlugin. |
| `ScrollTrigger.min.js` (45 KB) | ✅ yes — same partial | Scroll-linked animation, parallax, pinning, `scrub`. |
| `Flip.min.js` (26 KB) | ⛔ opt-in | Animate between two layout states — POS grid↔list, filtered/reordered product grids, expanding cards. |
| `ScrollToPlugin.min.js` (4 KB) | ⛔ opt-in | Smooth scroll to an anchor/offset — report sections, "back to top". |

For scale: the same shared partial already ships **533 KB** of ApexCharts, so the
118 KB of core + ScrollTrigger is a proportionate addition rather than the
heaviest thing on the page.

## Why vendored instead of `npm install`

This Blade app has **no bundler at runtime**: there is no `@vite` directive in any
view and `resources/js/app.js` is never loaded by the layouts. Pages pull local
files with `asset()`. An npm install would therefore add a dependency that no
browser ever downloads. The files are also *not* taken from a CDN because this
project's CSP blocks `cdnjs.cloudflare.com` — the CDN GSAP's own docs recommend.

## Registration happens once, in `zs-motion.js`

`public/assets/js/zs-motion.js` is the single registration point: it registers
ScrollTrigger, sets project defaults, guards `prefers-reduced-motion`, and exposes
`window.zsMotion`. It has no jQuery dependency and is loaded from two places,
because `resources/views/web/storefront.blade.php` is a standalone page that does
not extend a layout:

1. `resources/views/layouts/partials/script.blade.php` — reaches all five layouts
   (`master`, `blank`, `auth/app`, `web/master`, `web/blank`)
2. `resources/views/web/storefront.blade.php` — the public storefront

Load order matters: `gsap.min.js` → `ScrollTrigger.min.js` → `zs-motion.js` →
`custom.js` → `@stack('js')`. **Do not call `gsap.registerPlugin()` again for
these two, and never inside a loop, an AJAX callback, or a re-rendered panel.**

Pages that get no GSAP at all — deliberately: receipts, `purchase-orders/pdf`,
barcode/label sheets, mail templates, the installer, and `layouts/error-blank`.
Print and PDF output has nothing to animate.

### `window.zsMotion`

| Member | Purpose |
|---|---|
| `gsap`, `matchMedia` | the shared instances |
| `reduced()` | `true` when the user asked the OS to reduce motion |
| `whenMotion(setup)` | run `setup` only for `prefers-reduced-motion: no-preference`; reverting is automatic |
| `enter(targets, vars?)` | safe fade+rise entrance; **no-op** under reduced motion and when no target matches |
| `refresh()` | `ScrollTrigger.refresh()` after AJAX-injected DOM |
| `kill(scope?)` | kill tweens/ScrollTriggers before re-initialising a re-rendered panel |
| `revert()` | revert the shared matchMedia context on teardown |

Opt-in plugins (`Flip`, `ScrollToPlugin`) load *after* `zs-motion.js`, so the
page that needs them registers its own, once:

```blade
{{-- in the page view --}}
@push('js')
    <script src="{{ asset('assets/js/vendor/gsap/Flip.min.js') }}"></script>
    <script>
        gsap.registerPlugin(Flip);   // once, top level of this page script (not zs-motion.js)
        const state = Flip.getState('.product-card');
        // …change the DOM (filter / reorder / toggle a class)…
        Flip.from(state, { duration: 0.4, ease: 'power2.inOut', stagger: 0.01 });
    </script>
@endpush
```

`@stack('js')` sits after `custom.js` in `layouts/partials/script.blade.php`, so
`gsap` and `window.zsMotion` are guaranteed to exist by then.

## Rules for anything you write with it

Authoritative guidance is in the official skills — read them before writing
animation code:

* `.agents/skills/gsap/SKILL.md` — router + this repo's asset/CSP rules
* `.agents/skills/gsap-core/SKILL.md` — tweens, easing, stagger, `matchMedia()`
* `.agents/skills/gsap-scrolltrigger/SKILL.md` — `scrub`, pinning, `refresh()`, cleanup
* `.agents/skills/gsap-plugins/SKILL.md` — Flip, Draggable, SplitText, …

Non-negotiables, restated for this product:

1. Animate `transform`/`opacity` (`x`, `y`, `scale`, `rotation`, `autoAlpha`) —
   never `width`/`height`/`top`/`left`.
2. Kill what you create. POS rows and report tables are re-rendered in place, so
   re-initialising without `kill()` stacks duplicate tweens.
3. `ScrollTrigger.refresh()` after any AJAX-injected DOM that changes layout.
4. Honour `prefers-reduced-motion`. Staff stare at this screen all day.
5. Admin/POS motion stays functional: **150–250 ms, `power2.out`, no
   scroll-jacking, nothing that can delay a sale.** Save the cinematic work for
   `resources/views/web/storefront.blade.php` and the landing pages.
6. Guard missing targets — GSAP only warns and silently animates nothing.

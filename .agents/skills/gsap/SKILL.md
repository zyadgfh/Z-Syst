---
name: gsap
description: "GSAP animation entry point and router for this repository. Use for any web animation work: tweens, easing, stagger, timelines/sequencing, ScrollTrigger (parallax, pinning, scrub), plugins (Flip, Draggable, SplitText, ScrollSmoother, MorphSVG, DrawSVG, MotionPath, Observer, CustomEase), gsap.utils helpers, performance/60fps tuning, reduced-motion and responsive animation. Also use when asked to add, load, or wire up GSAP in this project's Laravel Blade views, since it carries the repo-specific asset and CSP rules. Routes to the eight official GreenSock skills installed alongside it."
argument-hint: "[what to animate, or 'install' / 'audit' / 'which skill']"
license: MIT
metadata:
  author: GreenSock (greensock/gsap-skills) + Z-Syst integration notes
  version: "aed9cfd32777"
  upstream: "https://github.com/greensock/gsap-skills"
---

# GSAP

Entry point for all animation work in this repository. The authoritative content
lives in the eight official GreenSock skills installed beside this file — **read
the relevant one before writing animation code**; do not answer from memory.

> GSAP and **every** plugin are free for commercial use since Webflow's
> acquisition. Club GSAP is no longer a paid tier. Everything installs from the
> public `gsap` npm package — no auth token, `.npmrc`, or private registry.
> (`references/llms.txt`)

## Route to the right skill

| The user wants… | Read |
|---|---|
| A tween, easing, stagger, `autoAlpha`, transforms, responsive or reduced-motion | `.agents/skills/gsap-core/SKILL.md` |
| Sequencing, choreography, keyframes, labels, playback control, nesting | `.agents/skills/gsap-timeline/SKILL.md` |
| Scroll-driven animation, parallax, pinned sections, `scrub`, refresh/cleanup | `.agents/skills/gsap-scrolltrigger/SKILL.md` |
| A plugin: Flip, Draggable, SplitText, ScrambleText, ScrollSmoother, ScrollTo, Observer, Inertia, MorphSVG, DrawSVG, MotionPath, CustomEase, EasePack, GSDevTools | `.agents/skills/gsap-plugins/SKILL.md` |
| `gsap.utils`: clamp, mapRange, interpolate, snap, toArray, wrap, random, pipe | `.agents/skills/gsap-utils/SKILL.md` |
| Jank, FPS, `will-change`, layout thrashing, batching | `.agents/skills/gsap-performance/SKILL.md` |
| React / Next.js (`useGSAP`, refs, `gsap.context()`, SSR) | `.agents/skills/gsap-react/SKILL.md` |
| Vue, Nuxt, Svelte (lifecycle, scoping, cleanup) | `.agents/skills/gsap-frameworks/SKILL.md` |

Reference material: `references/llms.txt` (upstream discovery index),
`references/examples/{vanilla,react,vue,nuxt}/` (runnable upstream examples).

**Not applicable to this repo's Blade app:** `gsap-react` and `gsap-frameworks` —
there is no React or Vue here (see below). Keep them for reference only.

## Non-negotiable GSAP rules

1. **Register each plugin exactly once**, at the entry point — never inside a
   loop, a component, or a per-page script: `gsap.registerPlugin(ScrollTrigger)`.
2. **Clean up.** Every tween/ScrollTrigger created for a view must be killed when
   that view goes away, or it leaks and double-fires on navigation.
3. **Animate `transform` and `opacity` only** for anything that must stay smooth.
   Animating `width`/`height`/`top`/`left` triggers layout on every frame.
4. **Honour `prefers-reduced-motion`** via `gsap.matchMedia()`. This product is a
   pharmacy POS that staff stare at all day; un-dismissable motion is a defect.
5. **Server-rendered HTML exists before your script runs.** Guard for missing
   targets — GSAP warns "target not found" and silently animates nothing.

## Wiring GSAP into THIS project

The Blade app **does not use Vite or any bundler at runtime**: there is no `@vite`
directive in any view, and `resources/js/app.js` is not loaded by the layouts.
Pages pull local files with `asset()` from `public/assets/js/`, listed in
`resources/views/layouts/partials/script.blade.php` (jQuery 3.7.1, Bootstrap
bundle, ApexCharts, `custom-ajax.js`, `pos-keys.js`, `pos-offline.js`, …).

So install GSAP the same way — **not** with `npm install` + `@vite`:

1. Put the UMD builds under version control next to the other vendored assets:
   `public/assets/js/vendor/gsap/gsap.min.js`, plus any plugin
   (`ScrollTrigger.min.js`, `Flip.min.js`, …).
2. Add the tag in `resources/views/layouts/partials/script.blade.php`, **before**
   `custom.js` so page scripts can rely on `gsap` existing:
   ```blade
   <script src="{{ asset('assets/js/vendor/gsap/gsap.min.js') }}"></script>
   <script src="{{ asset('assets/js/vendor/gsap/ScrollTrigger.min.js') }}"></script>
   ```
3. Register plugins once, in `public/assets/js/custom/custom.js`.

### CSP — this will silently break if you ignore it

`app/Http/Middleware/SecurityHeaders.php` sends:

```
script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://js.hcaptcha.com
```

- ✅ Local `asset('assets/js/vendor/gsap/…')` → `'self'`. **Preferred.**
- ✅ `https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js` → already allowlisted.
  This is how the storefront loads Alpine
  (`resources/views/web/storefront.blade.php`).
- ❌ **`https://cdnjs.cloudflare.com/ajax/libs/gsap/…` — GSAP's own documented CDN
  — is BLOCKED by this policy.** The script fails to load with no server-side
  error, and the animation just never runs. If a CDN is truly wanted, add the host
  to `script-src` in the middleware *and* update the matching expectation in
  `tests/Feature/SecurityHeadersTest.php` (that test asserts the exact string).

### Alpine and jQuery coexist here

- Alpine 3 is loaded **only** on the public storefront, from jsdelivr. Where both
  are present, start GSAP from Alpine's lifecycle so cleanup is automatic:
  `x-data` → `init() { this.tl = gsap.timeline(); }` and
  `destroy() { this.tl?.kill(); ScrollTrigger.getAll().forEach(t => t.kill()); }`.
- The admin panel is jQuery. Initialise on `DOMContentLoaded` (or after the AJAX
  swap in `custom-ajax.js`), and **kill before re-creating** — POS rows and report
  tables are re-rendered in place, so a naive re-init stacks duplicate tweens.
- ScrollTrigger needs `ScrollTrigger.refresh()` after any DOM that changes layout
  is injected (AJAX-loaded report panels, modals, tab switches).

### Where animation belongs in this product

- **Storefront / marketing** (`resources/views/web/storefront.blade.php`, the
  Landing module, `banner-design` and `ui-ux-pro-max` skills): full freedom —
  ScrollTrigger, SplitText, Flip.
- **Admin + POS** (`resources/views/admin/pos/**`, dashboards, reports): keep it
  functional and fast. 150–250 ms, `power2.out`, no scroll-jacking, never block a
  sale. The POS is keyboard-driven (`pos-keys.js`) — animation must not steal
  focus or delay input.
- **Receipts and PDFs** (`resources/views/receipts/**`, the Flutter `due_pdf`):
  print/PDF output — animation is pointless here, skip it.

## `/gsap` subcommands

`/gsap install` — vendor the UMD builds into `public/assets/js/vendor/gsap/`,
add the script tags, register plugins once, and confirm the CSP allows them.

`/gsap audit` — scan every GSAP usage for: plugins registered more than once,
tweens/ScrollTriggers created without a matching `kill()`, animated layout
properties, missing `prefers-reduced-motion` handling, and any script host the CSP
blocks. Report file:line and fix.

`/gsap which skill <task>` — print the routing row for a task without doing work.

Anything else is treated as an animation request: route to the skill above, read
it, then implement.

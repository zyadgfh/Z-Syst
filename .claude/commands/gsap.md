---
description: "GSAP animation — route to the official GreenSock skills and this repo's asset/CSP rules. Subcommands: install, audit, which skill <task>."
argument-hint: "[install | audit | which skill <task> | what to animate]"
---

Use the **`gsap`** skill: `.agents/skills/gsap/SKILL.md`.

Request: $ARGUMENTS

## How to handle it

1. **Read `.agents/skills/gsap/SKILL.md` first.** It is the router — it tells you
   which of the eight official GreenSock skills applies, and it carries this
   repository's non-obvious constraints (no Vite at runtime, local
   `public/assets/js/` assets, and a Content-Security-Policy that blocks GSAP's
   own documented `cdnjs.cloudflare.com` CDN).

2. **Then read the routed skill in full** — `.agents/skills/gsap-core/`,
   `gsap-timeline/`, `gsap-scrolltrigger/`, `gsap-plugins/`, `gsap-utils/`,
   `gsap-performance/`, `gsap-react/`, or `gsap-frameworks/`. Do not write GSAP
   code from memory; the official skills exist precisely because the API is easy
   to get subtly wrong (plugin registration, cleanup, `matchMedia`, position
   parameter).

3. **Handle the subcommands:**
   - `install` — vendor the UMD builds into `public/assets/js/vendor/gsap/`, add
     the `<script>` tags to `resources/views/layouts/partials/script.blade.php`
     *before* `custom.js`, register plugins once in
     `public/assets/js/custom/custom.js`, and verify every host you used is
     allowed by `script-src` in `app/Http/Middleware/SecurityHeaders.php`.
   - `audit` — check all GSAP usage for plugins registered more than once,
     tweens/ScrollTriggers with no matching `kill()`, animated layout properties
     (`width`/`height`/`top`/`left` instead of `transform`/`opacity`), missing
     `prefers-reduced-motion` handling, missing `ScrollTrigger.refresh()` after
     AJAX-injected DOM, and any blocked script host. Report `file:line` and fix.
   - `which skill <task>` — print only the matching routing row from the skill's
     table; do no work.

4. **No arguments, or a plain animation request** — route, read, then implement.
   Respect the product context the skill defines: full freedom on the storefront
   and landing pages; 150–250 ms, `power2.out`, no scroll-jacking and nothing
   that can delay a sale in the admin/POS; no animation in receipt or PDF views.

5. If you touch `script-src`, also update the exact expected string in
   `tests/Feature/SecurityHeadersTest.php` — that assertion compares the whole
   header verbatim and will fail otherwise.

If no arguments were given, ask what should be animated and where (storefront,
admin dashboard, POS, or landing page), or offer to run `install` / `audit`.

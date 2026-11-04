# Z-Syst site — Design & implementation plan

## Context
Z-Syst is a pharmacy operations platform for multi-branch teams. The existing repository is primarily Laravel, but the Vercel project is configured as a Vite deployment from the repository root. The last production deployment failed because no `dist` output directory was generated.

## Design direction
- **Design movement:** quiet medical precision with a warm editorial accent.
- **Core principles:** clear hierarchy, calm confidence, progressive disclosure, accessible contrast.
- **Color philosophy:** deep ink for trust and focus, soft mineral backgrounds for breathing room, mint for operational success, coral only for the single primary action.
- **Layout paradigm:** a split hero with an asymmetric product preview, followed by a horizontally paced capability story rather than a dense card grid.
- **Signature elements:** numbered capability rail, “live operations” product panel, thin mint activity line, rounded 20px surfaces with restrained shadows.
- **Interaction philosophy:** direct navigation, visible feedback, no hover-only meaning, and a compact mobile menu with keyboard-accessible controls.
- **Animation:** subtle entrance and hover transitions only; respect `prefers-reduced-motion`.
- **Typography:** system UI stack with strong display scale, 17px+ body copy, and Arabic-safe fallbacks.
- **Brand essence:** one operating rhythm for every pharmacy; precise, calm, dependable.
- **Brand voice:** concise and practical. Example lines: “كل ما يحتاجه فريقك، في إيقاع واحد.” and “ابدأ بجولة قصيرة داخل Z-Syst.”
- **Signature brand color:** `#35C49A` mint, used for positive status and the product mark.

## Implementation
- Add a static Vite landing entry at the repository root so Vercel can build it into `dist`.
- Keep Laravel assets and module code intact; add a separate `vite.config.vercel.js` and a `vercel.json` deployment contract.
- Use existing logo assets from `public/assets/images/logo/` and no remote image dependency.
- Include bilingual Arabic/English copy toggle, responsive navigation, accessible focus states, FAQ disclosure, and reduced-motion support.
- Expose `/manus-routes.json` with the public routes required by the web runtime.

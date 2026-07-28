# react-security-reviewer

Read this when a codebase contains React, Next.js, Remix, Vite SPA, React Router, JSX, TSX, or browser-side authentication flows.

## Goal

Find client-side vulnerabilities and React-specific application flaws that generic JavaScript taint review often misses. Treat React code as security-relevant when it handles tokens, routing decisions, user-generated content, feature flags, authorization UI, postMessage events, or server/client boundaries.

## High-risk React sinks

Verify source, sink, and mitigation for each:

- DOM XSS: `dangerouslySetInnerHTML`, `innerHTML`, `outerHTML`, `insertAdjacentHTML`, markdown renderers, rich-text renderers, `ref.current` DOM writes.
- Open redirect: `navigate()`, `router.push()`, `router.replace()`, `window.location`, `location.assign()` fed by `URLSearchParams`, `location.search`, OAuth `next`, `returnUrl`, or `redirect_uri`.
- Token exposure: JWTs, refresh tokens, API keys, or session secrets in `localStorage`, `sessionStorage`, Redux, Zustand, query strings, error telemetry, or browser logs.
- postMessage abuse: `message` listener using `event.data` without exact `event.origin` and schema validation.
- Client-only authorization: hiding buttons/routes without server-side authorization on the API call.
- CSP bypass: inline scripts, JSON-in-script hydration blobs, `unsafe-inline`, or unescaped user data in serialized props.

## Next.js and server/client boundary checks

- Server Actions / Route Handlers: authenticate and authorize inside the server function, not just in the UI component.
- SSR/SSG data loaders: verify tenant/user scoping before returning props. Cached server responses must not mix users or tenants.
- Middleware: confirm matcher coverage for nested routes, API routes, locale prefixes, and legacy pages.
- Hydration data: secrets, tokens, internal IDs, feature flags, and PII must not be serialized into `__NEXT_DATA__` or client props.

## Next.js BFF protocol downgrade

When a BFF/API route handler constructs URLs for the browser (enrollment setup URLs, download links, setup commands) using the scheme or port of an **internal upstream** rather than the browser-visible origin:

```typescript
// Flag: internal upstream is http://api:8080; browser accesses via https://app.example.com
function publicControlPlaneAddress(req: Request, upstreamURL: string): string {
  const upstream = new URL(upstreamURL)
  // BUG: overwrites the browser-visible host's scheme+port with internal upstream scheme+port
  return `${upstream.protocol}//${req.headers.get('host')}:${upstream.port}`
  // Returns: http://app.example.com:8080 — cleartext, wrong port
}

// Correct: use browser-visible origin; only fall back to upstream for explicitly public configs
const publicOrigin = process.env.PUBLIC_API_BASE_URL ?? `https://${req.headers.get('host')}`
```

Generated setup commands, download URLs, and enrollment tokens embedded in these URLs are exposed over cleartext when the derived URL downgrades to HTTP. Flag any BFF function that blends the internal upstream scheme/port with the browser-visible host.

## "use client" RSC payload data leakage

In Next.js App Router, adding `"use client"` to a component that a server component passes wide data into causes **all props** to be serialized into the RSC payload and sent to the browser — including fields that are not rendered in the UI.

```tsx
// Flag: wide server component converted to client component
// Before: FleetSurface was a server component — non-rendered fields stayed server-side
// After "use client":
"use client"
export function FleetSurface({ agents, assets, environments, rollouts, packageManifests, ... }) {
  // Only 'agents' is rendered, but ALL props are now in the RSC payload / browser JS bundle
  return <AgentTable agents={agents} />
  // assets, environments, rollouts, packageManifests, etc. — full objects in browser
}
```

When reviewing a server component converted to a client component, check what props flow in from the server-component parent. If the parent fetches broad datasets and passes all of them as props, non-rendered fields are now browser-readable even if the UI doesn't display them. Mitigation: keep the data-fetching/wide-summary layer server-only; pass only a minimal sanitized view model into the client component.

## GET-triggered server-side mutations (CSRF via navigation)

When a Next.js server component or server action reads URL query params (`searchParams`) and immediately performs a **state-changing** backend call (POST, audit write, resource creation) during render:

```tsx
// Flag: visiting this URL (top-level GET) causes an authenticated POST to /api/v1/graphs/query
export default async function ReportsPage({ searchParams }) {
  const view = searchParams.view  // attacker-controlled via URL
  // runGraphQuery sends an authenticated POST during server render — triggered by GET navigation
  const result = await runGraphQuery({ view, maxNodes: searchParams.max_nodes })
  // Victim navigates to /<tenant>/reports?view=asset_exposure — their session triggers the POST
}
```

With `SameSite=Lax` cookies, top-level GET navigations carry the victim's session cookie. If the server component uses that session to perform a mutation (audit event, query record, download record, expensive traversal), an attacker can cause those side effects by linking/navigating the victim to a crafted URL. Flag any server component that both reads `searchParams` and calls a mutating backend endpoint.

## BFF hardcoded security assertions

Flag any BFF/API route that injects a hardcoded constant for a security-relevant field before forwarding to the backend:

```typescript
// Flag: BFF substitutes a constant MFA assertion — bypasses backend non-empty check
const body = {
  reauth_proof: params.reauthProof,
  mfa_assertion: 'mfa_scaffold_assertion',  // hardcoded — any user passes backend gate
}
await fetch('/api/v1/delegated-sessions/start', { body: JSON.stringify(body) })

// Correct: require and forward the real client-supplied assertion
const { reauthProof, mfaAssertion } = await req.json()
if (!mfaAssertion) return Response.json({ error: 'mfa_assertion required' }, { status: 400 })
```

This pattern is distinct from missing input validation — the UI intentionally hides the MFA field and substitutes a constant, making the bypass invisible to the backend.

## Clipboard pastejacking via onCopy override

When a component attaches an `onCopy` handler to an element containing sensitive material (enrollment commands, tokens, credentials) that replaces the user's selected text with the **full** secret-bearing content:

```tsx
// Flag: user selects one line; clipboard gets entire multi-line command including tokens
<pre onCopy={(e) => {
  e.preventDefault()
  e.clipboardData.setData('text/plain', fullEnrollmentCommand)  // always the full secret command
}}>
  {enrollmentCommand}
</pre>
```

This removes the user's ability to safely copy a partial, non-sensitive fragment (e.g., just the server URL). An operator copying a hostname for a ticket or chat gets the full enrollment token in their clipboard, risking accidental disclosure. Flag `onCopy` handlers that always substitute the full content regardless of what the user selected.

## UI data rendering — redaction bypass

When a UI component renders sensitive operational data (task command lines, test parameters, evidence details) and the API returns both a raw field and a redacted field:

```tsx
// Flag: uses raw command before checking the redacted variant
const commandPreview =
  latestDetectionRun?.command ??        // raw — may be unredacted
  task.parameters.command               // fallback also raw
// Should be:
//   latestDetectionRun?.command_redacted ?? task.parameters.command_preview
```

Flag components that access `*.command`, `*.raw_*`, or `*.full_*` fields before checking `*.redacted` or `*.preview` equivalents, especially in views accessible to roles that should not see raw command lines.

## Hidden form field counts driving unbounded server-action loops (DoS)

When a Next.js server action reads loop-bound values such as `variant_count`, `platform_count`, or `cleanup_count` from hidden `<input type="hidden">` form fields and passes them — without an upper-bound check — directly into `for` loops that process form data before any authorization check is reached:

```tsx
// Flag: hidden form fields control loop bounds — no upper bound enforced
function readOptionalNumber(formData: FormData, key: string): number {
  const val = formData.get(key)
  return val ? parseInt(String(val), 10) : 0  // accepts any finite integer
}

async function saveTtpDetailDraft(formData: FormData) {
  'use server'
  const variantCount = readOptionalNumber(formData, 'variant_count')   // client-controlled
  const platformCount = readOptionalNumber(formData, 'platform_count') // client-controlled
  for (let i = 0; i < variantCount; i++) {  // unbounded loop before auth check
    await readTtpDetailVariantBasics(formData, i)
  }
  // Authorization check (createActionVersion) only reached after loop completes
}
```

```tsx
// Correct: enforce a maximum before the loop
const MAX_VARIANTS = 50
const variantCount = Math.min(readOptionalNumber(formData, 'variant_count'), MAX_VARIANTS)
```

An authenticated user who can render the page can forge a server action request with an arbitrarily large count (e.g., `variant_count=1000000`) and tie up the Next.js server event loop processing the loop. Because server actions are invoked over HTTP, no browser-side enforcement applies. The work is proportional to the attacker-controlled count and completes before any upstream API authorization can reject the request, making this a denial-of-service independent of normal RBAC controls. CWE-400 (Uncontrolled Resource Consumption). Severity: medium.

Flag when all three conditions are present:

- `readOptionalNumber`, `parseInt`, `Number()`, or `parseFloat` applied to `formData.get(...)` without a subsequent `Math.min` or explicit bounds check.
- The resulting number used as the upper bound of a `for` or `while` loop inside a `'use server'` function.
- The authorization check (API call, session verification, policy function) occurring **after** the loop completes rather than before it.

## Evidence requirements

Report only when you can show:

1. User-controlled source: URL params, props from untrusted API, CMS content, postMessage, local storage, form state, uploaded file content.
2. Dangerous sink: exact React/browser API call.
3. Missing mitigation: no sanitizer, allowlist, origin check, output encoding, server-side auth, or same-origin redirect enforcement.

## False-positive filters

- `DOMPurify.sanitize`, strict markdown allowlists, and trusted static literals usually downgrade XSS.
- Relative-only redirect allowlists (`/dashboard`, `/settings`) usually downgrade open redirect.
- Access tokens in httpOnly, Secure, SameSite cookies are not browser-readable token storage.
- UI route guards are not sufficient by themselves; verify the backing API before dismissing auth findings.

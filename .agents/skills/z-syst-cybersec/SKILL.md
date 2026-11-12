---
name: z-syst-cybersec
description: Router for the curated Z-Syst cybersecurity skill pack installed from mukul975/Anthropic-Cybersecurity-Skills. Use when planning or running a security audit, threat model, hardening review, or compliance check (HIPAA/GDPR/PCI) on this repository — Laravel multi-tenant pharmacy POS/ERP, Blade/jQuery/Alpine web UI, Flutter mobile app, Supabase PostgreSQL, Vercel deployment, GitHub Actions CI. Maps Z-Syst components to the installed playbooks and to the in-repo code-vulnscan skill.
metadata:
  source: mukul975/Anthropic-Cybersecurity-Skills
  source-commit: 54a798831d2266a3ca61ce68a7acb80b81160d57
  license: Apache-2.0
  installed: 2026-11-12
---

# Z-Syst Cybersecurity Skill Router

Curated pack of 62 skills from `mukul975/Anthropic-Cybersecurity-Skills` (Apache-2.0),
selected for this repository's actual architecture. Full provenance: see `PROVENANCE.md`
next to this file. The full upstream library (818 skills) is intentionally **not** installed —
OT/ICS, Windows forensics, malware RE, K8s, and cloud-IAM skills do not apply here.

## When to activate

- Security audit / penetration-test review of the Laravel app, API, or Flutter app
- Tenant-isolation (multi-tenant `business_id`) verification beyond `TenantScoped`
- Supabase RLS / auth posture review (use the `supabase` connector, read-only queries)
- Vercel deployment & environment hygiene review (use the `vercel` connector, names only)
- CI/CD supply-chain hardening (GitHub Actions, dependency scanning, secret scanning)
- HIPAA / GDPR / PCI control gap analysis for the pharmacy POS domain
- Threat modeling a new feature before implementation

## Component → playbook map

| Z-Syst component | Primary installed skills |
|---|---|
| Laravel web routes / controllers (Blade) | `performing-web-application-penetration-test`, `testing-for-broken-access-control`, `bypassing-authentication-with-forced-browsing`, `performing-csrf-attack-simulation`, `testing-cors-misconfiguration`, `performing-security-headers-audit`, `performing-content-security-policy-bypass`, `testing-for-open-redirect-vulnerabilities`, `performing-directory-traversal-testing` |
| Eloquent / raw SQL (tenant scoping) | `performing-second-order-sql-injection`, `exploiting-sql-injection-with-sqlmap`, `exploiting-idor-vulnerabilities`, `exploiting-race-condition-vulnerabilities` |
| JSON API used by Flutter + web | `testing-api-security-with-owasp-top-10`, `testing-api-for-broken-object-level-authorization`, `detecting-broken-object-property-level-authorization`, `exploiting-broken-function-level-authorization`, `exploiting-mass-assignment-in-rest-apis`, `exploiting-api-injection-vulnerabilities`, `exploiting-excessive-data-exposure-in-api`, `testing-api-authentication-weaknesses`, `performing-api-inventory-and-discovery`, `implementing-api-rate-limiting-and-throttling` |
| Session / JWT / password auth (GoTrue-backed) | `testing-jwt-token-security`, `exploiting-jwt-algorithm-confusion-attack`, `performing-jwt-none-algorithm-attack`, `configuring-oauth2-authorization-flow`, `performing-oauth-scope-minimization-review`, `performing-access-review-and-certification`, `performing-service-account-audit` |
| Input handling / rendering (jQuery, Alpine, Blade `!!`) | `testing-for-xss-vulnerabilities`, `exploiting-insecure-deserialization`, `testing-for-sensitive-data-exposure`, `testing-for-business-logic-vulnerabilities` |
| Flutter app (`pharmacy-store-app-codecanyon-main/`) | `conducting-mobile-app-penetration-test`, `exploiting-insecure-data-storage-in-mobile`, `exploiting-deeplink-vulnerabilities`, `testing-mobile-api-authentication`, `performing-android-app-static-analysis-with-mobsf` |
| Supabase (PostgreSQL + RLS + GoTrue + Storage) | `exploiting-idor-vulnerabilities`, `testing-api-for-broken-object-level-authorization`, `performing-cryptographic-audit-of-application`, `implementing-jwt-signing-and-verification` + repo skills `supabase`, `supabase-postgres-best-practices` |
| Vercel config (`vercel.json`) / headers | `performing-security-headers-audit`, `testing-cors-misconfiguration`, `implementing-infrastructure-as-code-security-scanning` |
| GitHub Actions (`.github/workflows/`) | `securing-github-actions-workflows`, `implementing-secret-scanning-with-gitleaks`, `integrating-sast-into-github-actions-pipeline`, `implementing-semgrep-for-custom-sast-rules` |
| Docker / container images | `hardening-docker-containers-for-production`, `scanning-docker-images-with-trivy` |
| Dependencies (composer, npm, pub) | `detecting-malicious-npm-packages`, `detecting-typosquatting-packages-in-npm-pypi`, `generating-and-analyzing-sboms`, `verifying-build-provenance-with-slsa-sigstore` |
| Audit output triage | `performing-web-application-vulnerability-triage`, `prioritizing-vulnerabilities-with-cvss-scoring`, `performing-cve-prioritization-with-kev-catalog` |
| Compliance (pharmacy = health data, card payments) | `implementing-hipaa-security-rule-safeguards`, `implementing-pci-dss-compliance-controls`, `conducting-gdpr-compliance-assessment`, `implementing-gdpr-data-protection-controls`, `performing-privacy-impact-assessment` |
| Pre-implementation threat model | `performing-threat-modeling-with-owasp-threat-dragon` |

## Standard audit workflow for this repo

1. **Strategy** — read `PROJECT_RULES/04_SECURITY_RULES.md` and prior reports
   (`docs/zsyst/21-security-scan-2026-11-06.md`, `23-deep-scan-2026-11-07.md`,
   `docs/security-production-audit-2026-09-22.md`) so findings build on, and do not
   regress, the PR #24 / #26 remediation baseline (398/398 PHPUnit green).
2. **Deterministic scan** — run the in-repo `code-vulnscan` skill
   (`.github/skills/code-vulnscan/scripts/scan.py`, `secrets.py`, `dependency.py`)
   and the `.bug-hunter` checks. Do not duplicate what they already cover.
3. **Playbook review** — apply the mapped skills above manually against the code,
   with the golden rule from `code-vulnscan`: a finding needs source → sink → path,
   verified by reading the actual code. Pattern matches are candidates, not findings.
4. **Live posture (read-only)** — Supabase connector: `get_auth_config`,
   `list_secrets` (names only), `execute_query` with SELECT-only RLS/policy audits.
   Vercel connector: `list_env_vars` (names only), `list_domains`, `get_deployment`.
   Never mutate live resources during an audit.
5. **Report** — write to `docs/zsyst/NN-security-audit-YYYY-MM-DD.md` following the
   §26 style used by existing reports: observed/inferred/unverified labels, severity,
   evidence (file:line or query result), and a remediation owner per finding.

## Hard rules while auditing

- Read-only: no application code changes, no secret values printed (names/refs only),
  no live config mutations, no destructive SQL.
- Evidence-based only: cite `file:line` or the exact connector result for every finding.
- Tenant isolation is the #1 risk class here — check `business_id` scoping on every
  new query path, including `orWhere` chains and `withoutGlobalScopes()` escapes.
- Findings that are already covered by tests (`tests/Feature/SecurityHeadersTest.php`,
  `ProductionPathTest`, tenant-isolation tests) must be reported as "verified control",
  not as vulnerabilities.

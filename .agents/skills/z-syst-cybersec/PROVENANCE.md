# Provenance — Z-Syst curated cybersecurity skill pack

| Field | Value |
|---|---|
| Source repository | https://github.com/mukul975/Anthropic-Cybersecurity-Skills |
| Source commit | `54a798831d2266a3ca61ce68a7acb80b81160d57` (main, shallow clone) |
| License | Apache-2.0 (upstream `LICENSE` retained in source repo; skill frontmatter carries `license: Apache-2.0`) |
| Install date | 2026-11-12 |
| Install method | Verbatim copy of `skills/<name>/` directories into `.agents/skills/<name>/` |
| Installed count | 62 of 818 upstream skills (+ this router = 63 directories) |
| Selection rationale | Z-Syst stack: Laravel multi-tenant pharmacy POS/ERP, Blade/jQuery/Alpine, Flutter mobile app, Supabase PostgreSQL (RLS/GoTrue), Vercel, GitHub Actions |
| Excluded on purpose | `ot-ics-security`, `malware-analysis`, `digital-forensics`, `hardware-firmware-security`, `blockchain-security`, `wireless-security`, most `cloud-security` (AWS/Azure/GCP-specific), Windows/AD red-teaming — none map to this codebase |
| Validation | Every installed `SKILL.md` checked for agentskills frontmatter (`name`, `description`, closing `---`) and `name` matching its directory — 62/62 pass. Upstream CI validates the same files (`tools/validate-agentskills.py`, requires `pyyaml`, not runnable in this sandbox without a venv). |

## Installed skills (62)

### Web application security (22)
- bypassing-authentication-with-forced-browsing
- exploiting-idor-vulnerabilities
- exploiting-insecure-deserialization
- exploiting-race-condition-vulnerabilities
- exploiting-server-side-request-forgery
- exploiting-sql-injection-with-sqlmap
- performing-content-security-policy-bypass
- performing-csrf-attack-simulation
- performing-directory-traversal-testing
- performing-security-headers-audit
- performing-second-order-sql-injection
- performing-web-application-penetration-test
- testing-cors-misconfiguration
- testing-for-broken-access-control
- testing-for-business-logic-vulnerabilities
- testing-for-open-redirect-vulnerabilities
- testing-for-sensitive-data-exposure
- testing-for-xss-vulnerabilities

### API security (10)
- detecting-broken-object-property-level-authorization
- exploiting-api-injection-vulnerabilities
- exploiting-broken-function-level-authorization
- exploiting-excessive-data-exposure-in-api
- exploiting-mass-assignment-in-rest-apis
- implementing-api-rate-limiting-and-throttling
- performing-api-inventory-and-discovery
- testing-api-authentication-weaknesses
- testing-api-for-broken-object-level-authorization
- testing-api-security-with-owasp-top-10

### Identity & authentication (6)
- configuring-oauth2-authorization-flow
- exploiting-jwt-algorithm-confusion-attack
- performing-access-review-and-certification
- performing-jwt-none-algorithm-attack
- performing-oauth-scope-minimization-review
- performing-service-account-audit
- testing-jwt-token-security

### Mobile (Flutter) security (5)
- conducting-mobile-app-penetration-test
- exploiting-deeplink-vulnerabilities
- exploiting-insecure-data-storage-in-mobile
- performing-android-app-static-analysis-with-mobsf
- testing-mobile-api-authentication

### DevSecOps & CI/CD (6)
- implementing-infrastructure-as-code-security-scanning
- implementing-secret-scanning-with-gitleaks
- implementing-semgrep-for-custom-sast-rules
- integrating-sast-into-github-actions-pipeline
- performing-threat-modeling-with-owasp-threat-dragon
- securing-github-actions-workflows

### Container security (2)
- hardening-docker-containers-for-production
- scanning-docker-images-with-trivy

### Supply chain (4)
- detecting-malicious-npm-packages
- detecting-typosquatting-packages-in-npm-pypi
- generating-and-analyzing-sboms
- verifying-build-provenance-with-slsa-sigstore

### Vulnerability management (3)
- performing-cve-prioritization-with-kev-catalog
- performing-web-application-vulnerability-triage
- prioritizing-vulnerabilities-with-cvss-scoring

### Compliance & privacy (5)
- conducting-gdpr-compliance-assessment
- implementing-gdpr-data-protection-controls
- implementing-hipaa-security-rule-safeguards
- implementing-pci-dss-compliance-controls
- performing-privacy-impact-assessment

### Cryptography (2)
- implementing-jwt-signing-and-verification
- performing-cryptographic-audit-of-application

## Relationship to existing in-repo security assets

- `.github/skills/code-vulnscan/` — deterministic taint/CVE/secret scanner (unchanged)
- `.bug-hunter/` — prior bug-hunt artifacts (unchanged)
- `PROJECT_RULES/04_SECURITY_RULES.md` — binding security rules for this repo (unchanged)
- This pack adds manual playbooks and compliance frameworks; it replaces nothing.

No application code was modified by this installation.

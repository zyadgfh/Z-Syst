# architecture-security-reviewer

Read this when reviewing a multi-service system, SaaS app, distributed worker pipeline, plugin/connector platform, cloud deployment, or any codebase where vulnerabilities may come from trust-boundary and design flaws rather than one unsafe function call.

## Goal

Find architecture-level vulnerabilities: broken trust boundaries, inconsistent authorization, tenant isolation gaps, unsafe async flows, excessive privileges, insecure service-to-service assumptions, and missing defense-in-depth.

## Architecture map

Build a compact map before judging findings:

- Actors: anonymous user, authenticated user, admin, tenant admin, service account, worker, external provider, internal operator.
- Trust boundaries: browser to API, API to worker, worker to cloud, webhook ingress, queue topics, plugin/extension execution, database boundaries, tenant boundaries.
- Sensitive assets: credentials, tokens, PII, billing data, tenant data, audit logs, signing keys, deployment secrets.
- Enforcement points: auth middleware, policy engine, database row filters, service mesh/IAM, queue ACLs, webhook signature checks.

## Vulnerability classes

- Confused deputy: a trusted backend performs an action using attacker-controlled resource identifiers or external URLs.
- Tenant breakout: missing tenant/org scoping in API queries, background jobs, caches, search indexes, object storage keys, or webhooks.
- Inconsistent policy enforcement: one service checks authorization while another internal endpoint, worker, GraphQL resolver, or legacy route does not.
- Async trust gap: queue messages, cron jobs, imports, or webhooks assume upstream validation and skip revalidation.
- Excessive privilege: one service account can read/write all tenants, buckets, secrets, or cloud resources when a scoped role would suffice.
- Unsafe extensibility: plugin code, connector configs, user templates, or scripts run with broad filesystem, network, or credential access.
- Egress and SSRF exposure: internal metadata endpoints, cloud control planes, Kubernetes API, and internal admin services reachable from user-triggered fetches.
- Audit bypass: privileged actions without immutable audit events, actor identity, tenant context, or correlation IDs.

## Delegated / MSSP / multi-party access patterns

When one organization can delegate access into another (MSSP, platform support, partner access), verify every step of the delegation chain:

**Self-approval bypass:**
- Can a delegating party (source-tenant admin) create grants that are immediately `active` without the target/customer tenant approving? Check whether `approved_by` is set to the source actor, and whether `require_customer_approval` is enforced as a hard constraint rather than a UI toggle.
- Can the source party set grant profiles (e.g., `mssp.admin_limited`) that are broader than what the customer intended to allow?

**Grant action limit ignored in read policies:**
- Delegation grants often carry an `allowed_actions` field constraining what the delegated session can read or write. Verify every policy helper in the delegated path checks `allowed_actions`, not just grant presence and delegated role. A helper like `canReadDelegatedTarget` that verifies the grant exists but skips `allowed_actions` lets any active grant override action constraints — a user with `DelegatedReadOnly` can read any operational data even if the approved grant scoped access to `audit.read` only.

**Delegated session MFA binding:**
- High-privilege delegated sessions should require verified MFA step-up. See auth-reviewer §1.6 for the scaffold bypass pattern where `MFAAssertion` is accepted as non-empty without actual factor verification.

## Aggregate endpoint API scope bypass

API key scopes are the least-privilege boundary for API clients. When an endpoint **aggregates data from multiple domains**, it must enforce **all relevant scopes**, not just the scope of the root resource.

```go
// Flag: campaign results endpoint only enforces read:campaigns
// but the response body includes assessment, findings, detection, and report data
router.Get("/campaigns/{id}/results",
    requireAPIKeyScope("read:campaigns", handler))
// Correct:
// requireAPIKeyScopes([]string{"read:campaigns","read:assessments","read:detections"}, handler)
```

For every endpoint that joins, embeds, or aggregates data from more than one domain:
1. List what data domains appear in the response body.
2. List what scopes the current middleware enforces.
3. Flag any mismatch where the response includes data that requires scopes not checked.

## Cross-tenant shared store with per-tenant DB uniqueness

When the database enforces uniqueness per tenant (e.g., `UNIQUE(tenant_id, secret_ref)`) but the backing secret/credential store is **global** (flat filesystem, KMS namespace, Redis keys) keyed by the bare ref:

- A tenant who controls `secret_ref` can overwrite another tenant's secret material even though the database row is tenant-isolated.
- Look for patterns where `secret_ref`, `credential_ref`, or similar fields are accepted from the client, stored globally by bare key, and later resolved by any tenant using the same key.

```go
// Flag: DB row is tenant-scoped; file/KMS store is global
func (s *Service) CreateTenantSecret(ctx context.Context, actor Actor, req CreateRequest) error {
    // req.SecretRef comes from client — e.g., "connectors.chronicle.service_account"
    // DB: UNIQUE(tenant_id, secret_ref) — different tenants can have the same ref
    s.fileStore.Write(req.SecretRef, req.Value)  // global key — overwrites another tenant's secret
}
```

Mitigation: either reject client-supplied refs entirely (server generates a tenant-namespaced ref), or namespace the storage key as `<tenant_id>/<secret_ref>`.

## Reserved service-account name collision (cross-tenant availability)

When a background service auto-creates system service accounts using fixed reserved keys (e.g., `detection-orchestrator`), verify the creation path handles pre-existing disabled accounts:

- Does the lookup accept only `status='active'` rows while the uniqueness constraint covers all non-deleted rows including disabled ones?
- An OrgAdmin who creates or disables a service account with the reserved key blocks the system's auto-creation, which fails with a unique-constraint error and can stall per-tenant scheduled operations — and depending on worker error handling, may also affect tenants processed afterward in the same pass.

## Agent/relay sequence integrity

For relay protocols using monotonically increasing sequence numbers for replay protection:

**Per-task vs per-agent sequence namespace:**
- If the server enforces a global max sequence across all task dispatches for an agent, subsequent tasks starting from sequence 1 will be rejected as replays after the first task completes. Each task must use an independent sequence namespace, or the server must track sequence per-dispatch-ID rather than globally per-agent.

**Sequence reset on process restart:**
- If the agent/watchdog client initializes sequence as a local counter (starting at 1 per process start) while the server tracks the last-seen sequence persistently, all messages after a restart fail as replays until the local counter exceeds the stored maximum. This blocks delivery of kill-switch, quarantine, and redeploy-hold signals.

## Entitlement gap — sub-resource listing endpoint bypasses pack-level entitlement

When a content pack entitlement or visibility system is added that hides parent resources (packs, pack versions, pack items, actions), check that **every queryable endpoint for child/related resource types** also enforces the entitlement check — not just the parent pack endpoints. This is a common "entitlement gap" where authorization added to parent resources does not automatically propagate to sibling or child resource types that have their own listing paths.

**Pattern to flag:**

A new `visibility` or `entitlement` helper is introduced at the repository layer and applied consistently to most resources in a family:

```
Entitlement enforced on: GET /packs, GET /pack-versions, GET /pack-items, GET /actions  ✓
Entitlement missing from: GET /techniques  — returns all global techniques including those from entitled packs  ✗
```

The `techniques` listing still uses an older, permissive policy that predates the entitlement system. Any authenticated user can enumerate proprietary technique metadata from entitled packs via the techniques endpoint, bypassing entitlement enforcement applied to packs.

Check: for every resource type in a hierarchical family (pack → item → technique/variant/etc.), confirm the entitlement helper is applied to its own listing endpoint — not only to its parent. Search for listing queries that never call the entitlement filter and compare against the set of resource types the entitlement system is meant to cover. CWE-284. Severity: medium.

## Rootless graph traversal returning all tenant nodes without scope constraint

Before a change, graph queries without a root node or resource scope were rejected. After the change, "rootless" queries are permitted for specific view types (e.g., `detection_coverage`, `asset_exposure`, `technique_coverage`). If the rootless traversal selects **all non-deleted graph nodes for the tenant** without constraining by node type, resource type, edges, filters, or a specific assessment/scope, any role with a broad graph-read permission can enumerate arbitrary tenant graph resources (agents, campaigns, actions, tasks, tickets, findings, user metadata, etc.) through the rootless query path — even when direct access to those resources requires narrower role checks.

**Pattern to flag:**

```go
// Flag: rootless traversal returns all tenant nodes without type/scope constraint
func (r *Repository) traverseTenantWide(ctx context.Context, tenantID uuid.UUID) ([]Node, error) {
    // Selects ALL graph_nodes for tenant — no node_type, no resource_type, no edge filter
    return r.db.Query(ctx, tenantWideTraversalSQL, tenantID)
    // Any user with CanReadGraphs can call this via ?view=detection_coverage with no root
}
```

Check: when a query or traversal path allows omitting a root node ID, verify the resulting SQL applies constraints equivalent to what a direct resource-listing endpoint would enforce — type filters, RBAC-matching resource scopes, or a bounded subgraph anchored to an authorized root. A "read graph" permission must not become a bypass for "read agents", "read catalog", etc. CWE-284. Severity: medium.

## Graph RBAC bypass — aggregate graph permission broader than constituent resource permissions

Graph endpoints are often authorized with a single aggregate permission (`CanReadGraphs`). If that permission allows roles that are explicitly excluded from the narrower policies protecting the underlying resources (e.g., `TicketingManager` is in `CanReadGraphs` but not in `CanReadAgents` or `CanReadCatalog`; `BlueTeamObserver` is in `CanReadGraphs` but not `CanReadCatalog`), then graph traversal becomes a privilege escalation vector. Graph node responses typically include `resource_type`, `resource_id`, `display_name`, and `properties_json` for every reachable node, so a user denied direct access to catalog actions or agents can retrieve equivalent metadata via graph endpoints.

**Pattern to flag:**

```go
// Flag: CanReadGraphs allows roles that CanReadCatalog and CanReadAgents do not
var CanReadGraphs = []Role{
    ReadOnlyUser, Auditor, BlueTeamObserver, TicketingManager,  // broad set
    DetectionEngineer, CampaignOperator, Approver,
}
// But:
var CanReadCatalog = []Role{ReadOnlyUser, CampaignOperator, ...}  // no BlueTeamObserver
var CanReadAgents  = []Role{ReadOnlyUser, CampaignOperator, ...}  // no TicketingManager

// Graph node for an agent or action is accessible to TicketingManager/BlueTeamObserver
// even though direct agent/catalog endpoints reject them
```

Check: for every role in `CanReadGraphs`, verify it is also present in the read policy for each resource type whose nodes appear in graph query results. If that intersection is not enforced statically in the permission set, enforce it dynamically: filter graph traversal output by per-node resource-type authorization before returning results to the caller. `CanReadGraphs` must be the intersection of all constituent resource read permissions, not a superset. CWE-285. Severity: medium.

## Evidence requirements

For architecture findings, include:

1. The trust-boundary assumption that fails.
2. The code/config path showing where enforcement is missing or inconsistent.
3. The attacker-controlled field or action that crosses the boundary.
4. The security consequence and the smallest architectural mitigation.

Do not report abstract design criticism. Tie every finding to reachable code, configuration, or deployment behavior.

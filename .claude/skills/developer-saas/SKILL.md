---
name: developer-saas
description: "Developer SaaS skill for planning, architecting, implementing, and launching modern software-as-a-service products. Covers multi-tenancy, subscription billing, API design, onboarding, operations, security, and product/engineering tradeoffs."
argument-hint: "[feature or module]"
license: MIT
---

# Developer SaaS Skill

A complete SaaS developer skill for designing and building products that are scalable, secure, maintainable, and launch-ready.

## When to Use

Use this skill when the task involves:
- Building or reviewing a SaaS product architecture
- Designing multi-tenant systems or platform-level services
- Implementing subscription billing, trials, quotas, or usage-based plans
- Designing customer onboarding, admin portals, or tenant management
- Defining API-driven data models, integrations, or extension points
- Planning operational readiness, monitoring, deployment, or security for SaaS
- Translating product requirements into a developer-ready backlog or implementation plan

Skip this skill for:
- Static marketing content or brochureware sites
- Desktop-only single-user applications
- Simple CRUD apps without a SaaS product context
- One-off internal tools that do not require multi-tenancy, billing, or platform operations

## Core Principles

### 1. Product first
- Identify the business problem, target customer, and success metrics before architecture.
- Prioritize the smallest valuable subscription flow that delivers measurable customer outcomes.
- Use product-driven engineering: every feature must serve onboarding, activation, retention, or monetization.

### 2. Platform mindset
- Build a repeatable platform, not a one-off app. Design for new customers, tenant isolation, and incremental expansion.
- Separate business domain logic from tenant/plan concerns.
- Keep shared services generic and tenant-aware rather than hardcoded for one customer.

### 3. Safety and resilience
- Protect customer data with strong authentication, authorization, and data isolation.
- Design for operational observability, incident response, and recovery.
- Treat infrastructure, backups, and deployment as part of the product experience.

### 4. Iterative SaaS delivery
- Start with an MVP that delivers a core outcome and a clear monetization path.
- Add SaaS platform features in phases: onboarding, billing, analytics, support, automation.
- Continuously measure product usage and prioritize improvements based on real customer behavior.

## Workflow

### Step 1: Capture the SaaS problem
- Who is the customer? (persona, company size, role)
- What is the job to be done? What outcome does the product deliver?
- What are the core workflows and data entities?
- What are the business goals? (activation, retention, revenue, efficiency)
- What are the success metrics? (DAU/WAU, trial conversion, churn, ARR)

### Step 2: Choose the right tenancy model
- Single-tenant: useful only when each customer needs a fully isolated stack and custom hosting.
- Shared multi-tenant: default for most SaaS products with many customers.
- Hybrid: shared application layer, isolated storage or metadata partitioning.

### Step 3: Define the minimum viable product
- Core product feature set
- Signup / login / onboarding flow
- Billing/trial plan and payment flow
- Admin/tenant management for customer and internal staff
- Basic analytics and reporting for both customers and operations

### Step 4: Design architecture and boundaries
- Identify domain modules: users, teams/organizations, subscriptions, billing, products, usage, support.
- Define core APIs and entities with explicit ownership.
- Separate horizontal concerns: auth, tenancy, billing, notifications, audit/logging.
- Prefer small, independent services or modules with clear contracts.

### Step 5: Implement SaaS essentials
- Authentication & authorization
- Tenant/organization model and isolation
- Subscription plans, trials, and limits
- Payment integration and webhook handling
- Onboarding and activation flows
- Admin dashboard / tenant portal
- Billing, invoicing, and usage reporting

### Step 6: Harden for production
- Add monitoring, logging, alerting, and SLA-aware health checks
- Implement rate limiting, abuse protection, and API quotas
- Secure data, credentials, and third-party integrations
- Add backup, restore, and migration processes
- Validate compliance requirements if applicable (PCI, GDPR, HIPAA)

### Step 7: Launch and iterate
- Deploy a working MVP with an unambiguous call to action
- Track usage and revenue metrics
- Collect user feedback and measure product-market fit
- Iterate on onboarding, pricing, performance, and retention

## Architecture Patterns

### Tenant isolation patterns
- Shared schema, shared database, tenant_id on most rows: easiest to build, cheapest to operate.
- Shared schema, separate database per tenant: stronger isolation, more complex management.
- Separate application per tenant: reserved for enterprise or regulated workloads.

### Recommended default
- Shared app + shared database + tenant_id scoping in queries.
- Use middleware / request context to resolve the current tenant.
- Deny cross-tenant access at the service layer and database layer where possible.

### Authorization domain model
- User → Account / Organization → Tenant
- Roles and permissions scoped to account/org and optionally resources
- Admin users should be separated from tenant users if needed

### Example tenant context
```php
// Pseudo-code tenant resolver
class TenantContext
{
    public function resolve(Request $request)
    {
        $accountId = $request->user()->account_id;
        return Account::with('tenant')->findOrFail($accountId);
    }
}
```

## SaaS Domain Modules

### 1. Identity & Access
- Signup, login, password reset, SSO
- Email verification
- Team invitations, roles, and permissions
- Session management and token revocation

### 2. Org / Tenant Management
- Account/organization creation and lifecycle
- Tenant settings and branding
- User onboarding status
- Tier and plan assignment
- Tenant-level feature flags if needed

### 3. Billing and Plans
- Plan catalog with features, limits, and prices
- Trial periods and trial-to-paid flow
- Payment gateway integration (Stripe, Paddle, PayPal, local gateway)
- Billing events, invoices, receipts, payment success/failure
- Webhook processing and reconciliation
- Proration, upgrades, downgrades, cancellations

### 4. Usage & Quotas
- Track usage safely and cheaply
- Enforce quotas by tenant, plan, or resource type
- Report usage in the tenant portal
- Support both soft limits (warnings) and hard limits (blocking)

### 5. Notifications
- Email, SMS, in-app notifications, webhooks
- Billing reminders, feature announcements, security alerts
- Use templates and a notification queue
- Ensure idempotent webhook/event processing

### 6. Data & Integrations
- API-first design for customer integrations
- Webhook subscriptions and outbound events
- Third-party integrations and connector framework
- Import/export of customer data

### 7. Platform Operations
- Admin dashboard for customer management
- Support ticket or notes system
- Metrics and reports for business health
- Audit logs for key actions

### 8. Security
- Strong password rules, MFA, and SSO support
- Data encryption in transit and at rest where needed
- Least privilege access control
- Secure third-party credentials and secrets
- Protect admin and billing endpoints with extra controls

## SaaS Developer Checklist

### Product and design
- [ ] Customer persona is defined
- [ ] Core value proposition is clear
- [ ] Onboarding path is explicit
- [ ] Billing and pricing are defined

### Platform and data
- [ ] Tenant boundaries are explicit
- [ ] `tenant_id` or equivalent is present on tenant-scoped tables
- [ ] Shared services do not leak tenant data
- [ ] Audit logs exist for critical actions

### Billing and monetization
- [ ] Trial flow is implemented
- [ ] Payment gateway webhook handling is idempotent
- [ ] Invoices and receipts are generated
- [ ] Plan changes are supported safely

### Security and compliance
- [ ] Auth flows are secure
- [ ] Role-based access is enforced
- [ ] Sensitive configuration is not stored in source control
- [ ] Secrets are rotated and audited

### Operations and launch
- [ ] Monitoring and alerts are configured
- [ ] Deployments are repeatable and documented
- [ ] Backups exist for production data
- [ ] Metrics for activation and churn are tracked

## Implementation Guidance

### API design
- Use REST or GraphQL consistently across the project.
- Keep APIs versioned and stable.
- Use meaningful resource names and avoid deep nesting.
- Return machine-readable error payloads.
- Include tenant context and request metadata in logs.

### Database design
- Model business entities explicitly: accounts, subscriptions, invoices, usage records, plans.
- Keep denormalized read models for reporting if needed.
- Use database constraints for core invariants: unique tenant+slug, valid plan state, no negative quotas.
- Avoid optimistic assumptions about customer data shape.

### Deployment
- Use infrastructure-as-code when possible.
- Prefer automated builds and tests before deployment.
- Separate staging from production environments.
- Use feature flags for risky changes and gradual rollout.

### Observability
- Track customer-facing metrics: signups, trial starts, conversions, churn, revenue.
- Track operational metrics: request latency, error rate, queue depth, payment failure rate.
- Log tenant_id and request_id for every request.

## Common SaaS Mistakes

- Building a billing system before the product value is validated.
- Mixing tenant data across customers.
- Treating onboarding as optional.
- Ignoring payment and subscription edge cases.
- Deploying without monitoring or rollback plans.

## Prompt Examples

Use this skill to answer prompts like:
- "Design the architecture for a subscription SaaS that manages remote team expenses."
- "Review the multi-tenant model and identify security gaps."
- "What should the billing and trial flow look like for a developer SaaS?"
- "Generate a developer task list for launching a SaaS MVP with usage-based pricing."
- "Explain how to handle invoicing and webhook reconciliation in a SaaS product."

## Notes
- Always ask follow-up questions if the product, customer, or target plan is unclear.
- Match technical recommendations to the user’s stack, product maturity, and compliance needs.
- When a decision has tradeoffs, present both the safe default and the downside of the alternative.
- Avoid assumptions about team size, deployment environment, or legal requirements unless the user specifies them.
- Keep recommendations practical: favor validated MVP features first, then platform extensions.

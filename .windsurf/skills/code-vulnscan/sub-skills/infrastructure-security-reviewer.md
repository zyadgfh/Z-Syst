# infrastructure-security-reviewer

Read this when a repository contains cloud infrastructure, deployment manifests, runtime platform config, CI/CD deployment credentials, object storage, service accounts, ingress, cluster, network, backup, or observability configuration.

## Goal

Find infrastructure security flaws that are broader than a single IaC syntax issue: cloud IAM exposure, public network access, runtime platform weakness, storage leakage, secret/KMS misuse, CI/CD supply-chain risk, backup exposure, and missing auditability.

## Cloud IAM

- Wildcard actions/resources: `Action: "*"`, `Resource: "*"`, broad `s3:*`, `iam:*`, `kms:*`, `secretsmanager:*`.
- Cross-account or public trust: `Principal: "*"`, unbounded `sts:AssumeRole`, weak GitHub OIDC `sub`/`aud` conditions.
- Overprivileged service accounts used by apps, workers, CI runners, Kubernetes pods, or serverless functions.
- Missing separation between read-only, deploy, break-glass, runtime, and admin identities.

## Network exposure

- Public databases, Redis, Elasticsearch/OpenSearch, message brokers, admin consoles, Kubernetes API, or internal APIs.
- Security groups/firewalls allowing `0.0.0.0/0` or `::/0` to sensitive ports: 22, 2375, 5432, 3306, 6379, 9200, 27017, 6443.
- Metadata service exposure from SSRF-capable services; require IMDSv2/hop-limit controls where applicable.
- Ingress rules that bypass auth, TLS, WAF, private networking, or service mesh policy.

## Storage exposure

- Public buckets, broad object ACLs, public CDN origins, unsigned downloads for tenant/private files.
- Missing encryption, versioning, access logs, object ownership controls, or retention for sensitive storage.
- Predictable object keys that allow tenant/file enumeration.
- Public database snapshots, AMIs/images, backups, volumes, or restore jobs.

## Secrets and KMS

- Plaintext secrets in Terraform vars, Helm values, Docker Compose, CI variables, user-data, launch templates, or pod env.
- KMS key policies granting broad decrypt, wildcard principals, or CI roles that can decrypt production data.
- Long-lived deployment credentials with no rotation path.
- Secrets copied into logs, artifacts, container layers, or build caches.

## Runtime platform

- Kubernetes: privileged pods, host networking/PID/IPC, hostPath, no NetworkPolicy, broad RBAC, default service account tokens, missing Pod Security admission.
- Serverless: public function URLs, broad execution roles, unauthenticated triggers, secrets in environment, no concurrency limits.
- Containers/VMs: Docker socket mounts, root runtime, writable root filesystem, missing seccomp/AppArmor, exposed Docker daemon.
- Service mesh/ingress: permissive mTLS policy, route rules that expose internal services, missing auth at edge and service level.

## CI/CD supply chain

- Unpinned GitHub Actions, containers, curl-pipe-shell installs, mutable deployment scripts, or unchecked third-party actions.
- Pull-request workflows with secrets, deploy permissions, `pull_request_target` misuse, or OIDC tokens on untrusted triggers.
- Artifact poisoning between build/test/deploy jobs.
- Deployment jobs that can modify cloud IAM, KMS, production data, or secrets outside their intended scope.

## Audit and monitoring

- Missing or disabled cloud audit logs, Kubernetes audit logs, object access logs, load balancer logs, or database audit logs.
- Logs mutable by the same role being audited.
- Privileged changes without actor, tenant, source IP, request ID, or deployment provenance.
- No alerts for public exposure, IAM policy changes, KMS decrypt spikes, secret reads, or failed auth bursts.

## Evidence requirements

Report only when you can tie the risk to repo-visible evidence: IaC, Helm/K8s manifests, CI/CD workflows, deployment docs, config files, cloud SDK code, or generated policy JSON. Include the exposed asset, the trust boundary, the overbroad permission or network path, and the realistic consequence.

If the repo only hints at deployed posture, mark the finding `needs_environment_verification` instead of confirmed.

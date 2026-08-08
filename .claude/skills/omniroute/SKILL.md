---
name: omniroute
description: "Work effectively in the OmniRoute repository by understanding its architecture, implementing features safely, and validating changes across routing, compression, providers, and the UI."
argument-hint: "[task or area]"
license: MIT
---

# OmniRoute Repository Skill

Use this skill when working inside the OmniRoute codebase for implementation, debugging, refactoring, or contribution work. OmniRoute is an open-source AI gateway and router that exposes one OpenAI-compatible endpoint while orchestrating providers, routing strategies, compression pipelines, resilience layers, and a dashboard.

## When to Use

Use this skill when the task involves:
- Adding or fixing routing, combo, or provider behavior
- Working on compression engines such as RTK, Caveman, LLMLingua, Headroom, or Ultra
- Updating the API, CLI, dashboard UI, MCP/A2A integrations, or auth flows
- Investigating provider fallbacks, quotas, resilience, or free-tier logic
- Preparing a change for a pull request or validating a local dev setup

Skip this skill for:
- Generic web or app work unrelated to OmniRoute
- Non-repo support requests that do not need code changes
- Broad product strategy work without a concrete implementation target

## Core Principles

### 1. Start from the product goal
- Identify the user-visible outcome before changing code.
- Clarify whether the task affects API behavior, routing choice, debugability, provider compatibility, or UI experience.
- Keep the change scoped to the smallest subsystem that solves the problem.

### 2. Preserve compatibility and safety
- Keep the OpenAI-compatible API behavior stable unless the task explicitly changes it.
- Preserve provider contracts, auth flows, and fallback behavior.
- Avoid broad rewrites when a targeted fix will do.

### 3. Favor composable, testable changes
- Prefer small modules and clearly named helpers.
- Keep configuration-driven behavior explicit.
- Make routing, compression, and provider logic easy to reason about and test.

### 4. Verify before claiming completion
- Confirm the relevant behavior locally before finishing.
- Run the relevant tests, type checks, or manual verification steps.
- Update docs when a user-facing change alters setup, behavior, or configuration.

## Recommended Workflow

### Step 1: Establish repository context
- Read the repository README and the most relevant docs first.
- Identify the affected subsystem:
  - API / gateway
  - routing / auto-combo
  - compression
  - providers / integrations
  - UI / dashboard
  - security / auth
  - CLI / MCP / A2A
- Check the package and environment expectations before editing.

### Step 2: Reproduce and scope the issue
- Write down the expected behavior and the observed behavior.
- Find the relevant existing code paths and nearby tests.
- Keep the change focused on one issue or one feature at a time.

### Step 3: Implement the smallest correct fix
- Follow the surrounding conventions in the repository.
- Prefer config-driven options over hard-coded behavior when the feature is reusable.
- For provider changes, preserve provider-specific quirks and error handling.
- For compression changes, protect structured data, code blocks, URLs, and JSON output.
- For UI changes, match the existing dashboard patterns and keep state transitions explicit.

### Step 4: Validate the change
- Run the relevant local verification steps.
- If a feature changes runtime behavior, verify it through the relevant endpoint, CLI command, or UI flow.
- If the change affects compatibility or config, check the associated docs and examples.

### Step 5: Prepare for contribution
- Keep PR scope focused and clearly explained.
- Mention tests or manual verification performed.
- Note any operational tradeoffs, new config values, or edge cases.
- Follow the repository contribution guidance when branching or preparing a change.

## Subsystem Guide

### API and gateway
- Focus on request normalization, provider routing, auth handling, and response compatibility.
- Preserve OpenAI-compatible semantics unless the change explicitly targets a new capability.

### Routing and combos
- Understand the strategy and fallback logic before changing selection behavior.
- Keep resilience and fairness in mind when routing across providers and pools.

### Compression
- Be conservative with content preservation.
- Compression should reduce tokens without damaging code, structured data, or tool outputs unexpectedly.

### Providers and integrations
- Respect provider-specific rate limits, auth flows, and response formats.
- Make provider behavior easy to reason about and safe to disable or fall back.

### UI and dashboard
- Prefer consistent patterns and existing components.
- Keep new configuration surfaces explicit and understandable.

### CLI, MCP, and A2A
- Treat these surfaces as first-class interfaces.
- Ensure a change in core behavior still works through those integration paths.

## Local Setup Checklist

Before starting a task, confirm:
- [ ] The expected runtime version is available
- [ ] Dependencies can be installed successfully
- [ ] The local environment file or required secrets are configured
- [ ] The relevant documentation for the affected subsystem is read
- [ ] The change can be reproduced or validated locally

## Typical Commands

Use the repo’s documented scripts and local development flow, for example:
- Install dependencies: `npm install`
- Copy the example environment file: `cp .env.example .env`
- Start the local server: `PORT=20128 npm run dev`
- Verify the app through the browser, API, or CLI as appropriate

## Quality Bar for Completion

A change is ready when:
- The user-facing problem is actually addressed
- The change is scoped and understandable
- The relevant verification step was completed
- The implementation follows the repository’s existing conventions
- Docs or examples were updated if needed

## Example Prompts

- “Inspect the routing flow for auto-combo selection and explain how fallback works.”
- “Add a provider-specific fix in the OmniRoute codebase and verify it locally.”
- “Trace how compression is applied and suggest a safe change for a specific output type.”
- “Help me understand where the dashboard and gateway interact in this repo.”

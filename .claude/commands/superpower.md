---
description: "Superpowers (obra/superpowers) — skill-first development workflow. Subcommands: list | which <task> | skill <name> | on | <your task>."
argument-hint: "[list | which <task> | skill <name> | on | your task]"
---

Use the **Superpowers** skills installed at `.agents/skills/` (obra/superpowers, MIT — brainstorm → plan → TDD → debug → verify → review → ship).

Request: $ARGUMENTS

## How to handle it

1. **Read `.agents/skills/using-superpowers/SKILL.md` first** — it is the
   bootstrap: the rule is to invoke the relevant skill BEFORE any response or
   action (even before clarifying questions), announce
   `Using [skill] to [purpose]`, and follow the skill exactly. Also read
   `.agents/skills/using-superpowers/references/claude-code-tools.md` when the
   harness tooling matters.

2. **Route the subcommand:**
   - `list` — print the table in section 3 below; do no work.
   - `which <task>` — print only the matching row(s) from section 3; do no work.
   - `skill <name>` — read `.agents/skills/<name>/SKILL.md` **in full** and
     follow it for this conversation.
   - `on` (or no arguments) — activate the skill-first rule for the rest of
     this conversation: before any reply, check the table below, and if a skill
     even might apply (the "1% rule"), read it and use it.
   - **anything else is a task** — route it per the priority rules in
     section 4, read the matched skill(s) **in full**, then work.

3. **The 15 installed skills** (`.agents/skills/<name>/SKILL.md`):

   | Skill | Use when |
   |---|---|
   | `using-superpowers` | Starting any conversation — skill discovery & invocation rule |
   | `brainstorming` | BEFORE any creative work: features, components, behavior changes, plans |
   | `writing-plans` | You have a spec/requirements for a multi-step task, before touching code |
   | `executing-plans` | Executing a plan inline in this session (you are the implementer) |
   | `subagent-driven-development` | Executing a plan with independent tasks via subagents |
   | `dispatching-parallel-agents` | 2+ independent tasks with no shared state |
   | `using-git-worktrees` | Feature work needing isolation from the current workspace |
   | `test-driven-development` | Implementing any feature or bugfix, before writing implementation code |
   | `systematic-debugging` | Any bug, test failure, or unexpected behavior — BEFORE proposing fixes |
   | `requesting-code-review` | Task complete / before merging — verify work meets requirements |
   | `receiving-code-review` | Acting on review feedback, before implementing suggestions |
   | `verification-before-completion` | Before claiming work is done/fixed/passing, committing, or opening a PR |
   | `finishing-a-development-branch` | Implementation done, tests pass — deciding how to integrate |
   | `writing-skills` | Creating, editing, or verifying skills |
   | `diagnosing-superpowers` | A superpowers session went wrong and needs diagnosis |

4. **Priority rules when routing a real task:**
   - "Let's build/add/change X" → `brainstorming` first, then (after sign-off)
     `writing-plans` → `test-driven-development` (+ `using-git-worktrees` for
     isolated feature work) → `verification-before-completion` →
     `requesting-code-review` → `finishing-a-development-branch`.
   - "Fix this bug / test fails" → `systematic-debugging` FIRST, then
     `test-driven-development`; never jump to a fix.
   - Executing an existing plan → `executing-plans` (inline) or
     `subagent-driven-development` (subagents), with
     `dispatching-parallel-agents` when tasks are independent.
   - Multiple skills apply → process skills (brainstorming, systematic-debugging)
     come first and set the approach; implementation skills carry it out.

5. **Discipline (from `using-superpowers`):**
   - Read the CURRENT SKILL.md in full every time — never act from memory of a
     previous version.
   - Announce `Using [skill] to [purpose]` before following it; if it has a
     checklist, make a todo per item.
   - User instructions (CLAUDE.md, AGENTS.md, direct requests) take precedence
     over skills; only skip a skill workflow when the user explicitly says so.
   - Skills are Markdown procedures — they run with the agent's normal
     permissions; commands they suggest still pass through the usual
     permission model.

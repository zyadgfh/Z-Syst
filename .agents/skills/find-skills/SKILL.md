---
name: find-skills
description: "Use when the user wants to discover, evaluate, and install an existing agent skill or reusable workflow for a specific task or domain."
argument-hint: "[task or domain]"
---

# Find Skills

Use this skill when the user wants help with a task that may already exist as a reusable skill, workflow, or capability.

## When to Use

Use this skill when the user:
- asks "how do I do X?"
- asks "find a skill for X"
- asks "is there a skill that can..."
- wants to extend agent capabilities
- mentions a domain such as design, testing, deployment, documentation, review, or automation

Skip this skill for tasks that are clearly one-off, highly specific, or already well understood without needing a reusable skill.

## Workflow

### 1. Understand the need
- Identify the domain and the specific task.
- Clarify the user’s goal, expected outcome, and constraints.
- Decide whether the request is common enough that an existing skill is likely.

### 2. Check trusted sources first
- Prefer well-known and reputable skill sources.
- Look for widely used options from established maintainers or official ecosystems.
- Favor skills with clear documentation, recent maintenance, and a strong fit to the request.

### 3. Search for candidates
- Search skill directories or skill-based tooling when available.
- Try relevant keywords and alternate terms.
- If needed, search broader concepts such as "review", "deployment", "design", or "testing".

### 4. Evaluate before recommending
- Prefer skills that are relevant, documented, and credible.
- Avoid recommending low-quality, obscure, or poorly maintained options.
- If a match is weak, explain the tradeoff instead of forcing a recommendation.

### 5. Present the best options clearly
When you find a relevant skill, include:
- the skill name
- what it does
- why it fits the request
- source or reputation notes
- installation or usage guidance if available

### 6. Offer the next step
- If the user wants, install or configure the skill.
- If no suitable skill exists, say so directly and offer to help manually.
- If the workflow is recurring, suggest creating a custom skill.

## Quality Criteria

A good recommendation should:
- match the user’s real need
- come from a trustworthy and relevant source
- be easy to understand and use
- avoid overclaiming when no strong match exists

## Example Prompts

- "Find a skill for PR reviews."
- "Is there a skill to help me create a changelog?"
- "Help me discover a skill for frontend design patterns."
- "I need a workflow for deployment automation."

## Example Response Pattern

Use this structure when presenting a match:

1. Briefly acknowledge the request.
2. Name the most relevant skill.
3. Explain why it fits.
4. Share installation or usage steps if available.
5. Offer to help refine the search or install it.

## When No Skill Is Found

If nothing suitable is found:
- say that clearly
- offer to help with the task directly
- suggest creating a custom skill if the workflow should be reusable

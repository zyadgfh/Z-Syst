# GSAP skills — provenance

| | |
|---|---|
| Upstream | https://github.com/greensock/gsap-skills |
| Author | GreenSock (the GSAP team) |
| Commit | `aed9cfd32777` (2026-04-21, "Added Google Antigravity Support") |
| License | MIT — see `references/LICENSE` |
| Installed | 2026-11-10 |
| Files | 8 skills verbatim + `references/{llms.txt,examples/,LICENSE}` |

## What is vendored here

The eight official skills are installed **byte-for-byte** as sibling directories
so their `description` frontmatter keeps working for automatic skill discovery,
and so an upstream update is a straight file copy:

```
.agents/skills/gsap-core/SKILL.md          14,792 B
.agents/skills/gsap-timeline/SKILL.md       4,394 B
.agents/skills/gsap-scrolltrigger/SKILL.md 18,390 B
.agents/skills/gsap-plugins/SKILL.md       21,579 B
.agents/skills/gsap-utils/SKILL.md         12,093 B
.agents/skills/gsap-performance/SKILL.md    4,139 B
.agents/skills/gsap-react/SKILL.md          6,565 B
.agents/skills/gsap-frameworks/SKILL.md    10,621 B
```

Nothing upstream was edited. The only files written for this repository are:

- `.agents/skills/gsap/SKILL.md` — the router, plus the Z-Syst integration and
  CSP rules that upstream cannot know about.
- `.claude/commands/gsap.md` — the `/gsap` slash command.
- This file.

`references/examples/` holds upstream's runnable vanilla / React / Vue / Nuxt
examples. React, Vue and Nuxt are **not** used by this project — they are kept
because the upstream skills refer to them, and because the storefront's Alpine
setup is closest to the vanilla example.

## Updating

```bash
curl -sL https://codeload.github.com/greensock/gsap-skills/tar.gz/refs/heads/main \
  -o /tmp/gsap.tgz && mkdir -p /tmp/gsap \
  && tar -xzf /tmp/gsap.tgz -C /tmp/gsap --strip-components=1

for d in /tmp/gsap/skills/*/; do
  n=$(basename "$d")
  cp "$d/SKILL.md" ".agents/skills/$n/SKILL.md"
done
cp /tmp/gsap/skills/llms.txt .agents/skills/gsap/references/llms.txt
cp -r /tmp/gsap/examples      .agents/skills/gsap/references/
cp /tmp/gsap/LICENSE          .agents/skills/gsap/references/LICENSE
```

Then update the commit hash in the table above and in the `metadata.version`
field of `.agents/skills/gsap/SKILL.md`. Re-check the **CSP** section of the
router: if upstream starts recommending a different CDN, that host is not
allowlisted in `app/Http/Middleware/SecurityHeaders.php`.

## Why this source

`greensock/gsap-skills` is the official skill pack published by the GSAP team
itself (~18.2k stars, MIT). The alternatives that surfaced in a GitHub search
were community re-writings of the same API (`AThevon/genjutsu`,
`iart-ai/web-animation-skills`, `maessamx/gsap-skill`) or curated link lists
(`frankxai/awesome-motion-design-agent-skills`) — useful, but second-hand, and
several still describe plugins as paid "Club GSAP" content, which stopped being
true after Webflow's acquisition.

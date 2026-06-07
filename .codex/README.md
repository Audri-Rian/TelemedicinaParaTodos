# Codex Mirrors

This directory mirrors the project Claude setup for Codex users.

- `agents/` contains the original Claude agent definitions as reference prompts.
- `commands/` contains the original Claude chat command definitions as reference prompts.
- The executable Codex-facing versions live in `.agents/skills/`.
- Root `AGENTS.md` maps the command equivalents and project rules.

Primary commands:

- `/lead "feature"` → `.agents/skills/tech-lead-sdd/SKILL.md`
- `/review-security [files]` → `.agents/skills/security-reviewer/SKILL.md`
- `/review-performance [files]` → `.agents/skills/performance-reviewer/SKILL.md`
- `/review-full [files]` → `.agents/skills/full-review/SKILL.md`

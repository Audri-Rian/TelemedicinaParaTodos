# Codex Project Instructions

This repository uses local Codex skills mirrored from the Claude setup.

## Always Apply

- Use `.agents/skills/development-rules/SKILL.md` before implementing, refactoring, or modifying code.
- Use `.agents/skills/commit-message/SKILL.md` when preparing a final commit message.
- Never edit generated files listed by the development rules, especially `resources/js/routes/`, `resources/js/actions/`, `resources/js/wayfinder/`, `resources/js/components/ui/`, and `bootstrap/ssr/`.
- Respect existing user changes in the working tree. Do not revert unrelated files.

## Command Equivalents

- `/lead "feature"` maps to the `tech-lead` / `tech-lead-sdd` skill. It analyzes the feature and writes a spec in `docs/specs/` before implementation.
- `/review-security [files]` maps to the `security-reviewer` skill.
- `/review-performance [files]` maps to the `performance-reviewer` skill.
- `/review-full [files]` maps to the `full-review` skill, running security and performance review over the same file set.

## Project Stack

Laravel 12, Inertia.js, Vue 3, TypeScript, PostgreSQL, Redis, RabbitMQ, Laravel Reverb, and domain requirements for telemedicine, LGPD, medical records, appointments, documents, and integrations.

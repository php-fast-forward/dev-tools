# Project Agents

Repository-specific agents live in this directory.

## Naming Convention

- Use one Markdown file per agent.
- Name files after the stable agent slug, for example `issue-editor.md`.
- Keep agent names hyphenated so they match references in `AGENTS.md` and GitHub-facing tooling.

## File Format

Each agent file uses:

1. a small YAML front matter block with the agent `name`, a short `description`,
   the `primary-skill`, and optional `supporting-skills`;
2. a Markdown body with these sections:
   - `Purpose`
   - `Responsibilities`
   - `Use When`
   - `Boundaries`
   - `Primary Skill`
   - `Supporting Skills`

## Scope

These prompts are specific to the Fast Forward DevTools repository. They define
durable role behavior and delegation boundaries, while `.agents/skills` remains
the procedural source of truth.

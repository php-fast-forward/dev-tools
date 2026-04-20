---
name: create-agentsmd
description: Generate or refresh repository-level AGENTS.md files with actionable setup, workflow, testing, style, and contribution guidance for coding agents. Use when a repository is missing AGENTS.md, when an existing AGENTS.md is stale, or when agent-facing project instructions need to be aligned with the current checkout.
---

# Fast Forward AGENTS.md

Use this skill to create or refresh a repository-root `AGENTS.md` that helps
coding agents work effectively in the current project. Keep the file specific to
the checked-out repository, verify commands and paths before writing them, and
prefer concise instructions over generic explanations.

## Workflow

1. Inspect the repository first: language, package manager, build and test
   entrypoints, CI workflows, docs, and any existing `AGENTS.md`.
2. Decide whether to create or update:
   - create `AGENTS.md` at the repository root when the file is missing;
   - update the existing file in place when it already contains repository-specific
     guidance worth preserving.
3. Capture only repository-relevant instructions that an agent would not safely
   infer from code alone.
4. Verify every command, path, workflow name, and generated artifact reference
   against the current checkout before finalizing the file.
5. Keep the result aligned with the public `agents.md` guidance without copying
   generic filler into the repository.

## Required Coverage

- Project overview and architecture cues that affect implementation work
- Setup, installation, and local development commands
- Testing commands, focused test patterns, and quality gates
- Code style, file-organization, and naming expectations that matter in review
- Build, release, or deployment behavior when the repository exposes one
- Pull request or contribution rules when they are discoverable from local files

## Writing Rules

- Use direct, actionable language aimed at another coding agent.
- Prefer exact commands in backticks instead of vague descriptions.
- Mention real paths, scripts, workflow files, and generated outputs.
- Keep `AGENTS.md` as a companion to `README.md`, not a duplicate of it.
- Call out monorepo or nested-`AGENTS.md` precedence only when it actually applies.
- Preserve valid repository-specific guidance when updating an existing file.
- Do not invent commands, services, environment variables, or workflows that are
  not present in the checkout.

## Reference Guide

| Need | Reference |
|------|-----------|
| Section planning and optional coverage | [references/content-outline.md](references/content-outline.md) |
| Final quality checks before finishing the file | [references/content-outline.md](references/content-outline.md) |

## Anti-patterns

- Do not paste a generic AGENTS template without inspecting the repository.
- Do not describe tools or frameworks that are not present in the checkout.
- Do not duplicate large sections of `README.md` when a short cross-reference is
  enough.
- Do not leave placeholder text, TODO markers, or fake commands in the file.
- Do not rewrite a good existing `AGENTS.md` from scratch when a focused refresh
  is enough.

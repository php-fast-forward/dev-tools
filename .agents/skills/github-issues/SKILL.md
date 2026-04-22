---
name: github-issues
description: Draft, create, update, comment on, and close GitHub issues for Fast Forward repositories. Use when an agent needs to turn a brief feature, bug, or task prompt into a production-ready issue, refine existing issue content, publish or update it on GitHub, manage existing metadata such as labels, types, milestones, projects, and project fields, add issue comments, or close tracked work without leaving the local workflow.
---

# Fast Forward GitHub Issues

Use this skill for the full Fast Forward issue lifecycle: draft implementation-ready issue content, then publish or maintain that issue on GitHub when requested. Keep issue copy in English and make every GitHub mutation explicit.

## Workflow

1. Resolve the request shape first: draft only, create, update, comment, or close. For GitHub mutations, read [references/context.md](references/context.md).
2. When the title or body needs drafting or refinement, classify the request as a feature, bug, or task. Read [references/triage.md](references/triage.md).
3. Build or revise the issue content with [references/templates.md](references/templates.md) and [references/architectural-criteria.md](references/architectural-criteria.md).
4. Run the content-quality pass in [references/review-checklist.md](references/review-checklist.md).
5. When the user wants a GitHub write, choose the exact mutation in [references/operations.md](references/operations.md) and the metadata rules in [references/metadata.md](references/metadata.md).
6. Re-run the GitHub write checks in [references/review-checklist.md](references/review-checklist.md), then return the issue number, URL, and a short summary of what changed.

## Output Contract

- For drafting-only requests, produce a ready-to-use Markdown issue with a specific, action-oriented title.
- For create, update, comment, or close requests, return the issue number, issue URL, and a short mutation summary.
- Use objective, testable language and prefer repository vocabulary over generic platform language.
- Include CLI examples, flags, paths, generated artifacts, or error cases when the change touches commands, automation, or reports.
- Add explicit non-goals when the prompt could expand into multiple initiatives.
- Ask follow-up questions only when a missing fact would materially change the issue type, acceptance criteria, or target issue. Otherwise make the smallest safe assumption and state it briefly.
- When publishing or updating an issue, explicitly state which metadata was applied or intentionally omitted: issue type, labels, milestone, project assignment, project field values, and related open issues.

## Fast Forward Defaults

- Prefer the current repository checkout when the user asks about "this repo" or "this project".
- Use `gh api` for GitHub write operations.
- Prefer issue types over labels for primary categorization when the organization supports them.
- Reuse only issue types, labels, milestones, projects, and project field options that already exist in the target repository or organization.
- Prefer filling the maximum useful metadata that can be inferred safely from the issue scope and the available GitHub configuration.
- Do not force weak labels, milestones, project assignments, or project field values when the fit is unclear.
- When a new issue appears materially related to another open issue, add that relationship instead of leaving the issues disconnected.
- Treat command or controller layers as orchestration only when drafting implementation issues.
- Prefer dedicated classes for input resolution, domain logic, processing, and output rendering when the change is non-trivial.
- Call out test, README, docs, wiki, sync, or generated report updates when the change clearly affects them.
- Treat deterministic output, CI compatibility, and future extensibility as first-class requirements.
- For CLI work, specify arguments or options, failure modes, exit behavior, and visible output surfaces.

## Reference Guide

| Need | Reference |
|------|-----------|
| Resolve the repository and target issue safely | [references/context.md](references/context.md) |
| Classify the prompt and choose the issue type | [references/triage.md](references/triage.md) |
| Start from a reusable issue body structure | [references/templates.md](references/templates.md) |
| Paste the correct acceptance-criteria block | [references/architectural-criteria.md](references/architectural-criteria.md) |
| Create, update, comment on, or close an issue | [references/operations.md](references/operations.md) |
| Choose issue types, labels, assignees, milestones, projects, and related issue metadata | [references/metadata.md](references/metadata.md) |
| Perform the final quality and mutation-safety pass | [references/review-checklist.md](references/review-checklist.md) |

## Anti-patterns

- Do not write an issue that jumps straight to implementation without explaining the problem.
- Do not leave acceptance criteria subjective or non-testable.
- Do not overload the issue with unrelated cleanup that belongs in a separate task.
- Do not force the code-isolation block onto documentation-only work.
- Do not ask exploratory questions when repository conventions already provide a safe default.
- Do not publish a placeholder issue body or mutate GitHub without restating the target issue first.
- Do not split drafting and publication into separate local skills when this workflow already covers both.
- Do not invent labels, issue types, milestones, projects, project field values, or issue links that are not already supported by the target repository context.

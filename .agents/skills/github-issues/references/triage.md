# Triage Guide

Use this guide to classify a brief prompt before drafting the issue body.

## Request Classification

| Signals in the prompt | Issue type | Title verbs | Draft emphasis |
|-----------------------|------------|-------------|----------------|
| add, introduce, support, allow, enhance, improve | Feature | Add, Introduce, Support, Allow, Enhance | user value, proposed behavior, rollout constraints |
| fix, broken, failing, regression, error, crash, incorrect | Bug | Fix, Resolve, Prevent, Restore | current vs expected behavior, failure surface, regression coverage |
| refactor, clean up, sync, standardize, reorganize, update, remove duplication | Task | Refactor, Standardize, Sync, Reorganize, Update | scope boundaries, structural goals, done definition |

## Ask or Infer

- Infer repository conventions whenever the prompt is short but the affected area is obvious.
- Ask a follow-up only when the missing detail would change the issue type, scope, or acceptance criteria in a material way.
- For feature requests, infer sensible defaults for command names, file locations, and output behavior from the repository layout.
- For bug reports, ask only if current behavior and expected behavior cannot both be inferred.
- For tasks and refactors, shrink scope with explicit non-goals instead of expanding the prompt into a redesign.

## Title Strategy

- Keep titles specific and action-oriented.
- Prefer the affected behavior or component over vague improvement language.
- Keep titles short enough to scan quickly. A practical target is about 72 characters.

Examples:

- `Add dependency analysis command to dev-tools`
- `Fix report generation when coverage directory is missing`
- `Refactor wiki sync pipeline into isolated services`

## Fast Forward Hints

- Command-oriented prompts usually need command signature, console output, exit behavior, and generated artifact expectations.
- Documentation prompts usually need affected docs sections, cross-links, and regeneration or sync expectations.
- Automation prompts usually need deterministic ordering, idempotent writes, and CI-safe behavior.

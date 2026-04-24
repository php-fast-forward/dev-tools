---
name: pull-request-review
description: Review Fast Forward pull requests with a findings-first contract that prioritizes bugs, regressions, missing tests, missing docs, generated-output drift, and workflow risk over general summaries.
---

# Fast Forward Pull Request Review

Use this skill when a pull request is ready for review and maintainers need a
repeatable, high-signal review pass. The review MUST lead with concrete
findings, ordered by severity, with repository file references whenever
possible.

## Workflow

1. Resolve the pull request context, touched files, and highest-risk surfaces.
   Read [references/surface-priorities.md](references/surface-priorities.md).
2. Inspect behavior before style. Start with source, workflows, generated
   outputs, synchronized assets, tests, and documentation that can change
   release or automation behavior.
3. When the pull request changes workflows, local actions, or packaged workflow
   wrappers, build an executable validation strategy before declaring the
   surface clean. Read
   [references/workflow-action-validation.md](references/workflow-action-validation.md).
4. Produce the review using the findings-first contract. Read
   [references/review-contract.md](references/review-contract.md).
5. Call out missing or weak tests, missing docs, changelog gaps, generated
   artifact drift, and consumer-sync impacts whenever the changed surfaces make
   them relevant.
6. Only after the findings, add a brief summary or note residual risk. If no
   issues are found, say that clearly and mention any remaining verification
   gaps.

## Fast Forward Defaults

- Findings come first. Summaries are secondary.
- Prioritize bugs, regressions, missing coverage, missing documentation,
  workflow or CI risks, generated-output drift, and sync side effects over
  stylistic nits.
- Treat ``.github/workflows``, ``resources/github-actions``, ``.github/actions``,
  ``.agents/skills``, ``.agents/agents``, ``README.md``, ``docs/``,
  ``CHANGELOG.md``, and ``.github/wiki`` as high-signal review surfaces.
- When ``.github/wiki`` moves, verify whether the wiki preview or wiki
  maintenance workflow is expected to refresh the submodule pointer before
  treating that change as unrelated drift or scope creep.
- When workflow automation pushes commits with ``GITHUB_TOKEN``, verify that
  required checks are dispatched or mirrored for the bot-authored commit before
  treating the workflow as safe.
- For workflow, local-action, or packaged-wrapper changes, prefer deterministic
  validation such as shell/YAML checks, fake ``gh``/``git`` harnesses,
  temporary repositories, or a temporary validation PR when local simulation
  cannot cover the behavior.
- Prefer precise repository file references in every finding.
- Review what changed, but reason about downstream consumer impact when the PR
  touches packaged assets or synchronized defaults.
- Do not dilute the review with praise or generic narrative before the
  findings.

## Reference Guide

| Need | Reference |
|------|-----------|
| Decide which changed surfaces deserve the closest scrutiny | [references/surface-priorities.md](references/surface-priorities.md) |
| Validate workflow, local-action, and packaged-wrapper changes | [references/workflow-action-validation.md](references/workflow-action-validation.md) |
| Format the review output in the expected findings-first shape | [references/review-contract.md](references/review-contract.md) |

## Anti-patterns

- Do not lead with a summary before the findings.
- Do not spend the review budget on low-value formatting commentary when
  behavioral or workflow risk exists.
- Do not ignore generated files, synced assets, or workflow wrappers when the
  pull request touched their sources.
- Do not mark workflow or action changes clean without an explicit validation
  strategy and evidence, or a clear residual-risk note when behavior cannot be
  exercised before merge.
- Do not claim a pull request is clean without mentioning the verification
  scope or any residual gaps.

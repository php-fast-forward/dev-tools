---
name: github-pull-request
description: Implement a ready Fast Forward GitHub issue on an isolated branch, verify code, tests, and documentation impacts, and open or update the corresponding pull request. Use when an agent needs to move an issue through the GitHub implementation-to-PR workflow, or when the user asks to work through ready issues sequentially. Resolve repository or pull-request context through local git and GitHub data when needed.
---

# Fast Forward GitHub Pull Request

Use this skill to take a Fast Forward issue from "ready to implement" to an opened or updated pull request. Default to one issue per run unless the user explicitly wants queue processing and the repository remains clean after each PR.

## Workflow

1. Resolve repository, branch, issue, and PR context. Read [references/context-routing.md](references/context-routing.md).
2. Select the next ready issue, or decide to skip or stop. Read [references/issue-selection.md](references/issue-selection.md).
3. Implement the issue on an isolated branch and keep the write scope focused. Read [references/implementation-loop.md](references/implementation-loop.md).
4. Draft or update the PR using repository templates when present, otherwise use the fallback structure. Read [references/pr-drafting.md](references/pr-drafting.md).
5. Run the final gate in [references/review-checklist.md](references/review-checklist.md) before handing results to the user.

## Fast Forward Defaults

- Keep one branch and one PR per issue.
- Branch from `main` or the repository integration branch, never from another feature branch.
- Prefer local `git` for checkout, commit, and push.
- Prefer connector-backed GitHub data for issue and PR context when available.
- Use `phpunit-tests`, `package-readme`, `sphinx-docs`, and `changelog-generator` whenever a change affects testable behavior, public APIs, documented usage, or the change history — including adding, modifying, or removing features, bug fixes, or contract changes.
- Never manually close an issue; rely on `Closes #123` style text in the PR body.
- Do not block waiting for merge. Open or update the PR, then report status and the next action.

## Changelog Updates

For any change that is user-visible or affects behavior, use `changelog-generator` to update CHANGELOG.md.

1. Use the `changelog-generator` skill to analyze code changes since last release
2. Add entries under the [Unreleased] section only for PR-specific changes
3. Write concise, specific, user-facing descriptions (avoid implementation details) following the skill's quality rules
4. Include PR reference when applicable: "Added changelog automation (#40)"
5. Group entries by type if applicable (e.g., Added, Fixed, Changed, Breaking)

**Rules**:

- Do not duplicate existing entries
- Do not move or modify past releases
- Every eligible PR must include its changelog entry before merge

This ensures every PR has proper changelog documentation before merge.

## Reference Guide

| Need | Reference |
|------|-----------|
| Resolve repo, branch, issue, and PR context | [references/context-routing.md](references/context-routing.md) |
| Choose the next issue or decide to skip | [references/issue-selection.md](references/issue-selection.md) |
| Execute the implementation and verification loop | [references/implementation-loop.md](references/implementation-loop.md) |
| Create or update the PR body and title | [references/pr-drafting.md](references/pr-drafting.md) |
| Perform the final quality pass | [references/review-checklist.md](references/review-checklist.md) |

## Anti-patterns

- Do not batch unrelated issues into one branch or PR.
- Do not create a duplicate PR if the current branch already has one.
- Do not open a PR before running the relevant verification commands.
- Do not proceed to the next issue if the repository is dirty from unfinished work.
- Do not let a vague issue body force broad implementation guesses; stop and clarify when the acceptance criteria are not actionable.

# Pull Request Drafting

Use this reference once the branch is pushed and the implementation is verified.

## First Check: Existing PR

Before creating a new PR, check whether the current branch already has an open PR.

- If a PR already exists, update it instead of creating a duplicate.
- If no PR exists, create one against `main` or the repository's integration branch.
- If an earlier PR for the same issue or historical branch already exists but
  is closed, do not assume that branch should be reused. When the old branch
  was deleted or the previous PR represents finished work, open a follow-up
  bug issue and publish a fresh branch and PR.

## Template Rule

If the repository provides `.github/pull_request_template.md`, use it as the source of truth for the PR body.

Use a template-first PR drafting flow:

- inspect the template first
- create or locate the PR
- update title and body with concrete implementation details

If no PR template exists, use the fallback structure below.

## Fallback PR Body

```markdown
## Summary
[One short paragraph describing the implemented behavior]

## Changes
- [Concrete change]
- [Concrete change]

## Testing
- [Command and result]
- [Command and result]

Closes #123
```

## Title Guidance

- Follow repository title rules when they exist.
- For this repository, prefer `[area] Brief description`.
- Use the issue number that the PR closes in the title when it is known.
- Derive `area` from the touched subsystem, command, or package rather than using a generic label.

Examples:

- `[tests] Add command coverage for sync workflow`
- `[docs] Document wiki generation flow`
- `[command] Add dependency analysis command`

## Draft vs Ready

- Create a draft PR when the user explicitly wants review before finalization or when you still need external confirmation.
- Create a ready PR when verification is complete and the change is ready for review.

## Optional Follow-ups

Apply only when the repository workflow or the user asks for them:

- assign the PR author
- add labels
- link related issues beyond the closing issue

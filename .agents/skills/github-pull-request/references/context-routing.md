# Context and Routing

Resolve the operating context before writing code or creating a PR.

## Repository and Branch Context

- If the user names a repository, issue, PR, or URL, use that directly.
- If the request is about the current checkout, inspect local `git` context first.
- If the repository is still ambiguous after local inspection, ask for the repository instead of guessing.

## Hybrid GitHub Model

Follow this hybrid GitHub model:

- Prefer connector-backed GitHub data for repository, issue, and PR metadata.
- Use local `git` for checkout, branching, staging, committing, and pushing.
- Use `gh` only for the gaps that are hard to cover otherwise, especially current-branch PR discovery, auth checks, and GitHub Actions inspection.

## When to Use This Orientation Pass

Use this orientation pass when:

- the repository or PR context is unclear
- the user asks for general GitHub triage before implementation
- you need a summary of existing PRs or issue state before choosing the next issue

Once the work becomes a local implementation and publish flow, stay in this skill instead of treating broad GitHub triage as a separate skill dependency.

## Preconditions

Before starting implementation, confirm:

- local checkout points at the intended repository
- the base branch exists locally and can be updated
- the issue is specific enough to implement
- authentication for the required GitHub operations is available if a PR will be opened

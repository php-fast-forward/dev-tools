---
name: fast-forward-pull-request
description: Implement open GitHub issues in a repository one by one with isolated branches, tests, documentation updates, and pull requests. Use when Codex is asked to work through issue queues, implement repository issues sequentially, or manage the full issue-to-PR flow in Fast Forward projects.
---

# Implement GitHub Issues

This skill iterates through all open issues in the repository and implements them sequentially following a structured workflow.

## Workflow Overview

For each open issue:

1. **Branching**: Create a new branch from `main` (never from another branch)
2. **Implementation**: Implement the solution following the issue description and acceptance criteria
3. **Commits**: Create well-structured commits with clear messages
4. **Tests**: Create or update tests using the `fast-forward-tests` skill
5. **Documentation**: Update README using `fast-forward-readme`, update docs using `fast-forward-docs` if applicable
6. **Pull Request**: Open a PR targeting `main` with `Closes #123` style closing text in the description
7. **Issue Handling**: Never close issues directly; only reference them via `Closes #123` style text in the PR

## Global Rules

- Everything must be written in English
- Never branch from a non-main branch
- Never accumulate work across issues in the same branch
- Keep changes isolated per issue
- Ensure code builds and tests pass before opening PR

## Prerequisites

Before starting, verify the repository context:

- Ensure you are in the correct repository directory
- Confirm `main` branch exists and is up-to-date
- Verify GitHub CLI (`gh`) is authenticated

## Step-by-Step Process

### Step 1: List Open Issues

Use the MCP tool `mcp__github__list_issues` to get all open issues:

- State: `open`
- Sort by: `created` (oldest first) or `updated` (most recently updated)
- Per page: 100 (to get all issues)

### Step 2: For Each Issue

For each open issue, perform the following:

#### 2.1 Read Issue Details

Use `mcp__github__issue_read` to get the full issue content:

- Title
- Body/description
- Labels
- Assignees
- Milestone

#### 2.2 Create Branch

Create a new branch from `main`:

```bash
git checkout main
git pull origin main
git checkout -b {type}/{issue-number}-{short-description}
```

Branch naming convention:

- `feature/123-description` for features
- `fix/123-description` for bug fixes
- `task/123-description` for tasks

#### 2.3 Implement Solution

Follow the issue description and acceptance criteria strictly:

- Do not introduce unrelated changes
- Keep implementation aligned with project architectural patterns
- Write clean, maintainable code

#### 2.4 Create Commits

Create multiple well-structured commits:

- Each commit represents a logical step
- Clear, descriptive commit messages
- Example:
  ```bash
  git add src/ChangedFile.php
  git commit -m "Add new feature for issue #123"
  ```

#### 2.5 Run Tests

After implementation, run tests:

```bash
composer dev-tools tests
```

If tests fail, fix the implementation before proceeding.

#### 2.6 Create/Update Tests

Use the `fast-forward-tests` skill to create or update tests:

- Invoke the skill with `skill(name="fast-forward-tests")`
- Follow the skill's workflow for test generation

#### 2.7 Update Documentation

If applicable:

- Use `fast-forward-readme` skill for README updates
- Use `fast-forward-docs` skill for Sphinx documentation

#### 2.8 Ensure Code Quality

Run the full dev-tools suite:

```bash
composer dev-tools
```

Fix any issues found (code style, static analysis, etc.).

#### 2.9 Push and Create PR

```bash
git push -u origin {branch-name}
```

Create a pull request:

- Target branch: `main`
- Title: `[{type}] {issue-title}`
- Description:
  ```markdown
  ## Summary
  Brief description of what was implemented.

  ## Changes
  - Change 1
  - Change 2

  ## Testing
  Describe testing approach and results.

  Closes #{issue-number}
  ```

**Important**: Include `Closes #{issue-number}` in the PR description so the issue is automatically closed only after the PR is merged.

#### 2.10 Wait for Merge

Wait for the PR to be merged before proceeding to the next issue.

### Step 3: Iterate to Next Issue

After a PR is merged, move to the next open issue and repeat the process.

## Issue Processing Order

Process issues in order of:

1. Milestone (if any)
2. Priority (if labels indicate priority)
3. Creation date (oldest first)

Skip issues that:

- Are blocked by another open issue
- Need more information from the reporter
- Are marked as `wontfix` or `duplicate`

## Notes

- Never manually close issues - only through PR merge
- Keep each branch focused on a single issue
- If an issue is too large, consider breaking it into smaller tasks
- Document any assumptions made during implementation
- Reference related issues in commit messages when relevant

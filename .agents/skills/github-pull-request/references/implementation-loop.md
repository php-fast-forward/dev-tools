# Implementation Loop

Use this loop for each selected issue.

## Branching

- Update `main` or the repository's integration branch before branching.
- Keep branch names stable and descriptive.
- Prefer the repository convention when one exists.
- If no repository convention is defined, use a stable pattern such as `issue-123-short-slug`.

## Implementation Boundaries

- Keep the diff focused on the selected issue.
- Do not fold unrelated cleanup into the branch unless it is required to make the issue pass.
- Follow existing repository architecture and naming patterns.

## Companion Skills

Use these only when the issue clearly needs them:

- `phpunit-tests` for new or updated PHPUnit coverage
- `package-readme` for README changes
- `sphinx-docs` for Sphinx or docs tree changes
- `phpdoc-code-style` for repository-specific PHPDoc cleanup, file-header normalization, and PHP formatting when the issue touches PHP comments or contracts

## Verification Strategy

Choose the smallest command that proves the change first, then run the project gate before opening the PR.

For Fast Forward repositories, prefer:

```bash
composer dev-tools tests -- --filter=RelevantTestName
composer dev-tools
```

Also verify any touched generated or synchronized outputs when relevant:

- wiki content
- docs output
- reports
- sync-generated files

## Commit Discipline

- Create clear commits that reflect logical steps.
- Keep commit messages specific to the issue.
- Do not publish a branch that still contains unresolved local uncertainty or broken verification.

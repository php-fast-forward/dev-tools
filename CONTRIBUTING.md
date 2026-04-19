# Contributing to FastForward DevTools

Thanks for helping improve FastForward DevTools.

## Getting Started

1. Fork the repository and create a branch from `main`.
2. Install dependencies:

   ```bash
   composer install
   ```

3. Run the standard project gate:

   ```bash
   composer dev-tools
   ```

## Development Workflow

Use the Composer commands exposed by the package during normal development:

```bash
composer dev-tools
composer dev-tools:fix
composer dev-tools tests
composer dev-tools docs
composer dev-tools:sync
```

Focused checks are fine while iterating, but please run the relevant
verification for the files you changed before opening a pull request.

## Coding Standards

- Follow the repository PHP style and architecture patterns already in `src/`
  and `tests/`.
- Keep command classes focused on orchestration and move non-trivial logic
  into dedicated collaborators.
- Update documentation when a change affects commands, workflows, generated
  outputs, or consumer onboarding.

## Changelog Expectations

Notable pull requests are expected to add an entry to `CHANGELOG.md`.

Typical flow:

```bash
composer changelog:entry --type=changed "Describe the notable change (#123)"
composer changelog:check --against=origin/main
```

## Pull Request Process

- Use the title format `[area] Brief description (#issue-number)` whenever an
  issue exists.
- Fill in the pull request template completely.
- Link the issue with `Closes #123` style wording in the PR body.
- Include the commands you ran to verify the change.

## Reporting Problems

- Bugs and feature ideas should use the GitHub issue templates.
- Security issues must not be reported publicly. Please follow
  [`SECURITY.md`](SECURITY.md).
- General help requests should follow [`SUPPORT.md`](SUPPORT.md).

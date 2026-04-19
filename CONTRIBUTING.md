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

4. Read the project guidance before making structural changes:

   - `README.md` for the public command surface and contributor workflow
   - `docs/` for the generated-user documentation structure
   - `AGENTS.md` for repository-specific engineering patterns, project agents,
     and skill usage

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

If you contribute with Codex or project agents, prefer the packaged workflow
helpers that already exist in this repository:

- `.agents/skills/github-pull-request/` for issue-to-branch-to-PR flow
- `.agents/skills/github-issues/` for issue drafting and updates
- `.agents/skills/phpunit-tests/` for focused PHPUnit coverage work
- `.agents/skills/phpdoc-code-style/` for PHPDoc and repository PHP style
- `.agents/skills/package-readme/` and `.agents/skills/sphinx-docs/` for
  README and `docs/` updates
- `.agents/agents/` for repository-specific role prompts such as
  `docs-writer`, `readme-maintainer`, `consumer-sync-auditor`, and
  `changelog-maintainer`

## Coding Standards

- Follow the repository PHP style and architecture patterns already in `src/`
  and `tests/`.
- Keep command classes focused on orchestration and move non-trivial logic
  into dedicated collaborators.
- Update documentation when a change affects commands, workflows, generated
  outputs, or consumer onboarding.
- Respect the current command bootstrapping and dependency-injection patterns
  described in `AGENTS.md` and `docs/internals/architecture.rst`.
- Keep generated or synchronized surfaces consistent when a change affects
  workflows, wiki output, consumer sync assets, or packaged skills.

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

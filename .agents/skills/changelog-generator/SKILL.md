---
name: changelog-generator
description: Generate or refresh human-readable CHANGELOG.md files that follow Keep a Changelog by comparing git tags and code diffs instead of commit messages. Use when an agent needs to bootstrap a changelog for a repository, backfill undocumented tagged releases, update release entries, or rewrite the Unreleased section for current branch work using the phly/keep-a-changelog commands available in the project.
---

# Changelog Generator

Generate changelog entries that a reader can understand without opening the code.

## Deterministic Helpers

Use the bundled PHP scripts before manual analysis:

```bash
php .agents/skills/changelog-generator/scripts/changelog-state.php
php .agents/skills/changelog-generator/scripts/diff-inventory.php <from-ref> <to-ref>
```

- `changelog-state.php` reports changelog presence, documented versions, discovered tags, undocumented tags, and suggested release ranges as JSON.
- `diff-inventory.php` reports changed files, line counts, and likely user-visible paths for a specific diff range as JSON.
- Both scripts auto-discover the repository root and opportunistically load `vendor/autoload.php` when it exists.

## Workflow

1. Establish current state.
- Read `CHANGELOG.md` if it exists.
- Prefer `changelog-state.php` to gather versions and ranges before inspecting files manually.
- Record documented versions and whether the official `## [Unreleased]` heading already exists.
- List tags in ascending semantic order with `git tag --sort=version:refname`, and capture their commit dates when the repository may have retroactive or out-of-sequence tags.
- Treat commit messages as navigation hints only; never derive final changelog text from them.

2. Choose diff ranges.
- If `CHANGELOG.md` is missing or empty, analyze each tag range from the first tagged version onward.
- If `CHANGELOG.md` already documents releases, start at the first tag after the last documented version.
- When tag publication order differs from semantic order, prefer the actual tag chronology for release ordering and use diffs that follow that real release sequence.
- Build `Unreleased` from the diff between the latest documented release or tag and `HEAD`.

3. Analyze changes from diffs.
- Prefer `diff-inventory.php <from> <to>` first so you can focus on the files most likely to affect user-visible behavior.
- Start with `git diff --name-status <from> <to>` and `git diff --stat <from> <to>`.
- Open targeted `git diff --unified=0 <from> <to> -- <path>` views for files that define public behavior, commands, config, schemas, workflows, or exposed APIs.
- Classify entries by observed impact:
  - `Added`: new files, APIs, commands, options, configuration, workflows, or user-visible capabilities
  - `Changed`: modified behavior, signature or default changes, renamed flows, or compatibility-preserving refactors with visible impact
  - `Fixed`: bug fixes, validation corrections, edge-case handling, or broken workflows
  - `Removed`: deleted APIs, commands, config, or capabilities
  - `Deprecated`: explicit deprecation notices or migration paths
  - `Security`: hardening or vulnerability fixes
- Skip pure churn that a reader would not care about unless it changes behavior or release expectations.
- Deduplicate multiple file changes that describe the same user-visible outcome.

4. Write human-readable entries.
- Write one line per change.
- Prefer the functional effect over implementation detail.
- Mention the concrete command, class, option, workflow, or API when that improves comprehension.
- When a matching PR exists, append it to the line in the format `(#123)` after the diff already supports the entry.
- Avoid vague phrases such as `misc improvements`, `refactorings`, or `code cleanup`.
- Keep the file structure compliant with Keep a Changelog 1.1.0: bracketed version headings, the official intro paragraph, and footer references for `Unreleased` and each version.
- Omit empty sections instead of inserting placeholder entries such as `Nothing.`.

5. Apply changes with project tooling.
- Prefer the local wrappers when available:

```bash
composer dev-tools changelog:init
composer dev-tools changelog:check
```

- Use the official CLI for entries and releases:

```bash
vendor/bin/keep-a-changelog entry:added "..."
vendor/bin/keep-a-changelog entry:changed "..."
vendor/bin/keep-a-changelog entry:fixed "..."
vendor/bin/keep-a-changelog unreleased:create --no-interaction
vendor/bin/keep-a-changelog unreleased:promote 1.2.0 --date=2026-04-12 --no-interaction
vendor/bin/keep-a-changelog version:show 1.2.0
vendor/bin/keep-a-changelog version:release 1.2.0 --provider-token=...
```

- For large historical backfills, direct markdown editing is acceptable for the first draft. After that, use the CLI to keep `Unreleased` and future entries consistent.

6. Verify the result.
- Keep `Unreleased` first and released versions in reverse chronological order.
- Keep section order as `Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`.
- Do not duplicate the same change across sections or versions.
- Ensure every documented version maps to a real tag or intentional unreleased state.
- Ensure footer references exist in the official style: `[unreleased]: ...`, `[1.2.0]: ...`.
- Run local helpers such as `composer dev-tools changelog:check` when the project provides them.

## PR Context

Use PR descriptions, issue text, or release notes only to refine wording after diff analysis confirms the change. Good uses:

- naming a feature exactly as presented to users
- adding a stable reference like `(#123)`
- understanding why a visible change matters when the diff alone is ambiguous

Do not use PR text to invent entries that are not supported by the code diff.

## Reference Files

- Read [references/keep-a-changelog-format.md](references/keep-a-changelog-format.md) for heading format, section order, and CLI mapping.
- Read [references/official-example-template.md](references/official-example-template.md) when you want a local template that mirrors the official Keep a Changelog example.
- Read [references/change-categories.md](references/change-categories.md) when the diff spans multiple change types.
- Read [references/description-patterns.md](references/description-patterns.md) when the first draft still sounds too internal or vague.

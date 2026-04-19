---
name: changelog-generator
description: Create, maintain, and validate human-readable changelog files that follow Keep a Changelog 1.1.0 using the local dev-tools changelog commands. Use when an agent needs to bootstrap a repository's first changelog, add categorized entries to Unreleased or a published version, check whether a branch added changelog content, infer the next version from Unreleased, promote Unreleased into a released version, or export release notes for publishing workflows.
---

# Changelog Generator

Maintain changelog files for humans first while keeping them deterministic enough for release automation.

## Workflow

1. Establish current state.
- Resolve the target file path first. Default to `CHANGELOG.md`, but respect any caller-provided `--file` path.
- Check whether the changelog file exists.
- Record whether `Unreleased` already has entries and whether any published releases already exist.
- If the file does not exist yet, or if Git tags exist that are not documented yet, treat the task as a historical backfill before switching to incremental maintenance.

2. Backfill missing release history when needed.
- If the repository has no changelog, or if some Git tags are still undocumented, walk the Git tags until the changelog is complete.
- Inspect tags in chronological order so each documented version can be derived from the diff against the previous tag.
- Capture the creation date for each tag and use it as the release date recorded in the changelog.
- For each missing released version:
  1. compare the previous tag to the current tag;
  2. record the current tag date;
  3. extract the notable user-facing, maintainer-facing, or automation-facing changes from that diff;
  4. resolve any associated pull request numbers from merge commits, squash commit titles, or release history;
  5. classify them with the standard Keep a Changelog categories;
  6. add them to the matching released section with `changelog:entry --release=<version> --date=<YYYY-MM-DD>`.
- Only after all historical tags are represented should new work continue in `Unreleased`.
- If a tag exists but the diff does not justify a notable entry, keep the release section minimal rather than inventing noise.

3. Choose the right local command.
- To add one new entry to `Unreleased`:

```bash
composer dev-tools changelog:entry -- --type=added "Add example workflow"
composer dev-tools changelog:entry -- --type=fixed "Fix release note validation"
```

- To add or amend an entry in a published section:

```bash
composer dev-tools changelog:entry -- --type=changed --release=1.2.0 "Adjust published note"
composer dev-tools changelog:entry -- --type=fixed --release=1.1.0 --date=2026-04-09 "Correct release metadata handling"
```

- To validate that a branch added changelog content:

```bash
composer dev-tools changelog:check
composer dev-tools changelog:check -- --against=refs/remotes/origin/main
composer dev-tools changelog:check -- --file=docs/CHANGELOG.md --against=origin/main
```

- To infer the next semantic version from `Unreleased`:

```bash
composer dev-tools changelog:next-version
composer dev-tools changelog:next-version -- --file=docs/CHANGELOG.md
```

- To promote `Unreleased` into a release:

```bash
composer dev-tools changelog:promote 1.2.0 -- --date=2026-04-19
composer dev-tools changelog:promote 1.2.0 -- --file=docs/CHANGELOG.md
```

- To export release notes from one published section:

```bash
composer dev-tools changelog:show 1.2.0
composer dev-tools changelog:show 1.2.0 -- --file=docs/CHANGELOG.md
```

4. Write human-readable entries.
- Keep each entry to one line.
- Prefer the user-visible effect over the implementation detail.
- Name the concrete surface when that helps: command, option, workflow, configuration, integration, or output.
- Avoid vague filler such as `misc improvements`, `cleanup`, or `refactorings`.
- When a change can be tied to a specific pull request, append that PR reference like `(#123)` to the entry.
- During tag backfill, actively look for PR numbers in merge commits, squash merge titles, or related release metadata before writing the final message.

5. Respect the managed format.
- Keep `Unreleased` first.
- Keep released versions in reverse chronological order.
- Keep section order as:
  1. `Added`
  2. `Changed`
  3. `Deprecated`
  4. `Removed`
  5. `Fixed`
  6. `Security`
- Omit empty sections.
- Preserve the official introduction and footer-reference style from Keep a Changelog 1.1.0.

6. Verify the result.
- For branch validation, prefer running:

```bash
composer dev-tools changelog:check -- --against=refs/remotes/origin/main
```

- For release preparation, also inspect:

```bash
composer dev-tools changelog:next-version
composer dev-tools changelog:show -- <version>
```

## Output Contract

- When the task is to add a changelog record, produce one or more command invocations that create the exact entries needed.
- When the repository has no changelog yet, or has undocumented tags, reconstruct the missing release history from Git tags before treating the work as ordinary `Unreleased` maintenance.
- When a changelog entry can be traced to a specific pull request, include that PR reference in the rendered entry.
- When the task is to validate a PR or branch, use `changelog:check` and report whether the branch has meaningful unreleased changes.
- When the task is to prepare a release, use `changelog:next-version`, `changelog:promote`, and `changelog:show` in that order unless the caller asks for only one step.
- When a repository has no changelog yet, bootstrap it through `changelog:entry` rather than hand-writing the initial file.

## Consumer Repository Notes

- Repositories using Fast Forward DevTools may not have a changelog yet. In that case, the first `changelog:entry` call SHOULD create the managed file automatically.
- Do not assume a consumer repository already has historical sections.
- Do not assume published Git tags are already documented. Compare the tags to the changelog and backfill any missing released sections.
- When a consumer repository has no previous release, `changelog:next-version` may infer `0.1.0` from the first meaningful `Unreleased` section.
- Do not assume the managed file lives at `CHANGELOG.md`; respect alternate paths when the caller or repository uses them.

## Reference Files

- Read [references/keep-a-changelog-format.md](references/keep-a-changelog-format.md) for the expected file structure.
- Read [references/official-example-template.md](references/official-example-template.md) for a concrete template.
- Read [references/change-categories.md](references/change-categories.md) when classifying entries.
- Read [references/description-patterns.md](references/description-patterns.md) when polishing wording.

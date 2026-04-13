# Keep a Changelog 1.0.0 Format

Use the official Keep a Changelog 1.0.0 structure as the default target format:

- Official guidance: `https://keepachangelog.com/en/1.0.0/`
- Official example: `https://keepachangelog.com/en/1.0.0/#how-do-i-make-a-good-changelog`

## Required introduction

Mirror the official introduction exactly unless the repository already has an approved custom introduction:

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
```

## Required heading shape

Use bracketed headings and footer references exactly like the official example:

```markdown
## [Unreleased]

### Added
- ...

## [1.4.0] - 2026-04-11

### Changed
- ...
```

## Footer references

Versions and `Unreleased` SHOULD be linkable through footer references in the official style:

```markdown
[unreleased]: https://github.com/<owner>/<repo>/compare/v1.4.0...HEAD
[1.4.0]: https://github.com/<owner>/<repo>/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/<owner>/<repo>/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/<owner>/<repo>/releases/tag/v1.2.0
```

Rules:

- `Unreleased` compares the latest documented tag to `HEAD`.
- Each released version compares the previous documented release tag to the current tag.
- The oldest documented release links to its release page when no older release exists in the changelog.
- When tags were published out of semantic order, keep the changelog ordered by actual release chronology and generate comparison links between adjacent displayed releases.

## Section order

Keep change types grouped in this order:

1. `Added`
2. `Changed`
3. `Deprecated`
4. `Removed`
5. `Fixed`
6. `Security`

## Compliance rules from the official guidance

- Changelogs are for humans, not machines.
- There SHOULD be an entry for every single version.
- The same types of changes SHOULD be grouped.
- Versions and sections SHOULD be linkable.
- The latest version SHOULD come first.
- The release date of each version SHOULD be displayed in ISO 8601 format: `YYYY-MM-DD`.
- Mention whether the project follows Semantic Versioning.
- Omit empty sections instead of filling them with placeholders such as `Nothing.`.

## CLI mapping

Check available commands locally when unsure:

```bash
vendor/bin/keep-a-changelog list --raw
```

Most common commands:

```bash
composer dev-tools changelog:init
composer dev-tools changelog:check
vendor/bin/keep-a-changelog entry:added "..."
vendor/bin/keep-a-changelog entry:changed "..."
vendor/bin/keep-a-changelog entry:fixed "..."
vendor/bin/keep-a-changelog entry:removed "..."
vendor/bin/keep-a-changelog entry:deprecated "..."
vendor/bin/keep-a-changelog unreleased:create --no-interaction
vendor/bin/keep-a-changelog unreleased:promote 1.2.0 --date=2026-04-12 --no-interaction
vendor/bin/keep-a-changelog version:show 1.2.0
vendor/bin/keep-a-changelog version:release 1.2.0 --provider-token=...
```

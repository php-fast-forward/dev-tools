# Keep a Changelog Format

## Required heading shape

Use this shape unless the repository already has a stronger house style:

```markdown
# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## Unreleased - TBD

### Added
- ...

### Changed
- ...

### Deprecated
- ...

### Removed
- ...

### Fixed
- ...

### Security
- ...

## 1.4.0 - 2026-04-11

### Added
- ...
```

## Section order

Always keep sections in this order:

1. `Added`
2. `Changed`
3. `Deprecated`
4. `Removed`
5. `Fixed`
6. `Security`

Preserve the repository's existing convention for empty sections. In Fast Forward repositories, `Unreleased` may keep placeholders while released versions usually keep only populated sections.

## Version rules

- Keep `Unreleased` first.
- Keep released versions in reverse chronological order.
- When version numbers were tagged out of sequence, prefer actual tag chronology over semantic version sorting for final section order.
- Use ISO 8601 dates: `YYYY-MM-DD`.
- Match version headings to real tags whenever possible.

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

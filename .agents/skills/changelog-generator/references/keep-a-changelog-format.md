# Keep a Changelog 1.1.0 Format

Use the official Keep a Changelog 1.1.0 structure as the default target format:

- Official guidance: `https://keepachangelog.com/en/1.1.0/`
- Official example: `https://keepachangelog.com/en/1.1.0/#how`

## Required introduction

Mirror the managed introduction exactly:

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
```

## Required heading shape

Use bracketed headings and footer references in the managed style:

```markdown
## [Unreleased]

### Added
- ...

## [1.4.0] - 2026-04-11

### Changed
- ...
```

## Footer references

Versions and `Unreleased` SHOULD be linkable through footer references:

```markdown
[unreleased]: https://github.com/<owner>/<repo>/compare/v1.4.0...HEAD
[1.4.0]: https://github.com/<owner>/<repo>/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/<owner>/<repo>/compare/v1.2.0...v1.3.0
[1.0.0]: https://github.com/<owner>/<repo>/releases/tag/v1.0.0
```

Rules:

- `Unreleased` compares the latest documented tag to `HEAD`.
- Each released version compares the previous documented release tag to the current tag.
- The oldest documented release links to its release page when no older release exists in the changelog.
- When bootstrapping or backfilling a changelog, document released versions in chronological order from the oldest missing tag to the newest missing tag, then render the final file in reverse chronological order.

## Section order

Keep change types grouped in this order:

1. `Added`
2. `Changed`
3. `Deprecated`
4. `Removed`
5. `Fixed`
6. `Security`

## Local command mapping

Use the local dev-tools commands instead of any external changelog CLI:

```bash
composer dev-tools changelog:entry -- --type=added "..."
composer dev-tools changelog:check
composer dev-tools changelog:next-version
composer dev-tools changelog:promote -- 1.2.0 --date=2026-04-19
composer dev-tools changelog:show -- 1.2.0
```

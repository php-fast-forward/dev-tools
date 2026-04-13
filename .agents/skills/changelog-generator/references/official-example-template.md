# Official Example Template

This is a repository-adapted template that mirrors the official Keep a Changelog 1.1.0 example structure.

Source of truth:

- `https://keepachangelog.com/en/1.1.0/`

Use this shape when drafting or rewriting `CHANGELOG.md`:

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Added `ExampleCommand` to bootstrap changelog automation (#40)

### Changed
- Changed release notes automation to export changelog entries directly from `CHANGELOG.md` (#40)

## [1.0.0] - 2026-04-08

### Added
- Initial public release of the package

### Fixed
- Fixed release metadata for the first tagged version

[unreleased]: https://github.com/<owner>/<repo>/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/<owner>/<repo>/releases/tag/v1.0.0
```

Notes:

- Keep headings bracketed.
- Keep footer references at the bottom of the file.
- Omit empty sections.
- Prefer compare links for every release except the oldest documented one.

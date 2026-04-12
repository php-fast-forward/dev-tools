# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [Unreleased] - TBD

### Added

- Add changelog bootstrap, validation commands, and reusable workflows

### Changed

- Sync changelog scripts and release note automation into consumer repositories

## [1.5.0] - 2026-04-11

### Added

- Added `Bootstrapper` class to bootstrap `.keep-a-changelog.ini` and `CHANGELOG.md`
- Added `BootstrapResult` value object for bootstrap outcome reporting
- Added `CommitClassifier` to classify commits into keep-a-changelog sections
- Added `GitProcessRunner` for git command execution in changelog workflows
- Added `GitReleaseCollector` to discover release tags and commits
- Added `HistoryGenerator` to render markdown from git history
- Added `KeepAChangelogConfigRenderer` for config file generation
- Added `MarkdownRenderer` for keep-a-changelog format output
- Added `UnreleasedEntryChecker` to compare unreleased entries against git baseline
- Added `ChangelogCheckCommand` (`changelog:check`) for changelog validation
- Added `ChangelogInitCommand` (`changelog:init`) for changelog bootstrapping
- Added reusable GitHub Actions workflows in `resources/github-actions/`
- Added documentation in `docs/usage/changelog-management.rst`

### Changed

- Improved `SyncCommand` to synchronize changelog automation assets
- Updated GitHub Actions to use PHP 8.3

## [1.4.0] - 2026-04-11

### Added

- Added `CoverageSummary` for programmatic PHPUnit coverage data access
- Added `CoverageSummaryLoader` for loading coverage summaries

### Changed

- Replaced coverage-check dependency

### Fixed

- Updated Symfony components to support version 8.0

## [1.3.0] - 2026-04-11

### Added

- Added `context7.json` to export-ignore list
- Added `GitAttributes` management with Reader, Merger, and Writer implementations
- Added `GitAttributesCommand` to manage export-ignore rules
- Added comprehensive tests for GitAttributes and License components
- Added License file generation support

### Changed

- Isolated `GitAttributesCommand` as standalone command

## [1.2.2] - 2026-04-10

### Changed

- Updated .gitattributes

### Fixed

- Enhanced documentation for license generation classes

## [1.2.1] - 2026-04-10

### Fixed

- Enhanced documentation for license generation

## [1.2.0] - 2026-04-10

### Added

- Added `CopyLicenseCommand` for LICENSE file generation
- Added `DependenciesCommand` for Composer dependency analysis
- Added `SkillsCommand` to synchronize packaged agent skills
- Added PHPDoc and PHPUnit test skeleton generation skills

### Changed

- Bundled dependency analysers with dev-tools

### Fixed

- Updated dependency analysis command

## [1.1.0] - 2026-04-09

### Added

- Added GrumPHP integration for Git hooks
- Added Rector automated refactoring
- Added ECS code style enforcement
- Added API documentation generation

### Changed

- Updated Composer scripts prefix to `dev-tools`

## [1.0.0] - 2026-04-08

### Added

- Added Composer plugin (`FastForward\DevTools\Composer\Plugin`) for unified dev-tools commands
- Added `CodeStyleCommand` (`dev-tools:code-style`) for ECS and code style fixes
- Added `DocsCommand` (`dev-tools:docs`) for Sphinx documentation generation
- Added `PhpDocCommand` (`dev-tools:phpdoc`) for PHPDoc validation and fixes
- Added `RefactorCommand` (`dev-tools:refactor`) for Rector refactoring
- Added `ReportsCommand` (`dev-tools:reports`) for API documentation generation
- Added `StandardsCommand` (`dev-tools:standards`) for combined quality checks
- Added `SyncCommand` (`dev-tools:sync`) to synchronize GitHub Actions and .editorconfig
- Added `TestsCommand` (`dev-tools:tests`) for PHPUnit test execution
- Added `WikiCommand` (`dev-tools:wiki`) for GitHub wiki generation
- Added `DevToolsExtension` for PHPUnit integration with JoliNotif notifications
- Added custom Rector rules for PHPDoc generation
- Added GitHub Actions workflows for CI/CD

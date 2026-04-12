# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## Unreleased - TBD

### Added

- Added `phly/keep-a-changelog` integration with `changelog:init`, `changelog:check`, and reusable `changelog-bump`, `require-changelog`, and `release` workflows to bootstrap `CHANGELOG.md`, validate `Unreleased`, and publish matching GitHub release notes (#40)
- Added reusable `auto-assign` and `label-sync` workflows to assign pull request authors and copy linked issue labels onto pull requests (#35)
- Added the `changelog-generator` skill and bundled PHP helpers to inspect changelog state and prioritize diff review before drafting entries (#40)

### Changed

- Changed `dev-tools:sync`, the README, and Sphinx usage docs to install and document changelog automation assets for consumer repositories (#40)
- Changed the reports, tests, and wiki workflows to install dependencies with `php-actions/composer` and lockfile-based Composer caching (#35)
- Changed the reports and wiki workflows to keep Composer plugins enabled when dev-tools commands must run in CI (#39)
- Changed changelog bootstrap and validation workflows to run Composer-based dev-tools commands on PHP 8.3 instead of legacy script shims (#40)
- Changed changelog guidance to derive entries from code diffs and append related pull request references in the format `(#123)` when a matching PR exists (#40)

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

### Security

- Nothing.

## 1.4.0 - 2026-04-11

### Added

- Added `CoverageSummary` and `CoverageSummaryLoader` so PHPUnit coverage data can be reused programmatically in tooling and reports

### Changed

- Changed PHPUnit coverage validation to use the in-repository summary loader instead of an external checker

### Fixed

- Fixed Symfony component constraints to support version 8.0

## 1.3.0 - 2026-04-11

### Added

- Added `GitAttributesCommand` and supporting reader, merger, and writer services to keep `export-ignore` rules synchronized with packaged files
- Added export-ignore coverage for license templates and `context7.json` in packaged archives

### Changed

- Changed Git attribute synchronization into a dedicated command instead of implicit sync logic

## 1.2.1 - 2026-04-10

### Added

- Added fuller PHPDoc coverage for license generation services and interfaces (#26)

### Changed

- Changed `.gitattributes` to export the packaging metadata introduced with license generation (#26)

## 1.2.0 - 2026-04-10

### Added

- Added `CopyLicenseCommand`, `DependenciesCommand`, and `SkillsCommand` to generate LICENSE files, audit Composer dependencies, and sync packaged agent skills into consumer repositories
- Added bundled license templates plus Fast Forward PHPDoc and PHPUnit skill packages for synced projects

### Changed

- Changed installation and consumer automation docs to cover dependency analysis, skill synchronization, and branch and pull request workflow guidance

### Fixed

- Fixed dependency analysis reporting for missing and unused Composer packages

## 1.1.0 - 2026-04-09

### Added

- Added `.gitignore` synchronization with classifier, merger, reader, and writer services for consumer repositories
- Added `ECSConfig` and `RectorConfig` extension points so consumers can override default tool configuration
- Added Fast Forward skills for GitHub issue, Sphinx docs, README, and PHPUnit workflows

### Changed

- Changed the installation flow into `dev-tools:sync`, which synchronizes scripts, GitHub workflow templates, `.editorconfig`, Dependabot config, and the repository wiki submodule
- Changed command abstractions and Composer plugin wiring to simplify CLI orchestration and consumer setup

### Fixed

- Fixed the reports workflow trigger, PHPDoc cleanup, skill file endings, and `.editorconfig` synchronization

## 1.0.0 - 2026-04-08

### Added

- Added PHPUnit desktop notification support through `DevToolsExtension`, `ByPassfinalsStartedSubscriber`, `JoliNotifExecutionFinishedSubscriber`, and bundled notifier assets
- Added expanded Sphinx guides, FAQ content, project links, and README badges for installation, configuration, and usage
- Added Dependabot and funding templates plus the phpDocumentor bootstrap template and Composer changelog plugin to the packaged tooling

### Changed

- Changed `install` into `dev-tools:sync` and updated Composer hooks to synchronize scripts after install and update
- Changed `DocsCommand` to accept a custom template path and include standard issue markers in generated API docs
- Changed `TestsCommand` to accept `--filter` for targeted PHPUnit runs
- Changed workflow templates, GitHub Pages metadata, and package dependencies to support richer reports and consumer automation

### Fixed

- Fixed GitHub Pages metadata, workflow PHP extension declarations, wiki submodule path handling, and phpdoc command arguments

## 1.2.2 - 2026-03-26

### Added

- Added `install` command to synchronize dev-tools scripts, reusable GitHub workflow-call templates, `.editorconfig`, and GrumPHP defaults into consumer repositories

### Changed

- Changed Composer plugin hooks to run synchronization after install and update instead of package-specific events
- Changed PHPUnit, reports, and wiki workflows to call the package's shared GitHub Actions templates
- Changed command and Rector/PHPDoc internals to align with the install-based synchronization flow

### Removed

- Removed `InstallScriptsCommand` and `ScriptsInstallerTrait` in favor of the unified install command

## 1.0.4 - 2026-03-26

### Changed

- Changed `DocsCommand` to resolve configuration files relative to the project root, including consumer-friendly relative paths

## 1.0.3 - 2026-03-26

### Added

- Added package name validation to install scripts before updating consumer repositories

## 1.0.2 - 2026-03-26

### Changed

- Changed Composer plugin metadata to declare `composer/composer` as a required dependency during installation

## 1.0.1 - 2026-03-26

### Added

- Added `InstallScriptsCommand` and `WikiCommand` to install dev-tools scripts and publish repository wiki content from the package
- Added README, Sphinx docs, and phpDocumentor configuration to document installation, commands, and workflows
- Added automated tests for commands, Composer plugin integration, and the custom Rector rules shipped with dev-tools

### Changed

- Changed core commands and Composer plugin wiring to resolve project paths, autoload files, and generated reports more reliably
- Changed tests, reports, and wiki workflows to run against the packaged commands and publish their artifacts consistently

### Fixed

- Fixed command argument handling, bin registration, path resolution, deploy wiring, and coverage configuration for the initial packaged release

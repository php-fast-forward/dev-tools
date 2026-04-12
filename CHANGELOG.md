# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## Unreleased - TBD

### Added

- Add changelog bootstrap, validation commands, and reusable workflows

### Changed

- Sync changelog scripts and release note automation into consumer repositories

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

- Add CoverageSummary and CoverageSummaryLoader for programmatic access to PHPUnit coverage data
- Add CoverageSummaryTest for coverage validation

### Changed

- Replace coverage-check dependency (#30)

### Fixed

- Update Symfony components to support version 8.0

## 1.3.0 - 2026-04-11

### Added

- Add context7.json to export-ignore list
- Add comprehensive tests for GitAttributes and License components
- Create context7.json
- Expand candidate list and update docs
- Introduce GitAttributes management with Reader, Merger, and Writer implementations
- Add GitAttributesCommand to manage export-ignore rules

### Changed

- Isolate GitAttributesCommand as standalone command

## 1.2.1 - 2026-04-10

### Added

- Enhance documentation for license generation classes and interfaces

### Changed

- Update .gitattributes

## 1.2.0 - 2026-04-10

### Added

- Add command and documentation for LICENSE file generation
- Implement CopyLicenseCommand for generating LICENSE files
- Add various license files and update template loader path
- Add license file generation to dev-tools:sync
- Added support for dependency analysis with new commands and execution improvements.
- Update documentation to include details about the new skills command for synchronizing packaged agent skills
- Add tests for SkillsSynchronizer and SkillsCommand, including synchronization and link manipulation scenarios.
- Refactor SkillsSynchronizer and SkillsCommand for improved logging and synchronization handling
- Add descriptive PHPDoc to skills classes and refactor sync methods
- Add SkillsCommand to synchronize packaged skills into consumer repositories
- Add PHPDoc and PHPUnit test skills with comprehensive guidelines
- Add new skills for GitHub issue management and documentation generation

### Changed

- Bundle dependency analysers with dev-tools (#10)
- Add dependency analysis command (#10)
- Update branching pattern and PR title guidance in documentation

### Fixed

- Update command for reporting missing and unused Composer dependencies

## 1.1.0 - 2026-04-09

### Added

- Add docs for command to merge and synchronize .gitignore files
- Introduce GitIgnoreInterface and related classes for .gitignore management
- Add .gitignore sync to dev-tools:sync
- Make rector.php extensible with RectorConfig class
- Make ecs.php extensible with ECSConfig class
- Add fast-forward-github-issues skill and agent for structured issue implementation
- Add skills for generating Sphinx documentation, README files, and PHPUnit tests with guidelines and checklists

### Changed

- Add unit tests for ECSConfig and RectorConfig classes
- Simplify command execution and update input handling
- Add ECSConfig extension examples to documentation

### Fixed

- Remove pull_request trigger from reports workflow
- PHPDocs
- Add ending line to skills
- Update .editorconfig

## 1.0.0 - 2026-04-08

### Added

- Add Composer badge to README and refine project description
- Update README with additional badges and improve .gitignore and .gitattributes
- Create FUNDING.yml
- Add comprehensive documentation updates and new FAQ section
- Add pcntl extension to PHP workflow for report generation
- Add pcntl extension to PHPUnit test workflow
- Remove OrderedDocblock and related tests; update AddMissingMethodPhpDocRector to simplify docblock handling
- Refactor ByPassfinalsStartedSubscriberTest to use Instantiator for event creation
- Add symfony/var-exporter dependency to composer.json
- Add symfony/var-dumper dependency to composer.json
- Add JoliNotif and BypassFinals integration for PHPUnit notifications; update installation instructions
- Add pyrech/composer-changelogs dependency and allow plugin
- Add template option to DocsCommand and include phpdoc-bootstrap-template dependency
- Adds a filter option for running tests in the TestsCommand command.
- Adds Dependabot configuration and updates the Sync command to copy the dependabot.yml file.
- Adds the OrderedDocblock class and implements tag ordering for PHPDoc normalization.
- Adds GeneralPhpdocAnnotationRemoveFixer to the ECS configuration.

### Changed

- Unify post-install and post-update event handling to run sync command
- Update the Composer cache key to use composer.json instead of composer.lock in the reporting, testing, and wiki workflows.
- Updates the Git submodule path to be relative to the current working directory in the SyncCommand class.
- Replace $this->filesystem->readFile by file_get_contents on DocsCommand to avoid composer compatibility issues
- GitHub Actions(deps): Bump actions/deploy-pages from 4 to 5
- GitHub Actions(deps): Bump actions/cache from 4 to 5
- GitHub Actions(deps): Bump actions/checkout from 4 to 6
- GitHub Actions(deps): Bump actions/upload-pages-artifact from 3 to 4
- Update GitHub workflows to trigger on push and workflow_dispatch.
- Adjust the php-cs-fixer configuration to set the order of the phpdoc tags.
- Rename the installation command to 'sync' and implement the SyncCommand class to synchronize development scripts, GitHub workflows, and .editorconfig files.
- Update the installation command to use the prefix 'dev-tools:' instead of 'install'.
- Update the getDevToolsFile method to use the parent directory instead of the path to the installed package.

### Fixed

- Update homepage URL in composer.json to point to GitHub Pages
- Remove unnecessary parameters from PayPal donation link in FUNDING.yml
- Correct php_extensions format in workflows for reports and tests
- Reorder variable assignments in addRepositoryWikiGitSubmodule method to fix tests on ci
- Remove trailing whitespace in phpdoc command arguments

## 1.2.2 - 2026-03-26

### Added

- Adds support to ensure that the repository wiki is added as a git submodule in .github/wiki during the installation of dev-tools scripts.
- Adds support for reusable GitHub Actions workflows and updates the script installation command.
- Adds support for GrumPHP and updates script installation commands in composer.json

### Changed

- Update phpdocs
- Refactor methods to use getDevToolsFile in AbstractCommand and DocsCommand.
- Updates search path for GitHub Actions configuration files.
- Updates installation command to synchronize scripts, GitHub workflows, and .editorconfig files.

### Removed

- Remove .editorconfig from export-ignore in .gitattributes

### Fixed

- Fix standards
- Fix github actions
- Fix install-scripts

## 1.0.4 - 2026-03-26

### Changed

- Updates the configuration file resolution in DocsCommand and adjusts the corresponding test to accept relative paths.

## 1.0.3 - 2026-03-26

### Added

- Add package name verification to install scripts and update tests to reflect changes.

## 1.0.2 - 2026-03-26

### Changed

- Set "composer/composer" dependency to "require" index

## 1.0.1 - 2026-03-26

### Added

- Add scripts to composer.json
- Add InstallScriptsCommand
- Improve Rector docblock handling and expand test coverage for commands and Composer plugin.
- Add unit tests
- Add REAME.md
- Add PHPDoc
- Add autoload to PhpDoc command
- Add backslash

### Changed

- Update scripts
- Update README.md
- Update docs
- Update GitHub Pages
- Apply autostash to rebase pulling
- Update wiki
- Migrate wiki submodule to .github/wiki and update all references
- Migrate wiki submodule to .github/wiki and update references
- Update docs command
- Revert "Remove unnecessary extract"
- Replace absolute path with configuration method in ReportCommand
- Apply standards
- Update actions
- Update GitHub Actions
- Update reports
- Enhance TTY support handling
- First commit!

### Removed

- Remove unnecessary extract
- Remove submodules from unecessary actions

### Fixed

- Fix docs
- Fix composer.json bin reference
- Fix ScriptsInstallerTrait
- Fix deploy
- Fix absolute path of php-cs-fixer
- Fix input class
- Fix paths
- Fix arguments
- Fix coverage
- Fix reports deploy
- Fix coverage check
- Fix TTY GitHub Action bug


# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Auto-create and push minimal changelog entries for same-repository Dependabot pull requests before changelog validation reruns (#186)

## [1.20.0] - 2026-04-23

### Changed

- Force reusable workflows to sparse-checkout `.github/actions` into a dedicated `.dev-tools-actions` workspace before resolving local GitHub Actions so consumer wrappers stop failing on missing action paths (#180)

## [1.19.0] - 2026-04-23

### Added

- Package a rigorous pull-request review skill, review-guardian agent, and ready-for-review workflow brief for repositories and synchronized consumers (#147)

### Changed

- Retry failed GitHub Actions jobs once when failed workflow logs match transient GitHub-side checkout or transport errors (#175)
- Teach the review and pull-request agent skills to treat workflow-managed wiki pointer updates as expected state and to prefer fresh follow-up issues plus PRs over reviving closed deleted branches (#147)
- Require GitHub issue write readback verification in the github-issues skill (#165)
- Standardize cache flags and nested cache-dir propagation across cache-aware commands (#162)
- Add `GITHUB_STEP_SUMMARY` output to tests, reports, wiki, and changelog workflows for clearer final-state workflow results (#148)

## [1.18.0] - 2026-04-23

### Changed

- Consolidate repository-local DevTools caches under `.dev-tools/cache`, keep published reports free of cache directories, and audit managed artifact path defaults across commands, workflows, and documentation (#151)
- Refresh repository mascot artwork (#166)

## [1.17.1] - 2026-04-22

### Fixed

- Keep the packaged pull-request label-sync action from failing when a PR does not reference any linked issue (#157)

## [1.17.0] - 2026-04-22

### Added

- Add structured command output across the DevTools command surface with `--json` and `--pretty-json`, including pretty-printed JSON and agent-aware JSON defaults (#33)
- Group queued subprocess output and emit GitHub Actions workflow annotations for clearer CI logs and error surfaces (#33)

### Changed

- Propagate structured output flags through nested DevTools subprocesses, simplify logger context extraction, and keep machine-readable runs quieter by default (#33)
- Adopt `--progress` as the positive opt-in for transient progress rendering while keeping supported commands quiet by default (#33)
- Teach packaged issue-authoring skills and prompts to reuse existing GitHub issue metadata, infer project field values, preserve relationships with open issues, and mirror linked issue project metadata onto pull requests (#152)
- Automate the configured GitHub Project board across issue intake, PR review, merge, release preparation, and changelog-driven release publication using repository-level Project variables (#152)
- Reorganize packaged GitHub Actions documentation to reflect the local `.github/actions` groups for `php`, `project-board`, `github-pages`, `wiki`, and `changelog`, plus the split between wiki preview and wiki maintenance workflows (#152)

### Fixed

- Stabilize logger and process-queue test expectations in CI by making GitHub Actions output detection deterministic during the PHPUnit suite (#33)
- Restore raw text output for `changelog:next-version` and `changelog:show` so changelog release workflows can keep capturing versions and redirecting release notes safely (#149)

## [1.16.0] - 2026-04-20

### Changed

- Skip pull-request changelog entry validation for generated release branches so release PRs can promote `Unreleased` without failing CI (#138)
- Restore dependency workflow documentation so README, AGENTS, and command guides match the required CI dependency-health behavior (#138)
- Require the GitHub pull-request skill to refresh `main` from the remote before branching for a new implementation so changelog and release state start from the latest baseline (#138)

## [1.15.0] - 2026-04-20

### Changed

- Consolidate dependency analysis on `composer-dependency-analyser`, add a reusable packaged analyzer config, remove the redundant `composer-unused` dependency, and expose `--dump-usage` plus report-only `--max-outdated=-1` support (#135)

## [1.14.0] - 2026-04-20

### Added

- Add CODEOWNERS generation and sync support for consumer repositories (#67)
- Package a create-agentsmd skill for repository-level AGENTS.md authoring (#128)
- Add an agents-maintainer project agent for ongoing AGENTS.md upkeep (#128)
- Synchronize packaged project-agent prompts into consumer repositories (#130)

## [1.13.0] - 2026-04-19

### Added

- Add community health files plus issue and pull request templates for contributors (#37)

### Changed

- Document required GitHub Actions permissions for changelog release automation (#118)
- Add the project mascot image to README and documentation (#121)
- Upgrade the packaged phpDocumentor bootstrap template to v2.0.0 (#121)
- Refresh outdated console bootstrap, sync, and dependency-injection documentation (#123)

## [1.12.0] - 2026-04-19

### Added

- Add Keep a Changelog management commands and release automation workflows (#28).
- Package changelog maintenance skills, docs, and the changelog-maintainer project agent (#28).

### Changed

- Force workflow colors through FORCE_COLOR across packaged GitHub Actions for clearer CI logs (#28).

## [1.11.0] - 2026-04-19

### Added

- Ship role-based project agents for issue implementation, docs, README, tests, and changelog work (#75) (#109).
- Show diffs for overwritten synchronized resources and add preview/check modes to dev-tools:sync (#66) (#110) (#62) (#111).
- Verify deployed reports health, synchronize funding metadata, and infer workflow PHP versions from project metadata (#70) (#112) (#56) (#113) (#76) (#114).

### Fixed

- Package the PHP version resolver action from resources/ so GitHub Actions can load it correctly (#115) (#116).

## [1.10.0] - 2026-04-19

### Changed

- Refresh dependency and reporting docs to match the shipped commands and workflows (#105) (#106).
- Move generated outputs into .dev-tools by default instead of public/ (#107) (#108).

### Fixed

- Preserve Git history in metrics previews so PhpMetrics reports show meaningful contributor and file history (#103) (#104).

## [1.9.0] - 2026-04-19

### Added

- Generate PhpMetrics reports for consumer repositories (#98).
- Add a Jack-powered dependency workflow to the dependencies command (#34) (#102).

### Changed

- Unify metrics outputs under a target directory (#99) (#100).

### Fixed

- Address code quality findings and sync safety regressions in command and test code (#94) (#95) (#96) (#97).

## [1.8.0] - 2026-04-18

### Added

- Canonicalize packaged Git hooks during synchronization (#92) (#93).

### Changed

- Isolate Finder creation behind a factory to make filesystem traversal extensible (#90) (#91).

## [1.7.0] - 2026-04-18

### Added

- Publish branch protection, migration, troubleshooting, and release-publishing guidance for consumer repositories (#61) (#72) (#79) (#80).
- Synchronize README metadata during composer sync and expand PHPUnit output controls (#57) (#84).
- Validate and clean up wiki and reports preview branches in packaged GitHub Actions workflows (#68) (#69) (#83).

### Changed

- Reduce default GitHub Actions token permissions across packaged workflows (#63).

## [1.6.0] - 2026-04-17

### Added

- Introduce copy-resource, update-composer-json, and git-hooks commands plus packaged funding and support metadata.
- Publish pull request Pages previews for reports and document the consumer preview workflow (#54) (#55).

### Changed

- Refactor command architecture around filesystem abstractions, resource copy and update commands, and dependency-injected process handling (#46).

## [1.5.0] - 2026-04-14

### Added

- Package pull request auto-assign and label-sync GitHub Actions workflows (#35).

### Changed

- Standardize Composer installation and caching across packaged reports, tests, and wiki workflows.
- Reorganize commands into the Console namespace and migrate to the GrumPHP shim (#42) (#44).

### Fixed

- Make wiki publishing work under branch protection and synced submodule pointer updates.

## [1.4.0] - 2026-04-11

### Changed

- Replace external coverage-check tooling with native PHPUnit coverage summary validation (#30) (#31).

### Fixed

- Update Symfony component constraints to support Symfony 8.0 (#31).

## [1.3.0] - 2026-04-11

### Added

- Manage .gitattributes export-ignore rules through dedicated gitattributes tooling (#27).

### Changed

- Exclude context7.json from packaged exports and broaden GitAttributes test coverage (#27).

## [1.2.2] - 2026-03-26

### Added

- Bundle GrumPHP support into packaged scripts and installation commands.
- Reuse packaged GitHub Actions workflows and synchronize .editorconfig and wiki submodule assets during setup.

### Fixed

- Resolve packaged workflow and wiki resource paths more reliably during script synchronization.

## [1.2.1] - 2026-04-10

### Changed

- Refine license-generation documentation and export-ignore defaults (#26).

## [1.2.0] - 2026-04-10

### Added

- Synchronize packaged skills into consumer repositories with the skills command (#23).
- Bundle dependency analysis tooling and the dependencies command (#10).
- Generate repository LICENSE files through dev-tools:sync and the license command (#25).

## [1.1.0] - 2026-04-09

### Added

- Package reusable skills for Sphinx docs, README maintenance, PHPUnit, and GitHub issue workflows.
- Synchronize .gitignore files through dedicated gitignore tooling (#21).

### Changed

- Make ECS and Rector configuration extensible for consumer overrides (#19).

## [1.0.4] - 2026-03-26

### Fixed

- Resolve DocsCommand configuration files correctly from relative paths.

## [1.0.3] - 2026-03-26

### Changed

- Verify the consumer package name before installing packaged scripts.

## [1.0.2] - 2026-03-26

### Fixed

- Move the composer/composer dependency into the required package metadata.

## [1.0.1] - 2026-03-26

### Added

- Introduce the unified Composer plugin command suite for tests, docs, reports, wiki publishing, and packaged script installation.

### Fixed

- Improve GitHub Actions and reports deployment handling for TTY, coverage, and Pages publishing.

## [1.0.0] - 2026-04-08

### Added

- Rename the installation workflow to dev-tools:sync and synchronize Dependabot, GitHub Actions, and repository defaults.
- Support PHPUnit test filtering, phpDocumentor bootstrap templates, and richer test notifications.

### Changed

- Standardize README badges, funding metadata, and documentation references for the packaged plugin.

### Fixed

- Normalize workflow PHP extension setup and Git submodule path handling for synced repositories.


[unreleased]: https://github.com/php-fast-forward/dev-tools/compare/v1.20.0...HEAD
[1.20.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.19.0...v1.20.0
[1.19.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.18.0...v1.19.0
[1.18.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.17.1...v1.18.0
[1.17.1]: https://github.com/php-fast-forward/dev-tools/compare/v1.17.0...v1.17.1
[1.17.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.16.0...v1.17.0
[1.16.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.15.0...v1.16.0
[1.15.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.14.0...v1.15.0
[1.14.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.13.0...v1.14.0
[1.13.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.12.0...v1.13.0
[1.12.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.11.0...v1.12.0
[1.11.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.10.0...v1.11.0
[1.10.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.9.0...v1.10.0
[1.9.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.8.0...v1.9.0
[1.8.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.7.0...v1.8.0
[1.7.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.5.0...v1.6.0
[1.5.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.2.2...v1.3.0
[1.2.2]: https://github.com/php-fast-forward/dev-tools/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/php-fast-forward/dev-tools/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.0.4...v1.1.0
[1.0.4]: https://github.com/php-fast-forward/dev-tools/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/php-fast-forward/dev-tools/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/php-fast-forward/dev-tools/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/php-fast-forward/dev-tools/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/php-fast-forward/dev-tools/releases/tag/v1.0.0

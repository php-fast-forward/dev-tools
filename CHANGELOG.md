# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add a hybrid command runtime bootstrap and capability bridge that keeps command discovery split between migrated Symfony commands (`DevTools`) and legacy Composer `BaseCommand` commands (`DevToolsComposer`) while exposing proxy commands during Composer execution for the first migration step (#199)

### Changed

- Align the standards pipeline to invoke PHPDoc checks via the `php-cs-fixer` command name/alias and route standards cache to `.dev-tools/cache/php-cs-fixer`, preserving backwards compatibility for existing `dockblock`-style entry points.
- Rename the command entry point from `standards:phpdoc` to `standards:dockblock` and keep both `dockblock` and `php-cs-fixer` aliases for compatibility (`standards:phpdoc` is intentionally not kept as a command name).

## [1.22.3] - 2026-04-25

### Fixed

- Add a branch-protection-safe changelog validation context that fails normal
- Teach pull-request publication guidance to read the published PR body back
- Keep workflow-dispatched test runs from failing before job creation by treating `max-outdated` as a string input and relying on the workflow default when release, wiki, or conflict automation dispatches required test status mirroring.

## [1.22.2] - 2026-04-24

### Fixed

- Dispatch required test status mirroring after changelog release-preparation workflows create or update release pull requests so release branches no longer require branch-protection bypass for workflow-authored commits (#250)

## [1.22.1] - 2026-04-24

### Fixed

- Promote any remaining project-board `Merged` work to `Released` during release publication so bypassed or skipped preparation transitions do not leave completed items stale (#253)
- Grant project-board write permission in the packaged changelog workflow wrapper so consumer release workflows can call the reusable changelog automation without GitHub rejecting the requested permissions (#251)

## [1.22.0] - 2026-04-24

### Added

- Auto-resolve pull-request conflicts limited to workflow-managed `.github/wiki` pointers and `CHANGELOG.md` `Unreleased` drift (#192)
- Teach the pull-request review skill, review-guardian agent, and review request brief to require explicit validation strategies for workflow, local-action, and packaged-wrapper changes, including local `actionlint` installation guidance (#241)

### Fixed

- Preserve color-friendly nested command environments, explicit Symfony Console ANSI flags, concise process section labels, and fixture-safe PhpMetrics execution with bounded Packagist lookups without restoring PTY (#239)
- Disable Xdebug for queued child processes unless coverage requires it without PCOV, reducing repeated Composer Xdebug warnings in orchestrated commands (#239)
- Keep the reports workflow permission warning loop shell-safe for paths containing backslashes (#244)
- Keep required PHPUnit matrix checks reporting after workflow-managed `.github/wiki` pointer commits by running the pull-request test workflow without top-level path filters and aligning the packaged consumer test wrapper (#230)
- Publish pending and per-version required PHPUnit statuses for workflow-dispatched test runs so wiki pointer commits do not wait for an all-matrix aggregate status (#230)
- Ignore intentional Composer Dependency Analyser shadow dependency findings by default while adding `dependencies --show-shadow-dependencies` for audits (#233)
- Dispatch the required test workflow after wiki preview automation updates a pull-request `.github/wiki` pointer, avoiding permanently pending required checks on bot-authored pointer commits (#230)
- Mirror workflow-dispatched wiki pointer test results into required `Run Tests` commit statuses so branch protection recognizes bot-authored pointer commits (#230)

## [1.21.0] - 2026-04-24

### Changed

- GitHub Actions(deps): Bump peter-evans/create-pull-request from 7 to 8 (#181)
- GitHub Actions(deps): Bump marocchino/sticky-pull-request-comment from 2 to 3 (#182)
- GitHub Actions(deps): Bump actions/github-script from 8 to 9 (#183)
- Auto-create and push minimal changelog entries for same-repository Dependabot pull requests before changelog validation reruns (#186)
- Resolve Dependabot changelog fallback state from the actual PR head branch and report `already-present`, `auto-created`, or `missing` in the workflow summary so rebased PRs cannot pass on inherited `Unreleased` entries alone (#191)

### Fixed

- Preserve literal angle brackets around maintainer emails when generating LICENSE files from composer metadata (#179)
- Keep packaged `.agents` payloads exportable and synchronize packaged skills and agents with repository-relative symlink targets so consumer repositories no longer receive broken absolute machine paths (#188)
- Rewrite drifted Git hooks by removing the previous target first, restore the intended `0o755` executable mode, and report unwritable hook replacements cleanly when `.git/hooks` stays locked (#190)
- Keep Composer plugin command discovery compatible with consumer environments by moving unsupported Symfony Console named parameters out of command metadata/configuration and by decoupling the custom filesystem wrapper from Composer's bundled Symfony Filesystem signatures (#185)
- Keep Composer autoload, Rector, and ECS from traversing nested fixture `vendor` directories when the composer-plugin consumer fixture has installed dependencies (#179)
- Skip LICENSE generation cleanly when a consumer composer manifest omits or leaves the `license` field empty (#227)
- Run nested DevTools subprocesses without forcing PTY, fixing aggregate commands in non-interactive environments (#171)

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


[unreleased]: https://github.com/php-fast-forward/dev-tools/compare/v1.22.3...HEAD
[1.22.3]: https://github.com/php-fast-forward/dev-tools/compare/v1.22.2...v1.22.3
[1.22.2]: https://github.com/php-fast-forward/dev-tools/compare/v1.22.1...v1.22.2
[1.22.1]: https://github.com/php-fast-forward/dev-tools/compare/v1.22.0...v1.22.1
[1.22.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.21.0...v1.22.0
[1.21.0]: https://github.com/php-fast-forward/dev-tools/compare/v1.20.0...v1.21.0
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

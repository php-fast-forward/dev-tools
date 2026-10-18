# FastForward\DevTools

FastForward DevTools is a Composer plugin that standardizes quality checks,
documentation builds, consumer repository bootstrap, and packaged agent skills
across Fast Forward libraries.

[![PHP Version](https://img.shields.io/badge/php-^8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fdev--tools-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/dev-tools)
[![Tests](https://img.shields.io/github/actions/workflow/status/php-fast-forward/dev-tools/tests.yml?logo=githubactions&logoColor=white&label=tests&color=22C55E)](https://github.com/php-fast-forward/dev-tools/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-phpunit-4ADE80?logo=php&logoColor=white)](https://php-fast-forward.github.io/dev-tools/coverage/index.html)
[![Metrics](https://img.shields.io/badge/metrics-phpmetrics-8B5CF6?logo=php&logoColor=white)](https://php-fast-forward.github.io/dev-tools/metrics/index.html)
[![Docs](https://img.shields.io/github/deployments/php-fast-forward/dev-tools/github-pages?logo=readthedocs&logoColor=white&label=docs&labelColor=1E293B&color=38BDF8&style=flat)](https://php-fast-forward.github.io/dev-tools/index.html)
[![License](https://img.shields.io/github/license/php-fast-forward/dev-tools?color=64748B)](LICENSE)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/php-fast-forward?logo=githubsponsors&logoColor=white&color=EC4899)](https://github.com/sponsors/php-fast-forward)

<p align="center">
  <img src="docs/_static/mascot-banner.png" alt="Dash coordinating tests, refactoring, and documentation at a multi-monitor DevTools workstation" width="840">
</p>

## ✨ Features

- Aggregates refactoring, PHPDoc, code style, tests, and reporting under a
  single Composer-facing command vocabulary
- Adds dependency analysis for missing and unused Composer packages through a
  single report entrypoint
- Delegates fragment authoring and Keep a Changelog release automation to
  the independent Fast Forward Changelog package
- Ships shared workflow stubs, `.editorconfig`, Dependabot configuration, and
  other onboarding defaults for consumer repositories
- Packages a rigorous pull-request review skill, matching `review-guardian`
  project agent, and ready-for-review workflow brief for high-signal review
  intake
- Generates `.github/CODEOWNERS` files from local project metadata instead of
  shipping repository-specific owners into consumers
- Synchronizes packaged skills and project-agent prompts into consumer
  `.agents/skills` and `.agents/agents` directories using safe link-based
  updates
- Supports guide-only and automation-only repositories by skipping PHPUnit or
  wiki generation gracefully when no runnable PHP surface exists
- Works both as a Composer plugin and as a local binary
- Preserves local overrides through consumer-first configuration resolution

## 🚀 Installation

```bash
composer require --dev fast-forward/dev-tools
```

## 🛠️ Usage

Once installed, the plugin automatically exposes the aggregated `dev-tools`
command and the individual Composer commands described below.

```bash
# Run all standard checks (refactoring, code styling, docs, tests, and reports)
composer dev-tools

# Automatically fix code standards issues where applicable
composer dev-tools:fix

# Run the standalone binary from another project directory
vendor/bin/dev-tools --working-dir=/path/to/project tests

# Store generated reports and caches outside the default .dev-tools workspace
vendor/bin/dev-tools --workspace-dir=.artifacts reports

# Update the installed DevTools package
vendor/bin/dev-tools self-update
```

You can also run individual commands for specific development tasks:

```bash
# Run PHPUnit tests
composer dev-tools tests
composer dev-tools tests --json
composer dev-tools tests --pretty-json

# Analyze missing, unused, misplaced, and outdated Composer dependencies
composer dependencies
composer dependencies --max-outdated=8
composer dependencies --max-outdated=-1
composer dependencies --dev
composer dependencies --dump-usage=symfony/console
composer dependencies --upgrade --dev

# Analyze code metrics with PhpMetrics
composer metrics
composer metrics --target=.dev-tools/metrics
composer --working-dir=packages/example metrics

# Create and validate an independent change fragment (CPX and PHP 8.5 required)
cpx --skip-local fast-forward/changelog:0.2.0 add "Fixes release-note rendering." --category=fixed --no-interaction
cpx --skip-local fast-forward/changelog:0.2.0 check --no-interaction

# Check and fix code style using ECS and Composer Normalize
composer code-style

# Refactor code using Rector
composer refactor

# Check and fix PHPDoc comments
composer phpdoc

# Generate HTML API documentation using phpDocumentor
composer docs
composer docs --source=docs/user-guide

# Generate Markdown documentation for the wiki
composer wiki

# Generate documentation frontpage and related reports
composer reports
composer reports --target=.dev-tools --coverage=.dev-tools/coverage
FAST_FORWARD_WORKSPACE_DIR=.artifacts composer reports

# Synchronize packaged agent skills into .agents/skills
composer skills

# Synchronize packaged project agents into .agents/agents
composer agents

# Synchronize Composer funding metadata with .github/FUNDING.yml
composer funding
composer funding --dry-run

# Generate .github/CODEOWNERS from composer.json metadata
composer codeowners
composer codeowners --interactive

# Merges and synchronizes .gitignore files
composer gitignore

# Manages .gitattributes export-ignore rules for leaner package archives
composer gitattributes

# Generates a LICENSE file from composer.json license information
composer license

# Copies packaged or local resources into the consumer repository
composer copy-resource --source resources/docblock --target .docheader

# Installs Fast Forward Git hooks
composer git-hooks

# Updates the composer.json file to match the packaged schema
composer update-composer-json --force

# Installs and synchronizes dev-tools scripts, GitHub Actions workflows,
# .editorconfig, .gitignore rules, packaged skills, packaged agents, and the
# local wiki Markdown in .dev-tools/wiki-generated
composer dev-tools:sync
```

The `dependencies` command ships with
`shipmonk/composer-dependency-analyser` and `rector/swiss-knife` as direct
dependencies of `fast-forward/dev-tools`, so it works without extra
installation in the consumer project.

The packaged `tests.yml` workflow runs dependency health as a required job and
defaults to `--max-outdated=-1`, which keeps outdated-package findings visible
in CI without failing the workflow for their count alone.

The `metrics` command ships with `phpmetrics/phpmetrics` as a direct
dependency of `fast-forward/dev-tools`, so consumer repositories can generate
metrics reports without extra setup.

Guide-only repositories and workflow-only repositories can still use the
packaged command surface. When no runnable PHPUnit surface exists, `tests`
returns a controlled warning instead of failing. When a repository ships
guides without PSR-4 source paths, `docs` builds the guide site without trying
to synthesize API pages. When `.github/wiki` is absent and no wiki has been
initialized yet, `wiki` now skips generation with a warning instead of failing
the whole automation run.

Normal `dev-tools:sync` generates local Markdown in `.dev-tools/wiki-generated`
without creating a Git submodule. The packaged `wiki.yml` workflow publishes
generated API pages directly to the GitHub Wiki from `main`; pull requests
generate a read-only artifact. Existing workflow wrappers require an explicit
migration or `composer dev-tools:sync --overwrite` to adopt these changes. See
[the workflow guide](docs/usage/github-actions.rst) for publication and page
ownership details.

Run the independent CLI with `cpx --skip-local fast-forward/changelog:0.2.0`.
Install CPX separately and run it with PHP 8.5; the consumer package still supports PHP
8.3. Changelog is isolated from the consumer Composer dependency graph.
Contributions add `.changelog/*.md`; reviewed version PRs consolidate them
into `CHANGELOG.md`. Do not add an `Unreleased` section. See the
[changelog guide](docs/commands/changelog.rst) for prerequisites and previews.
Require the external workflow's exact `Fast Forward Changelog` commit status
on the PR head, after verifying it in a repository pilot.

Structured output is available across the DevTools command surface through
`--json`, which returns deterministic `message` / `level` / `context` payloads
for CI, bots, and AI-agent workflows while preserving the normal
human-readable terminal output by default. Use `--pretty-json` when you want
the same structured payload indented for manual inspection in a terminal.
`--pretty-json` also implies JSON output, so there is no need to pass both
flags together. In agent environments, DevTools can also switch to JSON
automatically when the runtime is detected as agent-driven. The external
Changelog CLI has its own output and option contracts; consult its command help.

`--pretty-json` intentionally remains valid JSON. DevTools does not inject ANSI
escape sequences into that mode today because preserving a parseable payload
takes precedence over terminal-only color. Where orchestrated tools can expose
structured subprocess results safely, DevTools prefers adding stable fields to
the JSON context rather than coloring otherwise strict JSON output.

For the `tests` command, structured runs now capture the bundled PHPUnit
agent-reporter payload inside `context.output` while preserving the normal
DevTools JSON envelope. That means the top-level command still emits one final
document, and consumers can inspect stable nested keys such as
`context.output.result`, `context.output.summary`, optional
`context.output.details`, and `context.output.coverage` when minimum-coverage
validation is active.

Progress output is disabled by default on the commands that support transient
rendering, and `--progress` re-enables it for human-readable terminal runs.
When `--json` or `--pretty-json` is active on commands that orchestrate other
tools, DevTools keeps progress suppressed, forwards JSON flags where the
underlying tool supports structured output, and otherwise falls back to
quieter subprocess modes so the captured payload stays machine-readable. The
`tests` command now captures the bundled PHPUnit agent-reporter payload in
structured runs and stores it in `context.output`, preserving `result`,
`summary`, optional `details`, and a `raw_output` fallback when coverage or
other PHPUnit text is emitted before the final reporter JSON. In GitHub
Actions, queued subprocess output is
grouped into collapsible sections, and logged failures emit native workflow
error annotations, including file and line metadata when commands provide it.
The packaged tests, reports, wiki, and changelog workflows also append concise
Markdown outcomes to
`GITHUB_STEP_SUMMARY` so maintainers can scan versions, URLs, preview refs,
verification status, and release results without expanding full logs. This
repository also keeps a bounded retry workflow that reruns failed jobs once
when failed job logs match transient GitHub-side checkout or transport errors
such as HTTP 500 fetch failures, while leaving genuine logic and quality
failures untouched.

The packaged changelog caller uses
`php-fast-forward/changelog/.github/workflows/changelog.yml@v0`.
The `v0` channel follows published 0.x releases and can include breaking minor
updates. This repository selects that channel explicitly; local CPX examples
use the verified exact 0.2.0 CLI. Recheck compatibility before channel updates.
GitHub automation uses its own Docker/PHP runtime and requires Linux with
Docker, Bash and Git, independently of the consumer PHP version.

The `funding` command keeps supported `composer.json` funding entries aligned
with `.github/FUNDING.yml`, including GitHub Sponsors handles and `custom`
URLs, while preserving unsupported providers in place and re-running
`composer normalize` after manifest updates.

The `codeowners` command generates `.github/CODEOWNERS` from local
`composer.json` metadata. It prefers explicit GitHub profile URLs from author
metadata, falls back to commented suggestions from support metadata, and can
prompt for owners when `--interactive` is used in a terminal.

The `skills` command keeps `.agents/skills` aligned with the packaged Fast
Forward skill set. It creates missing links, repairs broken links, and
preserves existing non-symlink directories. The `dev-tools:sync` command calls
`skills` automatically after refreshing the rest of the consumer-facing
automation assets. The packaged skills include reusable flows for GitHub issue
authoring, issue implementation, changelog maintenance, and rigorous pull
request review.

The `agents` command keeps `.agents/agents` aligned with the packaged Fast
Forward project-agent prompt set. It follows the same symlink-preservation and
broken-link-repair safety model as the packaged skill synchronization flow, and
`dev-tools:sync` calls it automatically in normal synchronization mode. The
packaged agent set includes role prompts such as `issue-implementer`,
`docs-writer`, `test-guardian`, `readme-maintainer`, `review-guardian`, and
`changelog-maintainer`.

The packaged workflow stubs synchronized by `dev-tools:sync` now also include
changelog automation for pull-request validation and release preparation, so
consumer repositories can adopt the same changelog-driven release flow without
copying workflow logic by hand. They also include a rigorous review intake
workflow that reacts when a pull request becomes ready for review and posts a
deterministic brief for the dedicated review agent.

The external workflow checks fragments and prepares one managed version PR
on `changelog/version`, titled `chore: update changelog`. This repository uses
the official README caller with the explicitly approved
`name: Fast Forward Changelog` and `publish: true` settings: `main`, history
from tags, feedback on every PR and release preparation
enabled. The lifecycle uses its default Ubuntu runner and GitHub token.
Merging the reviewed managed version PR automatically publishes its tag and
GitHub Release from the exact approved commit. The workflow never merges that
PR itself; see the
[workflow guide](docs/usage/github-actions.rst).

This repository also keeps role-based project agents in `.agents/agents`. They
are packaged for consumer repositories alongside `.agents/skills`, so
downstream projects can adopt both reusable role prompts and the procedural
skills they depend on.

## 🧰 Command Summary

| Command | Purpose |
|---------|---------|
| `composer dev-tools` | Runs the full `standards` pipeline. |
| `composer tests` | Runs PHPUnit with local-or-packaged configuration. |
| `composer dependencies` | Previews Rector Swiss Knife dependency updates, then reports missing, unused, misplaced, and outdated Composer dependencies. |
| `composer metrics` | Runs PhpMetrics for the current project and generates requested report artifacts. |
| `composer docs` | Builds the HTML documentation site from PSR-4 code and `docs/`. |
| `composer agents` | Creates or repairs packaged project-agent links in `.agents/agents`. |
| `composer skills` | Creates or repairs packaged skill links in `.agents/skills`. |
| `composer funding` | Synchronizes managed funding metadata between `composer.json` and `.github/FUNDING.yml`. |
| `composer codeowners` | Generates managed `.github/CODEOWNERS` content from local repository metadata. |
| `composer gitattributes` | Manages export-ignore rules in `.gitattributes`. |
| `composer dev-tools:sync` | Updates scripts, CODEOWNERS, funding metadata, workflow stubs, `.editorconfig`, `.gitignore`, `.gitattributes`, local wiki Markdown, packaged skills, and packaged agents. |
| `vendor/bin/dev-tools self-update` / `composer dev-tools:self-update` | Updates the local DevTools package, or the Composer global installation when the active binary is globally installed. |

`--working-dir`/`-d` changes the project root used by the standalone binary.
`--workspace-dir`/`-w` changes where generated DevTools artifacts and caches are
written when command-specific paths are omitted. Composer executions can use
`FAST_FORWARD_WORKSPACE_DIR=.artifacts composer reports` for the same workspace
policy, while explicit options such as `--target`, `--coverage`, `--metrics`,
and `--cache-dir` continue to take precedence.

## 🔌 Integration

DevTools integrates with consumer repositories in two ways. The Composer plugin
exposes the command set automatically after installation, and the local binary
keeps the same command vocabulary when you prefer running tools directly from
`vendor/bin/dev-tools`. The consumer sync flow also refreshes `.agents/skills`
and `.agents/agents` so agents can discover the packaged skills and packaged
role prompts shipped with this repository, including workflows for GitHub
issue/PR handling, changelog maintenance, rigorous pull-request review, PHP
quality tasks, Sphinx docs, README generation, and repository `AGENTS.md`
authoring.

## 🏗️ Architecture

- `Composer Plugin` - `FastForward\DevTools\Composer\Plugin` exposes the
  packaged command set to Composer and runs `dev-tools:sync` after install and
  update.
- `DevTools Container` - `FastForward\DevTools\Container\ContainerFactory::get(FastForward\DevTools\Console\DevTools::class)`
  resolves the shared application from `DevToolsServiceProvider`, which wires
  process execution, filesystem access, external tool wrappers, Git helpers,
  diffing, reporting, and template loading.
- `Generic Link Synchronization` -
  `FastForward\DevTools\Sync\PackagedDirectorySynchronizer` provides the
  shared symlink-preserving workflow used by both `skills` and `agents`,
  keeping consumer-owned directories intact while repairing broken links.
- `Lazy Command Loading` - `DevToolsCommandLoader` discovers `#[AsCommand]`
  classes and resolves most commands only when they are invoked, while
  orchestration commands such as `standards` dispatch other commands through
  the console application itself.
- `Consumer Sync Pipeline` - `dev-tools:sync` refreshes `composer.json`,
  CODEOWNERS, funding metadata, workflow stubs, repository defaults, git
  metadata files, packaged Git hooks, and, in normal mode, local wiki Markdown
  plus packaged skills and packaged project agents.

## 🤝 Contributing

Run `composer dev-tools` before opening a pull request. If you change public
commands or consumer onboarding behavior, update `README.md` and `docs/`
together so downstream libraries keep accurate guidance.

Notable pull requests are also expected to add a change fragment before
review is complete. A typical contributor flow looks like this:

```bash
# Record the user-visible change
cpx --skip-local fast-forward/changelog:0.2.0 add "Refines release-note rendering." --category=changed --no-interaction

# Validate fragments; use --since after committing the contribution
cpx --skip-local fast-forward/changelog:0.2.0 check --since=origin/main --no-interaction
```

Maintainers can inspect pending fragments without consuming them:

```bash
cpx --skip-local fast-forward/changelog:0.2.0 status --source=tags --no-interaction
```

Use the managed version PR for consolidation. Test history-writing commands
only in a disposable checkout; they can consume accepted fragments. PR origin
resolution is enabled in automation and does not require editing a fragment
after opening its PR. Unknown PR numbers stay absent.

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## 🔗 Links

- [Repository](https://github.com/php-fast-forward/dev-tools)
- [Packagist](https://packagist.org/packages/fast-forward/dev-tools)
- [Documentation](https://php-fast-forward.github.io/dev-tools/index.html)
- [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119)

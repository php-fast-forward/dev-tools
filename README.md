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

## ✨ Features

- Aggregates refactoring, PHPDoc, code style, tests, and reporting under a
  single Composer-facing command vocabulary
- Adds dependency analysis for missing and unused Composer packages through a
  single report entrypoint
- Ships shared workflow stubs, `.editorconfig`, Dependabot configuration, and
  other onboarding defaults for consumer repositories
- Synchronizes packaged agent skills into consumer `.agents/skills`
  directories using safe link-based updates
- Works both as a Composer plugin and as a local binary
- Preserves local overrides through consumer-first configuration resolution

## 🚀 Installation

```bash
composer require --dev fast-forward/dev-tools:dev-main
```

## 🛠️ Usage

Once installed, the plugin automatically exposes the aggregated `dev-tools`
command and the individual Composer commands described below.

```bash
# Run all standard checks (refactoring, code styling, docs, tests, and reports)
composer dev-tools

# Automatically fix code standards issues where applicable
composer dev-tools:fix
```

You can also run individual commands for specific development tasks:

```bash
# Run PHPUnit tests
composer dev-tools tests

# Analyze missing, unused, and outdated Composer dependencies
composer dependencies
composer dependencies --max-outdated=8
composer dependencies --dev
composer dependencies --upgrade --dev

# Analyze code metrics with PhpMetrics
composer metrics
composer metrics --target=.dev-tools/metrics
composer --working-dir=packages/example metrics

# Check and fix code style using ECS and Composer Normalize
composer code-style

# Refactor code using Rector
composer refactor

# Check and fix PHPDoc comments
composer phpdoc

# Generate HTML API documentation using phpDocumentor
composer docs

# Generate Markdown documentation for the wiki
composer wiki

# Generate documentation frontpage and related reports
composer reports
composer reports --target=.dev-tools --coverage=.dev-tools/coverage

# Synchronize packaged agent skills into .agents/skills
composer skills

# Synchronize Composer funding metadata with .github/FUNDING.yml
composer funding
composer funding --dry-run

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
# .editorconfig, .gitignore rules, packaged skills, and the repository wiki
# submodule in .github/wiki
composer dev-tools:sync
```

The `dependencies` command ships with both dependency analyzers and
`rector/jack` as direct
dependencies of `fast-forward/dev-tools`, so it works without extra
installation in the consumer project.

The `metrics` command ships with `phpmetrics/phpmetrics` as a direct
dependency of `fast-forward/dev-tools`, so consumer repositories can generate
metrics reports without extra setup.

The `funding` command keeps supported `composer.json` funding entries aligned
with `.github/FUNDING.yml`, including GitHub Sponsors handles and `custom`
URLs, while preserving unsupported providers in place and re-running
`composer normalize` after manifest updates.

The `skills` command keeps `.agents/skills` aligned with the packaged Fast
Forward skill set. It creates missing links, repairs broken links, and
preserves existing non-symlink directories. The `dev-tools:sync` command calls
`skills` automatically after refreshing the rest of the consumer-facing
automation assets.

This repository also keeps role-based project agents in `.agents/agents`. They
are mirrored through `.github/agents` for GitHub-oriented discovery, while the
packaged `.agents/skills` directory remains the consumer-facing procedural
source of truth.

## 🧰 Command Summary

| Command | Purpose |
|---------|---------|
| `composer dev-tools` | Runs the full `standards` pipeline. |
| `composer tests` | Runs PHPUnit with local-or-packaged configuration. |
| `composer dependencies` | Previews Jack dependency updates, then reports missing, unused, and overly outdated Composer dependencies. |
| `composer metrics` | Runs PhpMetrics for the current project and generates requested report artifacts. |
| `composer docs` | Builds the HTML documentation site from PSR-4 code and `docs/`. |
| `composer skills` | Creates or repairs packaged skill links in `.agents/skills`. |
| `composer funding` | Synchronizes managed funding metadata between `composer.json` and `.github/FUNDING.yml`. |
| `composer gitattributes` | Manages export-ignore rules in `.gitattributes`. |
| `composer dev-tools:sync` | Updates scripts, funding metadata, workflow stubs, `.editorconfig`, `.gitignore`, `.gitattributes`, wiki setup, and packaged skills. |

## 🔌 Integration

DevTools integrates with consumer repositories in two ways. The Composer plugin
exposes the command set automatically after installation, and the local binary
keeps the same command vocabulary when you prefer running tools directly from
`vendor/bin/dev-tools`. The consumer sync flow also refreshes `.agents/skills`
so agents can discover the packaged skills shipped with this repository.

## 🏗️ Architecture

Each command is self-contained and receives its dependencies through constructor injection, following the :abbr:`DI` pattern. The ``ProcessBuilder`` and ``ProcessQueue`` classes provide a fluent API for constructing and executing system processes in sequence.

- ``ProcessBuilderInterface`` - Builds process commands with arguments
- ``ProcessQueueInterface`` - Manages and executes process queues
- ``FilesystemInterface`` - Abstracts filesystem operations

## 🤝 Contributing

Run `composer dev-tools` before opening a pull request. If you change public
commands or consumer onboarding behavior, update `README.md` and `docs/`
together so downstream libraries keep accurate guidance.

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## 🔗 Links

- [Repository](https://github.com/php-fast-forward/dev-tools)
- [Packagist](https://packagist.org/packages/fast-forward/dev-tools)
- [Documentation](https://php-fast-forward.github.io/dev-tools/index.html)
- [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119)

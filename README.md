# FastForward\DevTools

FastForward DevTools is a Composer plugin that standardizes quality checks,
documentation builds, consumer repository bootstrap, and packaged agent skills
across Fast Forward libraries.

[![PHP Version](https://img.shields.io/badge/php-^8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fdev--tools-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/dev-tools)
[![Tests](https://img.shields.io/github/actions/workflow/status/php-fast-forward/dev-tools/tests.yml?logo=githubactions&logoColor=white&label=tests&color=22C55E)](https://github.com/php-fast-forward/dev-tools/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-phpunit-4ADE80?logo=php&logoColor=white)](https://php-fast-forward.github.io/dev-tools/coverage/index.html)
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

Once installed, the plugin automatically exposes the `dev-tools` command via Composer.

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

# Analyze missing and unused Composer dependencies
composer dependencies
vendor/bin/dev-tools dependencies

# Check and fix code style using ECS and Composer Normalize
composer dev-tools code-style

# Refactor code using Rector
composer dev-tools refactor

# Check and fix PHPDoc comments
composer dev-tools phpdoc

# Generate HTML API documentation using phpDocumentor
composer dev-tools docs

# Generate Markdown documentation for the wiki
composer dev-tools wiki

# Generate documentation frontpage and related reports
composer dev-tools reports

# Synchronize packaged agent skills into .agents/skills
composer dev-tools skills

# Merges and synchronizes .gitignore files
composer dev-tools gitignore

# Generates a LICENSE file from composer.json license information
composer dev-tools license

# Installs and synchronizes dev-tools scripts, GitHub Actions workflows,
# .editorconfig, .gitignore rules, packaged skills, and the repository wiki
# submodule in .github/wiki
composer dev-tools:sync
```

The `dependencies` command ships with both dependency analyzers as direct
dependencies of `fast-forward/dev-tools`, so it works without extra
installation in the consumer project.

The `skills` command keeps `.agents/skills` aligned with the packaged Fast
Forward skill set. It creates missing links, repairs broken links, and
preserves existing non-symlink directories. The `dev-tools:sync` command calls
`skills` automatically after refreshing the rest of the consumer-facing
automation assets.

## 🧰 Command Summary

| Command | Purpose |
|---------|---------|
| `composer dev-tools` | Runs the full `standards` pipeline. |
| `composer dev-tools tests` | Runs PHPUnit with local-or-packaged configuration. |
| `composer dev-tools dependencies` | Reports missing and unused Composer dependencies. |
| `composer dev-tools docs` | Builds the HTML documentation site from PSR-4 code and `docs/`. |
| `composer dev-tools skills` | Creates or repairs packaged skill links in `.agents/skills`. |
| `composer dev-tools:sync` | Updates scripts, workflow stubs, `.editorconfig`, `.gitignore`, wiki setup, and packaged skills. |

## 🔌 Integration

DevTools integrates with consumer repositories in two ways. The Composer plugin
exposes the command set automatically after installation, and the local binary
keeps the same command vocabulary when you prefer running tools directly from
`vendor/bin/dev-tools`. The consumer sync flow also refreshes `.agents/skills`
so agents can discover the packaged skills shipped with this repository.

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

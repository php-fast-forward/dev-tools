# FastForward\DevTools

[![PHP Version](https://img.shields.io/badge/php-^8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fdev--tools-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/dev-tools)
[![Tests](https://img.shields.io/github/actions/workflow/status/php-fast-forward/dev-tools/tests.yml?logo=githubactions&logoColor=white&label=tests&color=22C55E)](https://github.com/php-fast-forward/dev-tools/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-phpunit-4ADE80?logo=php&logoColor=white)](https://php-fast-forward.github.io/dev-tools/coverage/index.html)
[![Docs](https://img.shields.io/github/deployments/php-fast-forward/dev-tools/github-pages?logo=readthedocs&logoColor=white&label=docs&labelColor=1E293B&color=38BDF8&style=flat)](https://php-fast-forward.github.io/dev-tools/index.html)
[![License](https://img.shields.io/github/license/php-fast-forward/dev-tools?color=64748B)](LICENSE)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/php-fast-forward?logo=githubsponsors&logoColor=white&color=EC4899)](https://github.com/sponsors/php-fast-forward)

## ✨ Features

- Aggregates multiple development tools into a single command
- Automates execution of tests, static analysis, and code styling
- First-class support for automated refactoring and docblock generation
- Integrates seamlessly as a Composer plugin without boilerplate
- Configures default setups for QA tools out of the box

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

# Installs and synchronizes dev-tools scripts, GitHub Actions workflows, .editorconfig, and ensures the repository wiki is present as a git submodule in .github/wiki
composer dev-tools:sync
```

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## 🔗 Links

- [Repository](https://github.com/php-fast-forward/dev-tools)
- [Packagist](https://packagist.org/packages/fast-forward/dev-tools)
- [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119)

# FastForward\DevTools

A Composer plugin and console toolkit designed to unify and standardize development workflows. Built to provide a seamless interface for PHPUnit, PHP-CS-Fixer, Rector, EasyCodingStandard (ECS), and phpDocumentor.

## ✨ Features

- Aggregates multiple development tools into a single command
- Automates execution of tests, static analysis, and code styling
- First-class support for automated refactoring and docblock generation
- Integrates seamlessly as a Composer plugin without boilerplate
- Configures default setups for QA tools out of the box

## 🚀 Installation

```bash
composer require --dev fast-forward/dev-tools
```

## 🛠️ Usage

Once installed, the plugin automatically exposes the `dev-tools` command via Composer.

```bash
# Run all standard checks (refactoring, code styling, docs, tests, and reports)
composer dev-tools

# Automatically fix code standards issues where applicable
composer dev-tools -- --fix
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
```

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## 🔗 Links

- [Repository](https://github.com/php-fast-forward/dev-tools)
- [Packagist](https://packagist.org/packages/fast-forward/dev-tools)
- [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119)

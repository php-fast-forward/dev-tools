# AGENTS.md

## Project Overview

FastForward\DevTools is a Composer plugin that aggregates multiple PHP development tools into a single unified command. It provides automated execution of tests, static analysis, code styling, refactoring, and documentation generation.

**Key Technologies:**

- PHP 8.3+
- Composer plugin
- PHPUnit 12.5 (testing)
- Rector 2.x (automated refactoring)
- Easy Coding Standard (code style)
- phpDocumentor (API docs)
- GrumPHP (Git hooks)
- PHPSpec/Prophecy (test doubles)

## Setup Commands

```bash
# Install as development dependency
composer require --dev fast-forward/dev-tools:dev-main

# Install dependencies
composer install
```

## Development Workflow

```bash
# Run all standard checks (refactoring, code styling, docs, tests, reports)
composer dev-tools

# Automatically fix code standards issues
composer dev-tools:fix

# Individual commands
composer dev-tools tests         # Run PHPUnit tests
composer dev-tools code-style    # Check and fix code style (ECS + Composer Normalize)
composer dev-tools refactor      # Refactor code using Rector
composer dev-tools phpdoc         # Check and fix PHPDoc comments
composer dev-tools docs          # Generate HTML API documentation
composer dev-tools wiki         # Generate Markdown documentation for wiki
composer dev-tools reports       # Generate docs frontpage and reports
composer dev-tools:sync         # Sync scripts, GitHub Actions, .editorconfig, wiki
```

## Testing Instructions

```bash
# Run all tests with coverage
composer dev-tools tests

# Run tests matching a pattern
composer dev-tools tests -- --filter=CodeStyle

# Run with coverage report
composer dev-tools tests -- --coverage=public/coverage
```

**Test Configuration:**

- PHPUnit XML config: `phpunit.xml`
- Test namespace: `FastForward\DevTools\Tests\`
- Source namespace: `FastForward\DevTools\`
- Coverage required (strict metadata enforcement)
- Coverage threshold: configured in `phpunit.xml`

**Test Patterns:**

- Tests use PHPUnit 12.x with Prophecy for mocking
- Custom PHPUnit extension: `FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension`

**Creating/Updating Tests:**

- Use skill `phpunit-tests` in `.agents/skills/phpunit-tests/` for creating or updating PHPUnit tests with Prophecy
- Run skill when: creating new test classes, adding test methods, or fixing existing tests

## Code Style

**PHP Coding Standard:** PSR-12 with Symfony style

**ECS Configuration:** `ecs.php`

- Uses Symfony, PSR-12, and Symplify rule sets
- PHPDoc alignment: left-aligned
- PHPUnit test case static methods: use `self::`
- Skips: vendor, resources, public, tmp directories

**File Organization:**

- Source code: `src/` (PSR-4: `FastForward\DevTools\`)
- Tests: `tests/` (PSR-4: `FastForward\DevTools\Tests\`)
- Commands: `src/Command/`
- PHPUnit events: `src/PhpUnit/Event/`
- Rector rules: `src/Rector/`
- Composer plugin: `src/Composer/`

**Naming Conventions:**

- Classes: PascalCase
- Methods: camelCase
- Properties: camelCase
- Constants: UPPER_CASE

**PHPdoc Requirements:**

- All classes require docblocks with `@copyright` and `@license`
- Use RFC 2119 keywords (MUST, SHOULD, MAY, etc.)
- PHPDoc checked via dev-tools phpdoc command
- Use skill `phpdoc-code-style` in `.agents/skills/phpdoc-code-style/` for PHPDoc cleanup and repository-specific PHP formatting

## Build and Deployment

This is a Composer plugin - no build step required. The package is published to Packagist.

**Package Details:**

- Type: `composer-plugin`
- Class: `FastForward\DevTools\Composer\Plugin`
- Minimum stability: `stable`

## Pull Request Guidelines

**Title Format:** `[<area>] Brief description (#<issue-number>)`

**Required Checks:**

```bash
composer dev-tools
```

**Before Submitting:**

- Run full dev-tools suite
- Ensure all tests pass
- Code style must be clean (ECS)
- PHPDoc must be valid
- Coverage must meet threshold
- Rector should handle refactoring automatically

## Additional Notes

- **GrumPHP**: Automatically runs on pre-commit (configured in `grumphp.yml`)
- **Rector**: Custom rules in `src/Rector/` for automated refactoring
- **Documentation**: Sphinx-based docs in `docs/` directory
- **Wiki**: GitHub wiki synced via `dev-tools wiki` and `dev-tools:sync`
- **GitHub Actions**: Workflows in `.github/workflows/` (synced via `dev-tools:sync`)

## Skills Usage

- **Creating/Updating Tests**: Use skill `phpunit-tests` in `.agents/skills/phpunit-tests/` for PHPUnit tests with Prophecy
- **Generating Documentation**: Use skill `sphinx-docs` in `.agents/skills/sphinx-docs/` for Sphinx documentation in `docs/`
- **Updating README**: Use skill `package-readme` in `.agents/skills/package-readme/` for generating README.md files
- **Updating PHPDoc / PHP Style**: Use skill `phpdoc-code-style` in `.agents/skills/phpdoc-code-style/` for PHPDoc cleanup and repository-specific PHP formatting
- **Drafting / Publishing GitHub Issues**: Use skill `github-issues` in `.agents/skills/github-issues/` to transform a short feature description into a complete, production-ready GitHub issue and create or update it on GitHub when needed
- **Implementing Issues & PRs**: Use skill `github-pull-request` in `.agents/skills/github-pull-request/` to iterate through open GitHub issues and implement them one by one with branching, testing, documentation, and pull requests

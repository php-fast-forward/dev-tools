# Fast Forward Conventions

Use this file for repository-specific PHPDoc and formatting decisions.

## File Layout

- Repository PHP files normally begin with `<?php` and `declare(strict_types=1);`.
- Source files commonly use the standard Fast Forward file header before the namespace.
- The codebase targets PHP 8.5.
- Source namespaces live under `FastForward\\DevTools\\`; tests live under `FastForward\\DevTools\\Tests\\`.

## Standard Header Pattern

Many source and test files follow this header pattern:

```php
/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */
```

Preserve this shape when it already exists. When adding a missing header, match the repository pattern instead of inventing a simplified variant.

## Symbol-Level Style

- Concrete classes are frequently `final`.
- Interface and service-contract docblocks may use RFC 2119 wording, but only when the visible API supports it.
- Keep tag alignment left-justified and consistent with nearby files.
- Reuse existing terminology from commands, config classes, Rector rules, GitIgnore helpers, and PHPUnit extensions.

## Verification

For PHPDoc-focused changes, prefer:

```bash
composer dev-tools phpdoc
```

If the change is part of a larger implementation, it can be reasonable to run:

```bash
composer dev-tools
```

## Pairing

When a PHPDoc cleanup is part of a branch that will become a pull request, also iterate `github-pull-request` so the PR flow accounts for PHPDoc verification and summary.

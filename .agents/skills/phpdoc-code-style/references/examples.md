# Fast Forward PHPDoc Examples

## Concrete Class Example

Use the repository header when the file already follows that pattern, then keep the symbol docblock focused on responsibility and visible guarantees.

```php
<?php

declare(strict_types=1);

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

namespace FastForward\DevTools\GitIgnore;

/**
 * Classifies .gitignore entries as directory-oriented or file-oriented patterns.
 *
 * This classifier SHALL inspect a raw .gitignore entry and determine whether the
 * entry expresses directory semantics or file semantics.
 */
final class Classifier implements ClassifierInterface
{
    /**
     * Classifies a .gitignore entry as either a directory or a file pattern.
     *
     * @param string $entry The raw .gitignore entry to classify.
     *
     * @return string The classification result.
     */
    public function classify(string $entry): string
    {
        // ...
    }
}
```

## Command Method Example

For command methods, describe the action and the visible result instead of inventing framework guarantees.

```php
/**
 * Configures the PHPDoc command.
 *
 * This method MUST register the expected CLI options for the command.
 *
 * @return void
 */
protected function configure(): void
{
    // ...
}
```

## Quick Style Signals

- Keep interface and command docblocks shorter than service-implementation docblocks unless the contract is genuinely subtle.
- Prefer repository terms such as "command", "workflow", "report", "sync", and "generated output" over generic service jargon.
- Reuse neighborhood punctuation and casing when the local file already has a clear style.

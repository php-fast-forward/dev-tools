# Fast Forward Interface Contract Example

Use this example when documenting a Fast Forward interface with visible obligations and conservative RFC 2119 wording.

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
 * Defines the contract for classifying .gitignore entries.
 *
 * This classifier SHALL inspect a raw .gitignore entry and determine whether the
 * entry expresses directory semantics or file semantics. Implementations MUST
 * preserve deterministic classification for identical inputs.
 */
interface ClassifierInterface
{
    /**
     * Classifies a .gitignore entry as directory or file pattern.
     *
     * @param string $entry The .gitignore entry to classify.
     *
     * @return 'directory'|'file' The classification result.
     */
    public function classify(string $entry): string;

    /**
     * Determines whether the entry represents a directory pattern.
     *
     * @param string $entry The .gitignore entry to check.
     *
     * @return bool Whether the entry is a directory pattern.
     */
    public function isDirectory(string $entry): bool;
}
```

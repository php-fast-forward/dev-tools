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

namespace FastForward\DevTools\License;

final class Resolver implements ResolverInterface
{
    private const array SUPPORTED_LICENSES = [
        'MIT' => 'mit.txt',
        'BSD-2-Clause' => 'bsd-2-clause.txt',
        'BSD-3-Clause' => 'bsd-3-clause.txt',
        'Apache-2.0' => 'apache-2.0.txt',
        'Apache-2' => 'apache-2.0.txt',
        'GPL-3.0-or-later' => 'gpl-3.0-or-later.txt',
        'GPL-3.0' => 'gpl-3.0-or-later.txt',
        'GPL-3+' => 'gpl-3.0-or-later.txt',
        'LGPL-3.0-or-later' => 'lgpl-3.0-or-later.txt',
        'LGPL-3.0' => 'lgpl-3.0-or-later.txt',
        'LGPL-3+' => 'lgpl-3.0-or-later.txt',
        'MPL-2.0' => 'mpl-2.0.txt',
        'ISC' => 'isc.txt',
        'Unlicense' => 'unlicense.txt',
    ];

    /**
     * @param string $license
     *
     * @return bool
     */
    public function isSupported(string $license): bool
    {
        return isset(self::SUPPORTED_LICENSES[$this->normalize($license)]);
    }

    /**
     * @param string $license
     *
     * @return string|null
     */
    public function resolve(string $license): ?string
    {
        $normalized = $this->normalize($license);

        if (! isset(self::SUPPORTED_LICENSES[$normalized])) {
            return null;
        }

        return self::SUPPORTED_LICENSES[$normalized];
    }

    /**
     * @param string $license
     *
     * @return string
     */
    private function normalize(string $license): string
    {
        return trim($license);
    }
}

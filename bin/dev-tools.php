<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools;

use FastForward\DevTools\Console\DevTools;

$autoloadCandidates = [\dirname(__DIR__) . '/vendor/autoload.php', \dirname(__DIR__, 4) . '/vendor/autoload.php'];

foreach ($autoloadCandidates as $autoloadCandidate) {
    if (is_file($autoloadCandidate)) {
        require_once $autoloadCandidate;

        exit(DevTools::create()->run());
    }
}

fprintf(\STDERR, "Could not locate Composer autoload.php for fast-forward/dev-tools.\n");

exit(1);

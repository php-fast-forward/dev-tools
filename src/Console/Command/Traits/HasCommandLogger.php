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

namespace FastForward\DevTools\Console\Command\Traits;

use FastForward\DevTools\Container\ContainerFactory;
use Psr\Log\LoggerInterface;

/**
 * Resolves the logger expected by command result helper traits.
 *
 * The trait caches the shared logger lazily so consuming commands do not need
 * to carry constructor wiring for internal logging helpers.
 */
trait HasCommandLogger
{
    /**
     * Caches the logger resolved for the consuming command.
     */
    private ?LoggerInterface $logger = null;

    /**
     * Returns the logger configured for the consuming command.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger ??= ContainerFactory::get(LoggerInterface::class);
    }
}

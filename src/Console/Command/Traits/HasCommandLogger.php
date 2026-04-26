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

use LogicException;
use Psr\Log\LoggerInterface;

/**
 * Resolves the logger expected by command result helper traits.
 *
 * The consuming command is expected to expose an initialized `$logger`
 * property so reusable traits can log without coupling themselves to a
 * specific constructor signature.
 */
trait HasCommandLogger
{
    /**
     * Returns the logger configured on the consuming command.
     *
     * @throws LogicException when the consuming command does not expose a valid logger property
     */
    public function getLogger(): LoggerInterface
    {
        if (! property_exists($this, 'logger') || null === $this->logger) {
            throw new LogicException(\sprintf(
                'Commands using %s MUST expose an initialized $logger property with an instance of %s.',
                LogsCommandResults::class,
                LoggerInterface::class,
            ));
        }

        if (! $this->logger instanceof LoggerInterface) {
            throw new LogicException(\sprintf(
                'Commands using %s MUST expose a %s instance on the $logger property.',
                LogsCommandResults::class,
                LoggerInterface::class,
            ));
        }

        return $this->logger;
    }
}

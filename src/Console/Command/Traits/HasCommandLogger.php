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
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * Resolves the logger expected by command result helper traits.
 *
 * The consuming command MAY expose an initialized `$logger` property. When it
 * does not, the trait SHALL resolve the shared logger from the DevTools
 * container so reusable traits can stay decoupled from constructor wiring.
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
        if (property_exists($this, 'logger') && $this->logger instanceof LoggerInterface) {
            return $this->logger;
        }

        if (property_exists($this, 'logger') && null !== $this->logger) {
            throw new LogicException(\sprintf(
                'Commands using %s MUST expose a %s instance on the $logger property.',
                LogsCommandResults::class,
                LoggerInterface::class,
            ));
        }

        $logger = ContainerFactory::get(LoggerInterface::class);

        if (! $logger instanceof LoggerInterface) {
            throw new LogicException(\sprintf(
                'Commands using %s MUST resolve a %s instance from the shared container.',
                LogsCommandResults::class,
                LoggerInterface::class,
            ));
        }

        return $logger;
    }
}

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

namespace FastForward\DevTools\Process;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Applies multiple process environment configurators in a stable order.
 */
final readonly class CompositeProcessEnvironmentConfigurator implements ProcessEnvironmentConfiguratorInterface
{
    /**
     * @param iterable<ProcessEnvironmentConfiguratorInterface> $configurators ordered environment configurators
     */
    public function __construct(
        private iterable $configurators
    ) {}

    /**
     * Configures environment variables for a queued process.
     *
     * @param Process $process the queued process that will be started
     * @param OutputInterface $output the parent output used to infer console capabilities
     */
    public function configure(Process $process, OutputInterface $output): void
    {
        foreach ($this->configurators as $configurator) {
            $configurator->configure($process, $output);
        }
    }
}

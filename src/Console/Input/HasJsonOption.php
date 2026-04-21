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

namespace FastForward\DevTools\Console\Input;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Provides the standard JSON output options used by DevTools commands.
 */
trait HasJsonOption
{
    /**
     * Adds the standard JSON output options to the current command.
     *
     * @return static
     */
    protected function addJsonOption(): static
    {
        return $this
            ->addOption(name: 'json', mode: InputOption::VALUE_NONE, description: 'Emit structured JSON output.')
            ->addOption(
                name: 'pretty-json',
                mode: InputOption::VALUE_NONE,
                description: 'Emit structured JSON output using indentation for readability.',
            );
    }

    /**
     * Determines whether JSON output was requested.
     *
     * The pretty-json flag SHALL imply JSON output.
     *
     * @param InputInterface $input
     */
    protected function isJsonOutput(InputInterface $input): bool
    {
        if ($this->isPrettyJsonOutput($input)) {
            return true;
        }

        return (bool) $input->getOption('json');
    }

    /**
     * Determines whether pretty JSON output was requested.
     *
     * @param InputInterface $input
     */
    protected function isPrettyJsonOutput(InputInterface $input): bool
    {
        return (bool) $input->getOption('pretty-json');
    }
}

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

namespace FastForward\DevTools\Process;

use Symfony\Component\Process\Process;

/**
 * Defines a fluent builder responsible for constructing process instances.
 *
 * Implementations MUST preserve the builder state consistently across chained
 * calls and SHALL return a process configured according to all previously
 * supplied arguments when build() is invoked.
 */
interface ProcessBuilderInterface
{
    /**
     * Adds an argument to the process being built.
     *
     * Implementations MUST append or register the provided argument for later
     * use when build() is called. When a non-empty value is provided, the
     * implementation SHALL associate that value with the argument according to
     * its command-building strategy.
     *
     * @param string $argument the argument name or token that SHALL be added to the process definition
     * @param ?string $value an optional value associated with the argument
     *
     * @return self the current builder instance for fluent chaining
     */
    public function withArgument(string $argument, ?string $value = null): self;

    /**
     * Builds a process instance for the specified command.
     *
     * Implementations MUST return a Process configured with the provided
     * command and all arguments previously collected by the builder. The
     * returned process SHOULD be ready for execution by the caller.
     *
     * @param string $command the base command that SHALL be used to create the process
     *
     * @return Process the configured process instance
     */
    public function build(string $command): Process;
}

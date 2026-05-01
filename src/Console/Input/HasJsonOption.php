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

use FastForward\DevTools\Container\ContainerFactory;
use FastForward\DevTools\Environment\RuntimeEnvironmentInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

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

        if ($this->isOptionEnabled($input, 'json')) {
            return true;
        }

        return $this->isImplicitJsonOutputEnabled();
    }

    /**
     * Determines whether pretty JSON output was requested.
     *
     * @param InputInterface $input
     */
    protected function isPrettyJsonOutput(InputInterface $input): bool
    {
        return $this->isOptionEnabled($input, 'pretty-json');
    }

    /**
     * Determines whether structured JSON output SHOULD be enabled implicitly.
     *
     * Commands MAY opt into runtime-environment-aware behavior by exposing a
     * `$runtimeEnvironment` property. Commands that do not expose it SHALL fall
     * back to the shared runtime-environment service from the DevTools container.
     */
    private function isImplicitJsonOutputEnabled(): bool
    {
        $runtimeEnvironment = $this->resolveRuntimeEnvironment();

        if (! $runtimeEnvironment instanceof RuntimeEnvironmentInterface) {
            $runtimeEnvironment = ContainerFactory::get(RuntimeEnvironmentInterface::class);
        }

        if (! $runtimeEnvironment instanceof RuntimeEnvironmentInterface) {
            return false;
        }

        return $runtimeEnvironment->isAgentPresent() && ! $runtimeEnvironment->isComposerTestRun();
    }

    /**
     * @return ?RuntimeEnvironmentInterface
     */
    private function resolveRuntimeEnvironment(): ?RuntimeEnvironmentInterface
    {
        if (! property_exists($this, 'runtimeEnvironment')) {
            return null;
        }

        if (! $this->runtimeEnvironment instanceof RuntimeEnvironmentInterface) {
            return null;
        }

        return $this->runtimeEnvironment;
    }

    /**
     * Determines whether a boolean input option was enabled.
     *
     * @param InputInterface $input
     * @param string $option
     */
    private function isOptionEnabled(InputInterface $input, string $option): bool
    {
        try {
            return (bool) $input->getOption($option);
        } catch (Throwable) {
            return false;
        }
    }
}

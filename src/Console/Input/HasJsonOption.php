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

use Ergebnis\AgentDetector\Detector;
use FastForward\DevTools\Environment\RuntimeEnvironmentInterface;
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

        if ((bool) $input->getOption('json')) {
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
        return (bool) $input->getOption('pretty-json');
    }

    /**
     * Determines whether structured JSON output SHOULD be enabled implicitly.
     *
     * Commands MAY opt into runtime-environment-aware behavior by exposing a
     * `$runtimeEnvironment` property. Commands that do not expose it SHALL fall
     * back to lightweight agent detection based on process environment
     * variables, except while the PHPUnit test runtime is active.
     */
    private function isImplicitJsonOutputEnabled(): bool
    {
        $runtimeEnvironment = $this->resolveRuntimeEnvironment();

        if ($runtimeEnvironment instanceof RuntimeEnvironmentInterface) {
            return $runtimeEnvironment->isAgentPresent() && ! $runtimeEnvironment->isComposerTestRun();
        }

        if ($this->isPhpUnitRuntime() || $this->isComposerTestRunEnvironmentEnabled()) {
            return false;
        }

        return (new Detector())->isAgentPresent($this->resolveEnvironmentVariables());
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
     * Returns whether the current process is executing inside PHPUnit.
     */
    private function isPhpUnitRuntime(): bool
    {
        return \defined('PHPUNIT_COMPOSER_INSTALL');
    }

    /**
     * Returns whether the Composer test runtime flag is enabled.
     */
    private function isComposerTestRunEnvironmentEnabled(): bool
    {
        $value = $_SERVER['COMPOSER_TESTS_ARE_RUNNING'] ?? getenv('COMPOSER_TESTS_ARE_RUNNING');

        if (false === $value || null === $value) {
            return false;
        }

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Returns environment variables suitable for lightweight agent detection.
     *
     * @return array<string, string>
     */
    private function resolveEnvironmentVariables(): array
    {
        $environmentVariables = [];

        foreach ([$_SERVER, $_ENV] as $environment) {
            foreach ($environment as $name => $value) {
                if (! \is_string($name) || ! \is_string($value)) {
                    continue;
                }

                $environmentVariables[$name] ??= $value;
            }
        }

        return $environmentVariables;
    }
}

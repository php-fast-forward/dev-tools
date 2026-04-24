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

use FastForward\DevTools\Console\Output\OutputCapabilityDetectorInterface;
use FastForward\DevTools\Environment\EnvironmentInterface;
use Stringable;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Keeps nested process output color-friendly without requiring PTY support.
 */
final readonly class ColorPreservingProcessEnvironmentConfigurator implements ProcessEnvironmentConfiguratorInterface
{
    /**
     * @param EnvironmentInterface $environment reads parent process environment variables
     * @param OutputCapabilityDetectorInterface $outputCapabilityDetector detects TTY/decorated output capabilities
     */
    public function __construct(
        private EnvironmentInterface $environment,
        private OutputCapabilityDetectorInterface $outputCapabilityDetector,
    ) {}

    /**
     * Configures color-related environment variables for nested commands.
     *
     * @param Process $process the queued process that will be started
     * @param OutputInterface $output the parent output used to infer console capabilities
     */
    public function configure(Process $process, OutputInterface $output): void
    {
        if (! $this->shouldForceColor($output)) {
            return;
        }

        $env = $process->getEnv();

        if ($this->hasNoColorOptOut($env)) {
            return;
        }

        $changed = $this->setDefault($env, 'FORCE_COLOR', '1');
        $changed = $this->setDefault($env, 'CLICOLOR_FORCE', '1') || $changed;

        if (null !== ($term = $this->environment->get('TERM'))) {
            $changed = $this->setDefault($env, 'TERM', $term) || $changed;
        }

        if ($changed) {
            $process->setEnv($env);
        }
    }

    /**
     * Determines whether child processes should be nudged toward ANSI output.
     *
     * @param OutputInterface $output the parent process output
     *
     * @return bool true when color should be forced for child processes
     */
    private function shouldForceColor(OutputInterface $output): bool
    {
        if ($this->outputCapabilityDetector->supportsAnsi($output)) {
            return true;
        }
        if ($this->isTruthyEnvironmentFlag('FORCE_COLOR')) {
            return true;
        }

        return $this->isTruthyEnvironmentFlag('CLICOLOR_FORCE');
    }

    /**
     * Determines whether an environment flag is set to a truthy value.
     *
     * @param string $name the environment variable name
     *
     * @return bool true when the environment variable is truthy
     */
    private function isTruthyEnvironmentFlag(string $name): bool
    {
        $value = $this->environment->get($name, '');

        return null !== $value && '' !== $value && '0' !== $value;
    }

    /**
     * Determines whether the process or parent environment opted out of color.
     *
     * @param array<string|Stringable> $env the process-specific environment variables
     *
     * @return bool true when NO_COLOR is present
     */
    private function hasNoColorOptOut(array $env): bool
    {
        return \array_key_exists('NO_COLOR', $env)
            || null !== $this->environment->get('NO_COLOR');
    }

    /**
     * Sets an environment default while preserving caller-provided values.
     *
     * @param array<string|Stringable> $env the environment map to update
     * @param string $name the environment variable name
     * @param string $value the default value
     *
     * @return bool true when the environment map changed
     */
    private function setDefault(array &$env, string $name, string $value): bool
    {
        if (\array_key_exists($name, $env)) {
            return false;
        }

        $env[$name] = $value;

        return true;
    }
}

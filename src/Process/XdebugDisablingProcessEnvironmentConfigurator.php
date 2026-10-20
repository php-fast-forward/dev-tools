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

use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Php\ExtensionInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Safe\preg_match;

/**
 * Disables Xdebug for child processes unless coverage still needs it.
 */
final readonly class XdebugDisablingProcessEnvironmentConfigurator implements ProcessEnvironmentConfiguratorInterface
{
    /**
     * @var list<string>
     */
    private const array COVERAGE_ARGUMENT_PATTERNS = [
        '--coverage',
        '--coverage-clover',
        '--coverage-cobertura',
        '--coverage-crap4j',
        '--coverage-html',
        '--coverage-php',
        '--coverage-text',
        '--coverage-xml',
        '--min-coverage',
    ];

    /**
     * @param EnvironmentInterface $environment reads parent process environment variables
     * @param ExtensionInterface $extension checks PHP extension availability
     */
    public function __construct(
        private EnvironmentInterface $environment,
        private ExtensionInterface $extension,
    ) {}

    /**
     * Configures Xdebug-related environment variables for nested commands.
     *
     * @param Process $process the queued process that will be started
     * @param OutputInterface $output the parent output used to infer console capabilities
     */
    public function configure(Process $process, OutputInterface $output): void
    {
        unset($output);

        if (! $this->shouldDisableXdebug($process)) {
            return;
        }

        $env = $process->getEnv();

        if (\array_key_exists('XDEBUG_MODE', $env)) {
            return;
        }

        $env['XDEBUG_MODE'] = 'off';
        $process->setEnv($env);
    }

    /**
     * Determines whether Xdebug can be disabled for the child process.
     *
     * @param Process $process the queued process that will be started
     *
     * @return bool true when Xdebug should be disabled for the child process
     */
    private function shouldDisableXdebug(Process $process): bool
    {
        if (! $this->extension->isLoaded('xdebug')) {
            return false;
        }

        if ($this->isTruthyEnvironmentFlag('COMPOSER_ALLOW_XDEBUG')) {
            return false;
        }

        if (null !== $this->environment->get('XDEBUG_MODE')) {
            return false;
        }

        if (! $this->requiresCoverage($process)) {
            return true;
        }

        return $this->extension->isLoaded('pcov');
    }

    /**
     * Determines whether the child process command line requests coverage.
     *
     * @param Process $process the queued process that will be started
     *
     * @return bool true when coverage arguments are present
     */
    private function requiresCoverage(Process $process): bool
    {
        $commandLine = $process->getCommandLine();

        foreach (self::COVERAGE_ARGUMENT_PATTERNS as $argument) {
            if ($this->containsCommandLineArgument($commandLine, $argument)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether a command line contains an exact long option.
     *
     * @param string $commandLine the shell-escaped command line
     * @param string $argument the long option to find
     *
     * @return bool true when the exact option is present
     */
    private function containsCommandLineArgument(string $commandLine, string $argument): bool
    {
        return 1 === preg_match(
            \sprintf('/(?:^|[\\s\'"])%s(?:=|[\\s\'"]|$)/', preg_quote($argument, '/')),
            $commandLine
        );
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
}

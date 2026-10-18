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

namespace FastForward\DevTools\Console\Command;

use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessEnvironmentConfiguratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Delegates changelog behavior to the isolated standalone library runtime.
 */
#[AsCommand(
    name: 'dev-tools:changelog',
    description: 'Runs the standalone Fast Forward Changelog CLI through CPX (PHP 8.5 required).',
    aliases: ['changelog'],
)]
final class ChangelogCommand extends Command
{
    /**
     * @param ProcessBuilderInterface $processBuilder builds argv-safe subprocesses
     * @param ProcessEnvironmentConfiguratorInterface $environmentConfigurator applies the shared child-process environment policy
     * @param int $phpVersionId the active PHP runtime version
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessEnvironmentConfiguratorInterface $environmentConfigurator,
        private readonly int $phpVersionId = \PHP_VERSION_ID,
    ) {
        parent::__construct();
    }

    /**
     * Accepts library arguments without duplicating its evolving option schema.
     */
    protected function configure(): void
    {
        $this->addArgument('arguments', InputArgument::IS_ARRAY, 'Arguments for fast-forward/changelog.');
        $this->setHelp(
            'Requires CPX and PHP 8.5 on PATH. Use "dev-tools changelog -- <command> [options]" to forward all library options, including --help.'
        );
        $this->ignoreValidationErrors();
    }

    /**
     * Forwards the library output and exit status without adding presentation.
     *
     * @param InputInterface $input the wrapper input
     * @param OutputInterface $output the destination for library output
     *
     * @return int the library process exit status
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->phpVersionId < 80500) {
            $output->writeln(
                '<error>Fast Forward Changelog 0.2.0 requires PHP 8.5. Run this command and CPX with PHP 8.5; DevTools itself remains compatible with PHP 8.3.</error>'
            );

            return self::FAILURE;
        }

        $arguments = $input instanceof ArgvInput ? $input->getRawTokens(true) : $input->getArgument('arguments');

        if ('--' === ($arguments[0] ?? null)) {
            array_shift($arguments);
        } else {
            $arguments = $this->withoutWrapperOptions($arguments);
        }

        if (! $input->isInteractive() && ! \in_array('--no-interaction', $arguments, true)) {
            array_unshift($arguments, '--no-interaction');
        }

        if (! \in_array('--ansi', $arguments, true) && ! \in_array('--no-ansi', $arguments, true)) {
            array_unshift($arguments, $output->isDecorated() ? '--ansi' : '--no-ansi');
        }

        $process = $this->processBuilder->build(['cpx', '--skip-local', 'fast-forward/changelog:0.2.0', ...$arguments]);
        $this->environmentConfigurator->configure($process, $output);

        return $process->run(static function (string $type, string $buffer) use ($output): void {
            $destination = Process::ERR === $type && $output instanceof ConsoleOutputInterface
                ? $output->getErrorOutput()
                : $output;
            $destination->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });
    }

    /**
     * Removes DevTools runtime options already applied by the parent application.
     *
     * @param list<string> $arguments raw tokens following the wrapper command
     *
     * @return list<string> library tokens with wrapper-only options removed
     */
    private function withoutWrapperOptions(array $arguments): array
    {
        $forwarded = [];

        while ([] !== $arguments) {
            $argument = array_shift($arguments);

            if ('--' === $argument) {
                return [...$forwarded, $argument, ...$arguments];
            }

            if (\in_array($argument, ['--no-logo', '--auto-update'], true)) {
                continue;
            }

            if (\in_array($argument, ['--working-dir', '-d', '--workspace-dir', '-w'], true)) {
                array_shift($arguments);

                continue;
            }

            if (str_starts_with($argument, '--working-dir=') || str_starts_with($argument, '--workspace-dir=')) {
                continue;
            }

            $forwarded[] = $argument;
        }

        return $forwarded;
    }
}

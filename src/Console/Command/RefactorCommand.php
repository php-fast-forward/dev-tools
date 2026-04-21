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

use Composer\Command\BaseCommand;
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides functionality to execute automated code refactoring using Rector.
 * This class MUST NOT be extended and SHALL encapsulate the logic for Rector invocation.
 */
#[AsCommand(
    name: 'refactor',
    description: 'Runs Rector for code refactoring.',
    aliases: ['rector'],
    help: 'This command runs Rector to refactor your code.'
)]
final class RefactorCommand extends BaseCommand
{
    /**
     * @var string the default Rector configuration file
     */
    public const string CONFIG = 'rector.php';

    /**
     * Creates a new RefactorCommand instance.
     *
     * @param FileLocatorInterface $fileLocator the file locator
     * @param ProcessBuilderInterface $processBuilder the process builder
     * @param ProcessQueueInterface $processQueue the process queue
     * @param CommandResponderFactoryInterface $commandResponderFactory the
     *                                                                  structured
     *                                                                  command
     *                                                                  responder
     *                                                                  factory
     */
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly CommandResponderFactoryInterface $commandResponderFactory,
    ) {
        parent::__construct();
    }

    /**
     * Configures the refactor command options and description.
     *
     * This method MUST define the expected `--fix` option. It SHALL configure the command name
     * and descriptions accurately.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Automatically fix code refactoring issues.'
            )
            ->addOption(
                name: 'config',
                shortcut: 'c',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The path to the Rector configuration file.',
                default: self::CONFIG
            )
            ->addOption(
                name: 'output-format',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format for the command result. Supported values: text, json.',
                default: OutputFormat::defaultValue(),
                suggestedValues: OutputFormat::supportedValues(),
            );
    }

    /**
     * Executes the refactoring process securely.
     *
     * The method MUST execute Rector securely via `Process`. It SHALL use dry-run mode
     * unless the `--fix` option is specified. It MUST return `self::SUCCESS` or `self::FAILURE`.
     *
     * @param InputInterface $input the input interface to retrieve arguments properly
     * @param OutputInterface $output the output interface to log outputs
     *
     * @return int the status code denoting success or failure
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $responder = $this->commandResponderFactory->from($input, $output);
        $textOutput = OutputFormat::TEXT === $responder->format();
        $processOutput = $textOutput ? $output : new BufferedOutput();
        $fix = (bool) $input->getOption('fix');

        if ($textOutput) {
            $output->writeln('<info>Running Rector for code refactoring...</info>');
        }

        $processBuilder = $this->processBuilder
            ->withArgument('process')
            ->withArgument('--config')
            ->withArgument($this->fileLocator->locate(self::CONFIG));

        if (! $fix) {
            $processBuilder = $processBuilder->withArgument('--dry-run');
        }

        $this->processQueue->add($processBuilder->build('vendor/bin/rector'));

        $result = $this->processQueue->run($processOutput);

        return self::SUCCESS === $result
            ? $responder->success(
                'Code refactoring checks completed successfully.',
                [
                    'command' => 'refactor',
                    'fix' => $fix,
                    'config' => self::CONFIG,
                    'process_output' => $processOutput instanceof BufferedOutput ? $processOutput->fetch() : null,
                ],
            )
            : $responder->failure(
                'Code refactoring checks failed.',
                [
                    'command' => 'refactor',
                    'fix' => $fix,
                    'config' => self::CONFIG,
                    'process_output' => $processOutput instanceof BufferedOutput ? $processOutput->fetch() : null,
                ],
            );
    }
}

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

namespace FastForward\DevTools\Console\Command;

use Composer\Command\BaseCommand;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Represents the command responsible for checking and fixing code style issues.
 * This class MUST NOT be overridden and SHALL rely on external tools like ECS and Composer Normalize.
 */
#[AsCommand(
    name: 'code-style',
    description: 'Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.',
    help: 'This command runs EasyCodingStandard and Composer Normalize to check and fix code style issues.'
)]
final class CodeStyleCommand extends BaseCommand
{
    /**
     * @var string the default configuration file used for EasyCodingStandard
     */
    public const string CONFIG = 'ecs.php';

    /**
     * Constructs a new command instance responsible for orchestrating code style checks.
     *
     * The provided collaborators SHALL be used to locate the ECS configuration,
     * build process definitions, and execute the resulting process queue. These
     * dependencies MUST be valid service instances capable of supporting the
     * command lifecycle expected by this class.
     *
     * @param FileLocatorInterface $fileLocator locates the configuration file required by EasyCodingStandard
     * @param ProcessBuilderInterface $processBuilder builds the process instances used to execute Composer and ECS commands
     * @param ProcessQueueInterface $processQueue queues and executes the generated processes in the required order
     */
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
    ) {
        parent::__construct();
    }

    /**
     * Configures the current command.
     *
     * This method MUST define the name, description, help text, and options for the command.
     * It SHALL register the `--fix` option to allow automatic resolutions of style issues.
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
                description: 'Automatically fix code style issues.'
            );
    }

    /**
     * Executes the code style checks and fixes block.
     *
     * The method MUST execute `composer update --lock`, `composer normalize`, and ECS using secure processes.
     * It SHALL return `self::SUCCESS` if all commands succeed, or `self::FAILURE` otherwise.
     *
     * @param InputInterface $input the input interface to retrieve options
     * @param OutputInterface $output the output interface to log messages
     *
     * @return int the status code of the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running code style checks and fixes...</info>');

        $composerUpdate = $this->processBuilder
            ->withArgument('--lock')
            ->withArgument('--quiet')
            ->build('composer update');

        $composerNormalize = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument($input->getOption('fix') ? '--quiet' : '--dry-run')
            ->build('composer normalize');

        $processBuilder = $this->processBuilder
            ->withArgument('--no-progress-bar')
            ->withArgument('--config', $this->fileLocator->locate(self::CONFIG));

        if ($input->getOption('fix')) {
            $processBuilder = $processBuilder->withArgument('--fix');
        }

        $ecs = $processBuilder->build('vendor/bin/ecs');

        $this->processQueue->add($composerUpdate);
        $this->processQueue->add($composerNormalize);
        $this->processQueue->add($ecs);

        return $this->processQueue->run($output);
    }
}

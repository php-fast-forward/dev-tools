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
use Composer\Console\Input\InputOption;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Coordinates the generation of Fast Forward documentation frontpage and related reports.
 * This class MUST NOT be overridden and SHALL securely combine docs and testing commands.
 */
#[AsCommand(
    name: 'reports',
    description: 'Generates the frontpage for Fast Forward documentation.',
    help: 'This command generates the frontpage for Fast Forward documentation, including links to API documentation and test reports.'
)]
final class ReportsCommand extends BaseCommand
{
    /**
     * Initializes the command with required dependencies.
     *
     * @param ProcessBuilderInterface $processBuilder the builder instance used to construct execution processes
     * @param ProcessQueueInterface $processQueue the execution queue mechanism for running sub-processes
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'target',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target directory for the generated reports.',
                default: 'public',
            )
            ->addOption(
                name: 'coverage',
                shortcut: 'c',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target directory for the generated test coverage report.',
                default: 'public/coverage',
            );
    }

    /**
     * Executes the generation logic for diverse reports.
     *
     * The method MUST run the underlying `docs` and `tests` commands. It SHALL process
     * and generate the frontpage output file successfully.
     *
     * @param InputInterface $input the structured inputs holding specific arguments
     * @param OutputInterface $output the designated output interface
     *
     * @return int the integer outcome from the base process execution
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Generating frontpage for Fast Forward documentation...</info>');

        $docs = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--target', $input->getOption('target'))
            ->build('composer dev-tools docs');

        $coverage = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--coverage', $input->getOption('coverage'))
            ->build('composer dev-tools tests');

        $this->processQueue->add(process: $docs, detached: true);
        $this->processQueue->add(process: $coverage, detached: true);

        return $this->processQueue->run($output);
    }
}

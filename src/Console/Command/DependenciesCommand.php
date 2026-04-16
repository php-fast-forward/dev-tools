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
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Orchestrates dependency analysis across the supported Composer analyzers.
 * This command MUST report missing and unused dependencies using a single,
 * deterministic report that is friendly for local development and CI runs.
 */
#[AsCommand(
    name: 'dependencies',
    description: 'Analyzes missing and unused Composer dependencies.',
    aliases: ['deps'],
    help: 'This command runs composer-dependency-analyser and composer-unused to report missing and unused Composer dependencies.'
)]
final class DependenciesCommand extends BaseCommand
{
    /**
     * @param ProcessBuilderInterface $processBuilder
     * @param ProcessQueueInterface $processQueue
     * @param FileLocatorInterface $fileLocator
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly FileLocatorInterface $fileLocator,
    ) {
        return parent::__construct();
    }

    /**
     * Executes the dependency analysis workflow.
     *
     * The command MUST verify the required binaries before executing the tools,
     * SHOULD normalize their machine-readable output into a unified report, and
     * SHALL return a non-zero exit code when findings or execution failures exist.
     *
     * @param InputInterface $input the runtime command input
     * @param OutputInterface $output the console output stream
     *
     * @return int the command execution status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running dependency analysis...</info>');

        $composerJson = $this->fileLocator->locate('composer.json');

        $composerUnused = $this->processBuilder
            ->withArgument($composerJson)
            ->withArgument('--no-progress')
            ->build('vendor/bin/composer-unused');

        $composerDependencyAnalyser = $this->processBuilder
            ->withArgument('--composer-json', $composerJson)
            ->withArgument('--ignore-unused-deps')
            ->withArgument('--ignore-prod-only-in-dev-deps')
            ->build('vendor/bin/composer-dependency-analyser');

        $this->processQueue->add($composerUnused);
        $this->processQueue->add($composerDependencyAnalyser);

        return $this->processQueue->run($output);
    }
}

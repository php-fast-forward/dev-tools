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
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function is_numeric;

/**
 * Orchestrates dependency analysis across the supported Composer analyzers.
 * This command MUST report missing and unused dependencies using a single,
 * deterministic report that is friendly for local development and CI runs.
 */
#[AsCommand(
    name: 'dependencies',
    description: 'Analyzes missing, unused, and outdated Composer dependencies.',
    aliases: ['deps'],
    help: 'This command runs composer-dependency-analyser, composer-unused, and Jack to report missing, unused, and outdated Composer dependencies.'
)]
final class DependenciesCommand extends BaseCommand
{
    /**
     * @param ProcessBuilderInterface $processBuilder creates analyzer and upgrade processes
     * @param ProcessQueueInterface $processQueue executes queued processes
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
    ) {
        return parent::__construct();
    }

    /**
     * Configures the dependency workflow options.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'max-outdated',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Maximum number of outdated packages allowed by jack breakpoint.',
                default: '5',
            )
            ->addOption(
                name: 'dev',
                mode: InputOption::VALUE_NONE,
                description: 'Prioritize dev dependencies where Jack supports it.',
            )
            ->addOption(
                name: 'upgrade',
                mode: InputOption::VALUE_NONE,
                description: 'Apply Jack dependency upgrades before executing the dependency analyzers.',
            );
    }

    /**
     * Executes the dependency analysis workflow.
     *
     * @param InputInterface $input the runtime command input
     * @param OutputInterface $output the console output stream
     *
     * @return int the command execution status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $maximumOutdated = $this->resolveMaximumOutdated($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $output->writeln('<error>' . $invalidArgumentException->getMessage() . '</error>');

            return self::FAILURE;
        }

        $this->processQueue->add($this->getRaiseToInstalledCommand($input));
        $this->processQueue->add($this->getOpenVersionsCommand($input));

        if ($input->getOption('upgrade')) {
            $this->processQueue->add($this->getComposerUpdateCommand());
            $this->processQueue->add($this->getComposerNormalizeCommand());
        }

        $output->writeln('<info>Running dependency analysis...</info>');

        $this->processQueue->add($this->getComposerUnusedCommand());
        $this->processQueue->add($this->getComposerDependencyAnalyserCommand());
        $this->processQueue->add($this->getJackBreakpointCommand($input, $maximumOutdated));

        return $this->processQueue->run($output);
    }

    /**
     * Builds the Composer Dependency Analyser process.
     *
     * @return Process the configured Composer Dependency Analyser process
     */
    private function getComposerDependencyAnalyserCommand(): Process
    {
        return $this->processBuilder
            ->withArgument('--ignore-unused-deps')
            ->withArgument('--ignore-prod-only-in-dev-deps')
            ->build('vendor/bin/composer-dependency-analyser');
    }

    /**
     * Builds the Jack breakpoint process.
     *
     * @param InputInterface $input the runtime command input
     * @param int $maximumOutdated the maximum number of outdated packages accepted by Jack
     *
     * @return Process the configured Jack breakpoint process
     */
    private function getJackBreakpointCommand(InputInterface $input, int $maximumOutdated): Process
    {
        $command = 'vendor/bin/jack breakpoint';

        if ((bool) $input->getOption('dev')) {
            $command .= ' --dev';
        }

        $command .= ' --limit ' . $maximumOutdated;

        return $this->processBuilder->build($command);
    }

    /**
     * Builds the Jack open-versions process.
     *
     * @param InputInterface $input the runtime command input
     *
     * @return Process the configured Jack open-versions process
     */
    private function getOpenVersionsCommand(InputInterface $input): Process
    {
        $command = 'vendor/bin/jack open-versions';

        if ((bool) $input->getOption('dev')) {
            $command .= ' --dev';
        }

        if (! (bool) $input->getOption('upgrade')) {
            $command .= ' --dry-run';
        }

        return $this->processBuilder->build($command);
    }

    /**
     * Builds the Jack raise-to-installed process.
     *
     * @param InputInterface $input the runtime command input
     *
     * @return Process the configured Jack raise-to-installed process
     */
    private function getRaiseToInstalledCommand(InputInterface $input): Process
    {
        $command = 'vendor/bin/jack raise-to-installed';

        if ((bool) $input->getOption('dev')) {
            $command .= ' --dev';
        }

        if (! (bool) $input->getOption('upgrade')) {
            $command .= ' --dry-run';
        }

        return $this->processBuilder->build($command);
    }

    /**
     * Builds the Composer update process.
     *
     * @return Process the configured Composer update process
     */
    private function getComposerUpdateCommand(): Process
    {
        return $this->processBuilder
            ->withArgument('-W')
            ->withArgument('--ansi')
            ->withArgument('--no-progress')
            ->build('composer update');
    }

    /**
     * Builds the Composer Normalize process.
     *
     * @return Process the configured Composer Normalize process
     */
    private function getComposerNormalizeCommand(): Process
    {
        return $this->processBuilder->build('composer normalize');
    }

    /**
     * Builds the composer-unused process.
     *
     * @return Process the configured composer-unused process
     */
    private function getComposerUnusedCommand(): Process
    {
        return $this->processBuilder->build('vendor/bin/composer-unused');
    }

    /**
     * Resolves the maximum outdated dependency threshold.
     *
     * @param InputInterface $input the runtime command input
     *
     * @return int the validated maximum number of outdated packages
     */
    private function resolveMaximumOutdated(InputInterface $input): int
    {
        $maximumOutdated = $input->getOption('max-outdated');

        if (! is_numeric($maximumOutdated)) {
            throw new InvalidArgumentException('The --max-outdated option MUST be a numeric threshold.');
        }

        $maximumOutdated = (int) $maximumOutdated;

        if (0 > $maximumOutdated) {
            throw new InvalidArgumentException('The --max-outdated option MUST be zero or greater.');
        }

        return $maximumOutdated;
    }
}

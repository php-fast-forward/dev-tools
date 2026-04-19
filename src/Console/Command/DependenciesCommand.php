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
use FastForward\DevTools\Dependency\DependencyUpgradeProcessFactoryInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param DependencyUpgradeProcessFactoryInterface $upgradeProcessFactory creates Jack and Composer upgrade processes
     * @param ProcessBuilderInterface $processBuilder creates analyzer processes
     * @param ProcessQueueInterface $processQueue executes queued processes
     * @param FileLocatorInterface $fileLocator resolves local composer.json
     */
    public function __construct(
        private readonly DependencyUpgradeProcessFactoryInterface $upgradeProcessFactory,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly FileLocatorInterface $fileLocator,
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
                name: 'fix',
                mode: InputOption::VALUE_NONE,
                description: 'Apply Jack dependency upgrades before executing the dependency analyzers.',
            )
            ->addOption(
                name: 'dev',
                mode: InputOption::VALUE_NONE,
                description: 'Prioritize dev dependencies where Jack supports it.',
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

        $this->fileLocator->locate('composer.json');

        $fix = (bool) $input->getOption('fix');
        $dev = (bool) $input->getOption('dev');

        $output->writeln(
            $fix
                ? '<info>Running dependency upgrade and analysis...</info>'
                : '<info>Running dependency dry-run upgrade preview and analysis...</info>'
        );

        foreach ($this->upgradeProcessFactory->create($fix, $dev) as $process) {
            $this->processQueue->add($process);
        }

        $this->processQueue->add(
            $this->processBuilder->build('vendor/bin/composer-unused')
        );
        $this->processQueue->add(
            $this->processBuilder
                ->withArgument('--ignore-unused-deps')
                ->withArgument('--ignore-prod-only-in-dev-deps')
                ->build('vendor/bin/composer-dependency-analyser')
        );
        $this->processQueue->add(
            $this->processBuilder
                ->withArgument('--limit', (string) $maximumOutdated)
                ->build('vendor/bin/jack breakpoint')
        );

        return $this->processQueue->run($output);
    }

    /**
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

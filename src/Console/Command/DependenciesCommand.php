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

use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Config\ComposerDependencyAnalyserConfig;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function is_numeric;

/**
 * Orchestrates dependency analysis across the supported Composer analyzers.
 * This command MUST report missing, unused, and misplaced dependencies using a single,
 * deterministic report that is friendly for local development and CI runs.
 */
#[AsCommand(
    name: 'dev-tools:deps',
    description: 'Analyzes missing, unused, misplaced, and outdated Composer dependencies.',
    aliases: ['deps', 'dependencies']
)]
final class DependenciesCommand extends Command
{
    use HasJsonOption;
    use LogsCommandResults;

    private const string ANALYSER_CONFIG = 'composer-dependency-analyser.php';

    private const int DISABLE_OUTDATED_THRESHOLD = -1;

    /**
     * @param ProcessBuilderInterface $processBuilder creates analyzer and upgrade processes
     * @param ProcessQueueInterface $processQueue executes queued processes
     * @param FileLocatorInterface $fileLocator resolves the dependency analyser configuration
     */
    public function __construct(
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
        $this->setHelp(
            'This command runs composer-dependency-analyser and Rector Swiss Knife to report missing, unused, misplaced, and'
            . ' outdated Composer dependencies.'
        );

        $this->addJsonOption()
            ->addOption(
                name: 'max-outdated',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Maximum number of outdated packages allowed by swiss-knife breakpoint. Use -1 to keep the report but ignore Rector Swiss Knife failures.',
                default: '5',
            )
            ->addOption(
                name: 'dev',
                mode: InputOption::VALUE_NONE,
                description: 'Prioritize dev dependencies where Rector Swiss Knife supports it.',
            )
            ->addOption(
                name: 'upgrade',
                mode: InputOption::VALUE_NONE,
                description: 'Apply Rector Swiss Knife dependency upgrades before executing the dependency analyzers.',
            )
            ->addOption(
                name: 'dump-usage',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Dump usages for the given package pattern and show all matched usages.',
            )
            ->addOption(
                name: 'show-shadow-dependencies',
                mode: InputOption::VALUE_NONE,
                description: 'Report shadow dependencies instead of applying Fast Forward intentional-shadow ignores.',
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
        $jsonOutput = $this->isJsonOutput($input);
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;

        try {
            $maximumOutdated = $this->resolveMaximumOutdated($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->failure($invalidArgumentException->getMessage(), $input);
        }

        $this->processQueue->add(
            process: $this->getRaiseToInstalledCommand($input),
            label: 'Raising Dependency Constraints with Rector Swiss Knife',
        );
        $this->processQueue->add(
            process: $this->getOpenVersionsCommand($input),
            label: 'Opening Dependency Constraints with Rector Swiss Knife',
        );

        if ($input->getOption('upgrade')) {
            $this->processQueue->add(
                process: $this->getComposerUpdateCommand(),
                label: 'Updating Dependencies with Composer'
            );
            $this->processQueue->add(
                process: $this->getComposerNormalizeCommand(),
                label: 'Normalizing composer.json with Composer Normalize',
            );
        }

        $this->log('Running dependency analysis...', $input);

        $this->processQueue->add(
            process: $this->getComposerDependencyAnalyserCommand($input),
            label: 'Analyzing Dependencies with Composer Dependency Analyser',
        );
        $this->processQueue->add(
            process: $this->getSwissKnifeBreakpointCommand($input, $maximumOutdated),
            ignoreFailure: $this->shouldIgnoreOutdatedFailures($maximumOutdated),
            label: 'Checking Outdated Dependencies with Rector Swiss Knife',
        );

        $result = $this->processQueue->run($processOutput);

        if (self::SUCCESS === $result) {
            return $this->success('Dependency analysis completed successfully.', $input, [
                'output' => $processOutput,
            ]);
        }

        return $this->failure('Dependency analysis failed.', $input, [
            'output' => $processOutput,
        ]);
    }

    /**
     * Builds the Composer Dependency Analyser process.
     *
     * @param InputInterface $input the runtime command input
     *
     * @return Process the configured Composer Dependency Analyser process
     */
    private function getComposerDependencyAnalyserCommand(InputInterface $input): Process
    {
        $processBuilder = $this->processBuilder
            ->withArgument('--config', $this->fileLocator->locate(self::ANALYSER_CONFIG));

        $dumpUsage = $input->getOption('dump-usage');

        if (\is_string($dumpUsage) && '' !== $dumpUsage) {
            $processBuilder = $processBuilder
                ->withArgument('--dump-usages', $dumpUsage)
                ->withArgument('--show-all-usages');
        }

        $showShadowDependencies = (bool) $input->getOption('show-shadow-dependencies');
        $process = $processBuilder->build(
            [DevToolsPathResolver::getPreferredToolBinaryPath('composer-dependency-analyser')]
        );
        $process->setEnv([
            ComposerDependencyAnalyserConfig::ENV_SHOW_SHADOW_DEPENDENCIES => $showShadowDependencies ? '1' : '0',
        ]);

        return $process;
    }

    /**
     * Builds the Rector Swiss Knife breakpoint process.
     *
     * @param InputInterface $input the runtime command input
     * @param int $maximumOutdated the maximum number of outdated packages accepted by Rector Swiss Knife
     *
     * @return Process the configured Rector Swiss Knife breakpoint process
     */
    private function getSwissKnifeBreakpointCommand(InputInterface $input, int $maximumOutdated): Process
    {
        $processBuilder = $this->processBuilder;

        if ((bool) $input->getOption('dev')) {
            $processBuilder = $processBuilder->withArgument('--dev');
        }

        if (! $this->shouldIgnoreOutdatedFailures($maximumOutdated)) {
            $processBuilder = $processBuilder->withArgument('--limit', (string) $maximumOutdated);
        }

        return $processBuilder->build([DevToolsPathResolver::getPreferredToolBinaryPath('swiss-knife'), 'breakpoint']);
    }

    /**
     * Builds the Rector Swiss Knife open-versions process.
     *
     * @param InputInterface $input the runtime command input
     *
     * @return Process the configured Rector Swiss Knife open-versions process
     */
    private function getOpenVersionsCommand(InputInterface $input): Process
    {
        $processBuilder = $this->processBuilder;

        if ((bool) $input->getOption('dev')) {
            $processBuilder = $processBuilder->withArgument('--dev');
        }

        if (! (bool) $input->getOption('upgrade')) {
            $processBuilder = $processBuilder->withArgument('--dry-run');
        }

        return $processBuilder->build(
            [DevToolsPathResolver::getPreferredToolBinaryPath('swiss-knife'), 'open-versions']
        );
    }

    /**
     * Builds the Rector Swiss Knife raise-to-installed process.
     *
     * @param InputInterface $input the runtime command input
     *
     * @return Process the configured Rector Swiss Knife raise-to-installed process
     */
    private function getRaiseToInstalledCommand(InputInterface $input): Process
    {
        $processBuilder = $this->processBuilder;

        if (! (bool) $input->getOption('upgrade')) {
            $processBuilder = $processBuilder->withArgument('--dry-run');
        }

        return $processBuilder->build(
            [DevToolsPathResolver::getPreferredToolBinaryPath('swiss-knife'), 'raise-to-installed']
        );
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
        return $this->processBuilder
            ->withArgument('--ansi')
            ->build('composer normalize');
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

        if (self::DISABLE_OUTDATED_THRESHOLD > $maximumOutdated) {
            throw new InvalidArgumentException('The --max-outdated option MUST be -1 or greater.');
        }

        return $maximumOutdated;
    }

    /**
     * Determines whether Rector Swiss Knife outdated failures SHOULD be ignored for the given threshold.
     *
     * @param int $maximumOutdated the validated outdated threshold option
     *
     * @return bool true when the outdated threshold is explicitly disabled
     */
    private function shouldIgnoreOutdatedFailures(int $maximumOutdated): bool
    {
        return self::DISABLE_OUTDATED_THRESHOLD === $maximumOutdated;
    }
}

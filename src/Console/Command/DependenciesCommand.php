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
use Composer\Command\BaseCommand;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
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
    name: 'dependencies',
    description: 'Analyzes missing, unused, misplaced, and outdated Composer dependencies.',
    aliases: ['deps']
)]
final class DependenciesCommand extends BaseCommand implements LoggerAwareCommandInterface
{
    use HasJsonOption;
    use LogsCommandResults;

    private const string ANALYSER_CONFIG = 'composer-dependency-analyser.php';

    private const int DISABLE_OUTDATED_THRESHOLD = -1;

    /**
     * @param ProcessBuilderInterface $processBuilder creates analyzer and upgrade processes
     * @param ProcessQueueInterface $processQueue executes queued processes
     * @param FileLocatorInterface $fileLocator resolves the dependency analyser configuration
     * @param LoggerInterface $logger writes command feedback
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly FileLocatorInterface $fileLocator,
        private readonly LoggerInterface $logger,
    ) {
        return parent::__construct();
    }

    /**
     * Configures the dependency workflow options.
     */
    protected function configure(): void
    {
        $this->setHelp('This command runs composer-dependency-analyser and Jack to report missing, unused, misplaced, and outdated Composer dependencies.');

        $this->addJsonOption()
            ->addOption(
                name: 'max-outdated',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Maximum number of outdated packages allowed by jack breakpoint. Use -1 to keep the report but ignore Jack failures.',
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
            )
            ->addOption(
                name: 'dump-usage',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Dump usages for the given package pattern and show all matched usages.',
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

        $this->processQueue->add($this->getRaiseToInstalledCommand($input));
        $this->processQueue->add($this->getOpenVersionsCommand($input));

        if ($input->getOption('upgrade')) {
            $this->processQueue->add($this->getComposerUpdateCommand());
            $this->processQueue->add($this->getComposerNormalizeCommand());
        }

        if (! $jsonOutput) {
            $this->logger->info('Running dependency analysis...', [
                'input' => $input,
            ]);
        }

        $this->processQueue->add($this->getComposerDependencyAnalyserCommand($input));
        $this->processQueue->add(
            $this->getJackBreakpointCommand($input, $maximumOutdated),
            $this->shouldIgnoreOutdatedFailures($maximumOutdated),
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

        return $processBuilder->build('vendor/bin/composer-dependency-analyser');
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

        if (! $this->shouldIgnoreOutdatedFailures($maximumOutdated)) {
            $command .= ' --limit ' . $maximumOutdated;
        }

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
     * Determines whether Jack outdated failures SHOULD be ignored for the given threshold.
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

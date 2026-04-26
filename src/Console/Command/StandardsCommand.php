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
use FastForward\DevTools\Console\Input\HasCacheOption;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes the full suite of Fast Forward code standard checks.
 * This class MUST NOT be modified through inheritance and SHALL streamline code validation workflows.
 */
#[AsCommand(
    name: 'dev-tools:standards',
    description: 'Runs Fast Forward code standards checks.',
    aliases: ['standards'],
)]
final class StandardsCommand extends Command
{
    use HasCacheOption;
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * @param ProcessBuilderInterface $processBuilder
     * @param ProcessQueueInterface $processQueue
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures constraints and arguments for the collective standard runner.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setHelp(
            'This command runs all Fast Forward code standards checks, including code refactoring, PHPDoc'
            . ' validation, code style checks, documentation generation, and tests execution.'
        );
        $this
            ->addJsonOption()
            ->addCacheOption('Whether to enable cache writes in nested cache-aware standards commands.')
            ->addCacheDirOption(
                description: 'Base cache directory used for nested cache-aware standards commands.',
                default: ManagedWorkspace::getCacheDirectory('standards'),
            )
            ->addOption(
                name: 'progress',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to enable progress output from nested standards commands.'
            )
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Automatically fix code standards issues.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = $this->isJsonOutput($input);
        $prettyJsonOutput = $this->isPrettyJsonOutput($input);
        $progress = ! $jsonOutput && (bool) $input->getOption('progress');
        $cacheArgument = $this->resolveCacheArgument($input);
        $cacheDirEnabled = '--no-cache' !== $cacheArgument;

        $commandOutput = $jsonOutput ? new BufferedOutput() : $output;
        $commands = [];
        $fix = (bool) $input->getOption('fix');

        $this->logger->info('Running code standards checks...', [
            'input' => $input,
        ]);

        foreach (['refactor', 'php-cs-fixer', 'code-style', 'reports'] as $command) {
            $commands[] = $command;
            $processBuilder = $this->processBuilder;

            if (! $jsonOutput) {
                $processBuilder = $processBuilder->withArgument('--ansi');
            }

            if ($progress) {
                $processBuilder = $processBuilder->withArgument('--progress');
            }

            if ('reports' !== $command && $fix) {
                $processBuilder = $processBuilder->withArgument('--fix');
            }

            if (\in_array($command, ['php-cs-fixer', 'reports'], true) && null !== $cacheArgument) {
                $processBuilder = $processBuilder->withArgument($cacheArgument);
            }

            if (
                $cacheDirEnabled
                && \in_array($command, ['php-cs-fixer', 'reports'], true)
                && null !== $cacheDir = $this->resolveCacheDirArgument($input, $command)
            ) {
                $processBuilder = $processBuilder->withArgument('--cache-dir', $cacheDir);
            }

            if ($jsonOutput) {
                $processBuilder = $processBuilder->withArgument('--json');
            }

            if ($prettyJsonOutput) {
                $processBuilder = $processBuilder->withArgument('--pretty-json');
            }

            $this->processQueue->add(
                process: $processBuilder->build(DevToolsPathResolver::getBinaryCommand($command)),
                label: $this->getProcessLabel($command),
            );
        }

        $result = $this->processQueue->run($commandOutput);

        if (self::FAILURE === $result) {
            return $this->failure('Code standards checks failed.', $input, [
                'output' => $commandOutput,
                'commands' => $commands,
            ]);
        }

        return $this->success('Code standards checks completed successfully.', $input, [
            'output' => $commandOutput,
            'commands' => $commands,
        ]);
    }

    /**
     * Resolves a human-readable process label for a nested standards command.
     *
     * @param string $command the nested dev-tools command name
     *
     * @return string the process section label
     */
    private function getProcessLabel(string $command): string
    {
        return match ($command) {
            'refactor' => 'Refactoring Code with DevTools',
            'php-cs-fixer', 'docheader' => 'Checking PHPDoc with DevTools',
            'code-style' => 'Checking Code Style with DevTools',
            'reports' => 'Generating Reports with DevTools',
            default => 'Running DevTools Command',
        };
    }
}

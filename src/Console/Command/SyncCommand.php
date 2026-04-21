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
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Orchestrates dev-tools synchronization commands for the consumer repository.
 */
#[AsCommand(
    name: 'dev-tools:sync',
    description: 'Installs and synchronizes dev-tools scripts, GitHub Actions workflows, CODEOWNERS, .editorconfig, and .gitattributes in the root project.',
    help: 'This command runs the dedicated synchronization commands for composer.json, resources, CODEOWNERS, funding metadata, wiki, git metadata, packaged skills, packaged agents, license, and Git hooks.'
)]
final class SyncCommand extends BaseCommand
{
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
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addJsonOption()
            ->addOption(
                name: 'overwrite',
                shortcut: 'o',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite existing target files.',
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview managed-file drift without writing changes.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Exit non-zero when managed-file drift is detected.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before applying managed-file replacements.',
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
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;
        $overwrite = (bool) $input->getOption('overwrite');
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');
        $modeArguments = [
            $dryRun ? '--dry-run' : null,
            $check ? '--check' : null,
            $interactive ? '--interactive' : null,
        ];
        $allowDetached = ! $dryRun && ! $check && ! $interactive;

        $this->logger->info('Starting dev-tools synchronization...', [
            'input' => $input,
        ]);

        $this->queueDevToolsCommand(['update-composer-json', ...$modeArguments], false, $jsonOutput, $prettyJsonOutput);
        $this->queueDevToolsCommand(['funding', ...$modeArguments], false, $jsonOutput, $prettyJsonOutput);
        $this->queueDevToolsCommand(
            [
                'copy-resource',
                '--source=resources/github-actions',
                '--target=.github/workflows',
                $overwrite ? '--overwrite' : null,
                ...$modeArguments,
            ],
            $allowDetached,
            $jsonOutput,
            $prettyJsonOutput,
        );
        $this->queueDevToolsCommand(
            [
                'copy-resource',
                '--source=.editorconfig',
                '--target=.editorconfig',
                $overwrite ? '--overwrite' : null,
                ...$modeArguments,
            ],
            $allowDetached,
            $jsonOutput,
            $prettyJsonOutput,
        );
        $this->queueDevToolsCommand(
            [
                'copy-resource',
                '--source=resources/dependabot.yml',
                '--target=.github/dependabot.yml',
                $overwrite ? '--overwrite' : null,
                ...$modeArguments,
            ],
            $allowDetached,
            $jsonOutput,
            $prettyJsonOutput,
        );
        $this->queueDevToolsCommand(
            ['codeowners', $overwrite ? '--overwrite' : null, ...$modeArguments],
            $allowDetached,
            $jsonOutput,
            $prettyJsonOutput,
        );

        if ($dryRun || $check || $interactive) {
            $this->logger->warning(
                'Skipping wiki, skills, and agents during preview/check modes because they do not yet expose non-destructive verification.',
                [
                    'input' => $input,
                ],
            );
        } else {
            $this->queueDevToolsCommand(['wiki', '--init'], true, $jsonOutput, $prettyJsonOutput);
            $this->queueDevToolsCommand(['skills'], true, $jsonOutput, $prettyJsonOutput);
            $this->queueDevToolsCommand(['agents'], true, $jsonOutput, $prettyJsonOutput);
        }

        $this->queueDevToolsCommand(['gitignore', ...$modeArguments], $allowDetached, $jsonOutput, $prettyJsonOutput);
        $this->queueDevToolsCommand(
            ['gitattributes', ...$modeArguments],
            $allowDetached,
            $jsonOutput,
            $prettyJsonOutput
        );
        $this->queueDevToolsCommand(['license', ...$modeArguments], $allowDetached, $jsonOutput, $prettyJsonOutput);
        $this->queueDevToolsCommand(['git-hooks', ...$modeArguments], $allowDetached, $jsonOutput, $prettyJsonOutput);

        $result = $this->processQueue->run($processOutput);

        if (self::SUCCESS === $result) {
            return $this->success('Dev-tools synchronization completed successfully.', $input, [
                'output' => $processOutput,
                'skipped_destructive_syncs' => $dryRun || $check || $interactive,
            ]);
        }

        return $this->failure('Dev-tools synchronization failed.', $input, [
            'output' => $processOutput,
            'skipped_destructive_syncs' => $dryRun || $check || $interactive,
        ]);
    }

    /**
     * @param list<string|null> $arguments
     * @param bool $detached
     * @param bool $jsonOutput
     * @param bool $prettyJsonOutput
     */
    private function queueDevToolsCommand(
        array $arguments,
        bool $detached = false,
        bool $jsonOutput = false,
        bool $prettyJsonOutput = false,
    ): void {
        $processBuilder = $this->processBuilder;
        $arguments = array_filter($arguments, static fn(?string $arg): bool => null !== $arg);

        if ($jsonOutput && ! \in_array('--json', $arguments, true)) {
            $arguments[] = '--json';
        }

        if ($prettyJsonOutput && ! \in_array('--pretty-json', $arguments, true)) {
            $arguments[] = '--pretty-json';
        }

        foreach ($arguments as $argument) {
            $processBuilder = $processBuilder->withArgument($argument);
        }

        $this->processQueue->add($processBuilder->build($this->devToolsBinary()), detached: $detached);
    }

    /**
     * @return string
     */
    private function devToolsBinary(): string
    {
        return Path::makeAbsolute('bin/dev-tools', \dirname(__DIR__, 3));
    }
}

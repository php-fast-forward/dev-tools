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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Sync\PackagedDirectorySynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronizes packaged Fast Forward project agents into the consumer repository.
 */
#[AsCommand(
    name: 'agents',
    description: 'Synchronizes Fast Forward project agents into .agents/agents directory.',
    help: 'This command ensures the consumer repository contains linked Fast Forward project agents by creating symlinks to the packaged prompts and removing broken links.'
)]
final class AgentsCommand extends BaseCommand
{
    private const string DIRECTORY_LABEL = '.agents/agents';

    /**
     * @param PackagedDirectorySynchronizer $synchronizer
     * @param FilesystemInterface $filesystem
     * @param CommandResponderFactoryInterface $commandResponderFactory
     */
    public function __construct(
        private readonly PackagedDirectorySynchronizer $synchronizer,
        private readonly FilesystemInterface $filesystem,
        private readonly CommandResponderFactoryInterface $commandResponderFactory,
    ) {
        parent::__construct();
    }

    /**
     * Configures output-format options for the synchronization command.
     */
    protected function configure(): void
    {
        $this->addOption(
            name: 'format',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Output format for the command result. Supported values: text, json.',
            default: OutputFormat::defaultValue(),
            suggestedValues: OutputFormat::supportedValues(),
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $responder = $this->commandResponderFactory->from($input, $output);
        $textOutput = OutputFormat::TEXT === $responder->format();

        $packageAgentsPath = $this->filesystem->getAbsolutePath(self::DIRECTORY_LABEL, \dirname(__DIR__, 3));
        $agentsDir = $this->filesystem->getAbsolutePath(self::DIRECTORY_LABEL);

        if ($textOutput) {
            $output->writeln('<info>Starting agents synchronization...</info>');
        }

        if (! $this->filesystem->exists($packageAgentsPath)) {
            return $responder->failure(
                \sprintf('No packaged .agents/agents found at: %s', $packageAgentsPath),
                [
                    'command' => 'agents',
                    'packaged_agents_path' => $packageAgentsPath,
                    'agents_dir' => $agentsDir,
                    'directory_created' => false,
                ],
            );
        }

        $directoryCreated = false;

        if (! $this->filesystem->exists($agentsDir)) {
            $this->filesystem->mkdir($agentsDir);
            $directoryCreated = true;

            if ($textOutput) {
                $output->writeln('<info>Created .agents/agents directory.</info>');
            }
        }

        $this->synchronizer->setLogger($this->getIO());

        $result = $this->synchronizer->synchronize($agentsDir, $packageAgentsPath, self::DIRECTORY_LABEL);

        if ($result->failed()) {
            return $responder->failure(
                'Agents synchronization failed.',
                [
                    'command' => 'agents',
                    'packaged_agents_path' => $packageAgentsPath,
                    'agents_dir' => $agentsDir,
                    'directory_created' => $directoryCreated,
                ],
            );
        }

        return $responder->success(
            'Agents synchronization completed successfully.',
            [
                'command' => 'agents',
                'packaged_agents_path' => $packageAgentsPath,
                'agents_dir' => $agentsDir,
                'directory_created' => $directoryCreated,
            ],
        );
    }
}

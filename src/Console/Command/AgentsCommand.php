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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Sync\PackagedDirectorySynchronizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
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
    use HasJsonOption;

    private const string DIRECTORY_LABEL = '.agents/agents';

    /**
     * @param PackagedDirectorySynchronizer $synchronizer
     * @param FilesystemInterface $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PackagedDirectorySynchronizer $synchronizer,
        private readonly FilesystemInterface $filesystem,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures JSON output options for the synchronization command.
     */
    protected function configure(): void
    {
        $this->addJsonOption();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageAgentsPath = $this->filesystem->getAbsolutePath(self::DIRECTORY_LABEL, \dirname(__DIR__, 3));
        $agentsDir = $this->filesystem->getAbsolutePath(self::DIRECTORY_LABEL);
        $this->logger->info('Starting agents synchronization...');

        if (! $this->filesystem->exists($packageAgentsPath)) {
            $this->logger->error(
                'No packaged .agents/agents found at: {packaged_agents_path}',
                [
                    'command' => 'agents',
                    'packaged_agents_path' => $packageAgentsPath,
                    'agents_dir' => $agentsDir,
                    'directory_created' => false,
                ],
            );

            return self::FAILURE;
        }

        $directoryCreated = false;

        if (! $this->filesystem->exists($agentsDir)) {
            $this->filesystem->mkdir($agentsDir);
            $directoryCreated = true;
            $this->logger->info('Created .agents/agents directory.');
        }

        $this->synchronizer->setLogger($this->getIO());

        $result = $this->synchronizer->synchronize($agentsDir, $packageAgentsPath, self::DIRECTORY_LABEL);

        if ($result->failed()) {
            $this->logger->error(
                'Agents synchronization failed.',
                [
                    'command' => 'agents',
                    'packaged_agents_path' => $packageAgentsPath,
                    'agents_dir' => $agentsDir,
                    'directory_created' => $directoryCreated,
                ],
            );

            return self::FAILURE;
        }

        $this->logger->info(
            'Agents synchronization completed successfully.',
            [
                'command' => 'agents',
                'packaged_agents_path' => $packageAgentsPath,
                'agents_dir' => $agentsDir,
                'directory_created' => $directoryCreated,
            ],
        );

        return self::SUCCESS;
    }
}

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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Sync\PackagedDirectorySynchronizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronizes packaged Fast Forward project agents into the consumer repository.
 */
#[AsCommand(name: 'agents', description: 'Synchronizes Fast Forward project agents into .agents/agents directory.')]
final class AgentsCommand extends Command
{    use HasJsonOption;
    use LogsCommandResults;

    private const string AGENTS_DIRECTORY = '.agents/agents';

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
        $this->setHelp(
            'This command ensures the consumer repository contains linked Fast Forward project agents by creating'
            . ' symlinks to the packaged prompts and removing broken links.'
        );
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
        $packageAgentsPath = DevToolsPathResolver::getPackagePath(self::AGENTS_DIRECTORY);
        $agentsDir = $this->filesystem->getAbsolutePath(self::AGENTS_DIRECTORY);
        $this->logger->info('Starting agents synchronization...');

        if (! $this->filesystem->exists($packageAgentsPath)) {
            return $this->failure(
                'No packaged .agents/agents found at: {packaged_agents_path}',
                $input,
                [
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
            $this->logger->info('Created .agents/agents directory.');
        }

        $result = $this->synchronizer->synchronize($agentsDir, $packageAgentsPath, self::AGENTS_DIRECTORY);

        if ($result->failed()) {
            return $this->failure(
                'Agents synchronization failed.',
                $input,
                [
                    'packaged_agents_path' => $packageAgentsPath,
                    'agents_dir' => $agentsDir,
                    'directory_created' => $directoryCreated,
                ],
            );
        }

        return $this->success(
            'Agents synchronization completed successfully.',
            $input,
            [
                'packaged_agents_path' => $packageAgentsPath,
                'agents_dir' => $agentsDir,
                'directory_created' => $directoryCreated,
            ],
        );
    }
}

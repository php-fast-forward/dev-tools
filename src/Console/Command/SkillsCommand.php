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
 * Synchronizes packaged Fast Forward skills into the consumer repository.
 *
 * This command SHALL ensure that the consumer repository contains the expected
 * `.agents/skills` directory structure backed by the packaged skill set. The
 * command MUST verify that the packaged skills directory exists before any
 * synchronization is attempted. If the target skills directory does not exist,
 * it SHALL be created before the synchronization process begins.
 *
 * The synchronization workflow is delegated to {@see PackagedDirectorySynchronizer}. This
 * command MUST act as an orchestration layer only: it prepares the source and
 * target paths, triggers synchronization, and translates the resulting status
 * into Symfony Console output and process exit codes.
 */
#[AsCommand(name: 'skills', description: 'Synchronizes Fast Forward skills into .agents/skills directory.')]
final class SkillsCommand extends Command implements LoggerAwareCommandInterface
{
    use HasJsonOption;
    use LogsCommandResults;

    private const string SKILLS_DIRECTORY = '.agents/skills';

    /**
     * Initializes the command with an optional skills synchronizer instance.
     *
     * @param PackagedDirectorySynchronizer $synchronizer the synchronizer responsible
     *                                                    for applying the skills
     *                                                    synchronization process
     * @param FilesystemInterface $filesystem filesystem used to resolve
     *                                        and manage the skills
     *                                        directory structure
     * @param LoggerInterface $logger logger used for command feedback
     */
    public function __construct(
        private readonly PackagedDirectorySynchronizer $synchronizer,
        private readonly FilesystemInterface $filesystem,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct('skills');
    }

    /**
     * Configures the supported JSON output option.
     */
    protected function configure(): void
    {
        $this->setHelp(
            'This command ensures the consumer repository contains linked Fast Forward skills by creating'
            . ' symlinks to the packaged skills and removing broken links.'
        );
        $this->addJsonOption();
    }

    /**
     * Executes the skills synchronization workflow.
     *
     * This method SHALL:
     * - announce the start of synchronization;
     * - resolve the packaged skills path and consumer target directory;
     * - fail when the packaged skills directory does not exist;
     * - create the target directory when it is missing;
     * - delegate synchronization to {@see PackagedDirectorySynchronizer};
     * - return a success or failure exit code based on the synchronization result.
     *
     * The command MUST return {@see self::FAILURE} when packaged skills are not
     * available or when the synchronizer reports a failure. It MUST return
     * {@see self::SUCCESS} only when synchronization completes successfully.
     *
     * @param InputInterface $input the console input instance provided by Symfony
     * @param OutputInterface $output the console output instance used to report progress
     *
     * @return int The process exit status. This MUST be {@see self::SUCCESS} on
     *             success and {@see self::FAILURE} on failure.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageSkillsPath = DevToolsPathResolver::getPackagePath(self::SKILLS_DIRECTORY);
        $skillsDir = $this->filesystem->getAbsolutePath(self::SKILLS_DIRECTORY);
        $this->logger->info('Starting skills synchronization...');

        if (! $this->filesystem->exists($packageSkillsPath)) {
            return $this->failure(
                'No packaged skills found at: {packaged_skills_path}',
                $input,
                [
                    'packaged_skills_path' => $packageSkillsPath,
                    'skills_dir' => $skillsDir,
                    'directory_created' => false,
                ],
            );
        }

        $directoryCreated = false;

        if (! $this->filesystem->exists($skillsDir)) {
            $this->filesystem->mkdir($skillsDir);
            $directoryCreated = true;
            $this->logger->info('Created .agents/skills directory.');
        }

        $result = $this->synchronizer->synchronize($skillsDir, $packageSkillsPath, self::SKILLS_DIRECTORY);

        if ($result->failed()) {
            return $this->failure(
                'Skills synchronization failed.',
                $input,
                [
                    'packaged_skills_path' => $packageSkillsPath,
                    'skills_dir' => $skillsDir,
                    'directory_created' => $directoryCreated,
                ],
            );
        }

        return $this->success(
            'Skills synchronization completed successfully.',
            $input,
            [
                'packaged_skills_path' => $packageSkillsPath,
                'skills_dir' => $skillsDir,
                'directory_created' => $directoryCreated,
            ],
        );
    }
}

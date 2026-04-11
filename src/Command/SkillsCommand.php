<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Command;

use FastForward\DevTools\Agent\Skills\SkillsSynchronizer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Synchronizes packaged Fast Forward skills into the consumer repository.
 *
 * This command SHALL ensure that the consumer repository contains the expected
 * `.agents/skills` directory structure backed by the packaged skill set. The
 * command MUST verify that the packaged skills directory exists before any
 * synchronization is attempted. If the target skills directory does not exist,
 * it SHALL be created before the synchronization process begins.
 *
 * The synchronization workflow is delegated to {@see SkillsSynchronizer}. This
 * command MUST act as an orchestration layer only: it prepares the source and
 * target paths, triggers synchronization, and translates the resulting status
 * into Symfony Console output and process exit codes.
 */
final class SkillsCommand extends AbstractCommand
{
    /**
     * Initializes the command with an optional skills synchronizer instance.
     *
     * If no synchronizer is provided, the command SHALL instantiate the default
     * {@see SkillsSynchronizer} implementation. Consumers MAY inject a custom
     * synchronizer for testing or alternative synchronization behavior, provided
     * it preserves the expected contract.
     *
     * @param SkillsSynchronizer|null $synchronizer the synchronizer responsible
     *                                              for applying the skills
     *                                              synchronization process
     * @param Filesystem|null $filesystem filesystem used to resolve
     *                                    and manage the skills
     *                                    directory structure
     */
    public function __construct(
        private readonly SkillsSynchronizer $synchronizer = new SkillsSynchronizer(),
        ?Filesystem $filesystem = null
    ) {
        parent::__construct($filesystem);
    }

    /**
     * Configures the command name, description, and help text.
     *
     * The command metadata MUST clearly describe that the operation synchronizes
     * Fast Forward skills into the `.agents/skills` directory and that it manages
     * link-based synchronization for packaged skills.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('skills')
            ->setDescription('Synchronizes Fast Forward skills into .agents/skills directory.')
            ->setHelp(
                'This command ensures the consumer repository contains linked Fast Forward skills '
                . 'by creating symlinks to the packaged skills and removing broken links.'
            );
    }

    /**
     * Executes the skills synchronization workflow.
     *
     * This method SHALL:
     * - announce the start of synchronization;
     * - resolve the packaged skills path and consumer target directory;
     * - fail when the packaged skills directory does not exist;
     * - create the target directory when it is missing;
     * - delegate synchronization to {@see SkillsSynchronizer};
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
        $output->writeln('<info>Starting skills synchronization...</info>');

        $packageSkillsPath = $this->getDevToolsFile('.agents/skills');
        $skillsDir = $this->getConfigFile('.agents/skills', true);

        if (! $this->filesystem->exists($packageSkillsPath)) {
            $output->writeln('<comment>No packaged skills found at: ' . $packageSkillsPath . '</comment>');

            return self::FAILURE;
        }

        if (! $this->filesystem->exists($skillsDir)) {
            $this->filesystem->mkdir($skillsDir);
            $output->writeln('<info>Created .agents/skills directory.</info>');
        }

        $this->synchronizer->setLogger($this->getIO());

        $result = $this->synchronizer->synchronize($skillsDir, $packageSkillsPath);

        if ($result->failed()) {
            $output->writeln('<error>Skills synchronization failed.</error>');

            return self::FAILURE;
        }

        $output->writeln('<info>Skills synchronization completed successfully.</info>');

        return self::SUCCESS;
    }
}

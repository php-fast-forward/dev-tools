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
use FastForward\DevTools\Agent\Skills\SynchronizeResult;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronizes Fast Forward skills into the consumer repository by managing `.agents/skills` links.
 */
final class SkillsCommand extends AbstractCommand
{
    private readonly SkillsSynchronizer $synchronizer;

    /**
     * @param SkillsSynchronizer|null $synchronizer
     */
    public function __construct(?SkillsSynchronizer $synchronizer = null)
    {
        $this->synchronizer = $synchronizer ?? new SkillsSynchronizer();

        parent::__construct();
    }

    /**
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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting skills synchronization...</info>');

        $packageSkillsPath = $this->getDevToolsFile('.agents/skills');
        $skillsDir = $this->getConfigFile('.agents/skills', true);

        // Normal consumer repository flow
        if (! $this->filesystem->exists($packageSkillsPath)) {
            $output->writeln('<comment>No packaged skills found at: ' . $packageSkillsPath . '</comment>');

            return self::FAILURE;
        }

        if (! $this->filesystem->exists($skillsDir)) {
            $this->filesystem->mkdir($skillsDir);
            $output->writeln('<info>Created .agents/skills directory.</info>');
        }

        /** @var SynchronizeResult $result */
        $result = $this->synchronizer->synchronize($skillsDir, $packageSkillsPath, $this->getIO());

        if ($result->failed()) {
            $output->writeln('<error>Skills synchronization failed.</error>');

            return self::FAILURE;
        }

        $output->writeln('<info>Skills synchronization completed successfully.</info>');

        return self::SUCCESS;
    }
}

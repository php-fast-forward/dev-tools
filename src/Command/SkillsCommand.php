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

use FastForward\DevTools\Command\Skills\SkillsSynchronizer;
use FastForward\DevTools\Command\Skills\SynchronizeResult;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Synchronizes Fast Forward skills into the consumer repository by managing `.agents/skills` links.
 */
final class SkillsCommand extends AbstractCommand
{
    private readonly SkillsSynchronizer $synchronizer;

    public function __construct(?SkillsSynchronizer $synchronizer = null)
    {
        $this->synchronizer = $synchronizer ?? new SkillsSynchronizer();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('dev-tools:skills')
            ->setDescription('Synchronizes Fast Forward skills into .agents/skills directory.')
            ->setHelp(
                'This command ensures the consumer repository contains linked Fast Forward skills '
                . 'by creating symlinks to the packaged skills and removing broken links.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting skills synchronization...</info>');

        $rootPath = $this->getCurrentWorkingDirectory();
        $skillsDir = Path::makeAbsolute('.agents/skills', $rootPath);

        // Use __DIR__ to get the package path
        $packagePath = Path::makeAbsolute('..', __DIR__);
        while (! file_exists($packagePath . '/composer.json')) {
            $parent = \dirname($packagePath);
            if ($parent === $packagePath) {
                break;
            }
            $packagePath = $parent;
        }
        $packageSkillsPath = Path::makeAbsolute('.agents/skills', $packagePath);

        // If package path equals root path, we're in the dev-tools repo itself
        // and skills are already present as regular files (tracked in git)
        if ($packagePath === $rootPath && $this->filesystem->exists($skillsDir)) {
            $output->writeln('<info>Skills already available in development repository (tracked in git).</info>');

            return self::SUCCESS;
        }

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
        $result = $this->synchronizer->synchronize(
            $rootPath,
            $skillsDir,
            $packageSkillsPath,
            static function (string $message) use ($output): void {
                $output->writeln($message);
            },
        );

        if ($result->failed()) {
            $output->writeln('<error>Skills synchronization failed.</error>');

            return self::FAILURE;
        }

        $output->writeln('<info>Skills synchronization completed successfully.</info>');

        return self::SUCCESS;
    }
}

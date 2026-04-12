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

use FastForward\DevTools\Changelog\UnreleasedEntryChecker;
use FastForward\DevTools\Changelog\UnreleasedEntryCheckerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Verifies that the changelog contains pending unreleased notes.
 */
final class ChangelogCheckCommand extends AbstractCommand
{
    /**
     * @param Filesystem|null $filesystem
     * @param UnreleasedEntryCheckerInterface|null $unreleasedEntryChecker
     */
    public function __construct(
        ?Filesystem $filesystem = null,
        private readonly ?UnreleasedEntryCheckerInterface $unreleasedEntryChecker = null,
    ) {
        parent::__construct($filesystem);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('changelog:check')
            ->setDescription('Checks whether CHANGELOG.md contains meaningful unreleased entries.')
            ->setHelp(
                'This command validates the current Unreleased section and may compare it against a base git reference to enforce pull request changelog updates.'
            )
            ->addOption(
                name: 'against',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Optional git reference used as the baseline CHANGELOG.md.',
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
        $hasPendingChanges = ($this->unreleasedEntryChecker ?? new UnreleasedEntryChecker())
            ->hasPendingChanges($this->getCurrentWorkingDirectory(), $input->getOption('against'));

        if ($hasPendingChanges) {
            $output->writeln('<info>CHANGELOG.md contains unreleased changes ready for review.</info>');

            return self::SUCCESS;
        }

        $output->writeln('<error>CHANGELOG.md must add a meaningful entry to the Unreleased section.</error>');

        return self::FAILURE;
    }
}

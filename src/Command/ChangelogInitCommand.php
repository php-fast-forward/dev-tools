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

use FastForward\DevTools\Changelog\Bootstrapper;
use FastForward\DevTools\Changelog\BootstrapperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Bootstraps keep-a-changelog assets for the current repository.
 */
final class ChangelogInitCommand extends AbstractCommand
{
    /**
     * @param Filesystem|null $filesystem
     * @param BootstrapperInterface|null $bootstrapper
     */
    public function __construct(
        ?Filesystem $filesystem = null,
        private readonly ?BootstrapperInterface $bootstrapper = null,
    ) {
        parent::__construct($filesystem);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('changelog:init')
            ->setDescription('Bootstraps keep-a-changelog configuration and CHANGELOG.md.')
            ->setHelp(
                'This command creates .keep-a-changelog.ini, generates CHANGELOG.md from git release history when missing, and restores an Unreleased section when necessary.'
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
        $result = ($this->bootstrapper ?? new Bootstrapper($this->filesystem))
            ->bootstrap($this->getCurrentWorkingDirectory());

        if ($result->configCreated) {
            $output->writeln('<info>Created .keep-a-changelog.ini.</info>');
        }

        if ($result->changelogCreated) {
            $output->writeln('<info>Generated CHANGELOG.md from repository history.</info>');
        }

        if ($result->unreleasedCreated) {
            $output->writeln('<info>Restored an Unreleased section in CHANGELOG.md.</info>');
        }

        if (! $result->configCreated && ! $result->changelogCreated && ! $result->unreleasedCreated) {
            $output->writeln('<info>Changelog automation assets are already up to date.</info>');
        }

        return self::SUCCESS;
    }
}

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
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Infers the next semantic version from changelog content.
 */
#[AsCommand(
    name: 'changelog:next-version',
    description: 'Infers the next semantic version from the Unreleased changelog section.',
    help: 'This command inspects Unreleased changelog categories and prints the next semantic version inferred from the current changelog state.'
)]
final class ChangelogNextVersionCommand extends BaseCommand
{
    /**
     * @param FilesystemInterface $filesystem
     * @param ChangelogManagerInterface $changelogManager
     * @param CommandResponderFactoryInterface $commandResponderFactory
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly ChangelogManagerInterface $changelogManager,
        private readonly CommandResponderFactoryInterface $commandResponderFactory,
    ) {
        parent::__construct();
    }

    /**
     * Configures version inference options.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'file',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to the changelog file.',
                default: 'CHANGELOG.md',
            )
            ->addOption(
                name: 'current-version',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Explicit current version to use as the bump base.',
            )
            ->addOption(
                name: 'output-format',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format for the command result. Supported values: text, json.',
                default: OutputFormat::defaultValue(),
                suggestedValues: OutputFormat::supportedValues(),
            );
    }

    /**
     * Prints the inferred next semantic version.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $responder = $this->commandResponderFactory->from($input, $output);
        $path = $this->filesystem->getAbsolutePath($input->getOption('file'));
        $currentVersion = $input->getOption('current-version');
        $nextVersion = $this->changelogManager->inferNextVersion($path, $currentVersion);

        return $responder->success(
            $nextVersion,
            [
                'command' => 'changelog:next-version',
                'file' => (string) $input->getOption('file'),
                'current_version' => $currentVersion,
                'next_version' => $nextVersion,
            ],
        );
    }
}

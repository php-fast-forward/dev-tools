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
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints the rendered notes body for a released changelog version.
 */
#[AsCommand(
    name: 'changelog:show',
    description: 'Prints the notes body for a released changelog version.',
    help: 'This command renders the body of one released changelog section so it can be reused for GitHub release notes.'
)]
final class ChangelogShowCommand extends BaseCommand
{
    use HasJsonOption;

    /**
     * @param FilesystemInterface $filesystem
     * @param ChangelogManagerInterface $changelogManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly ChangelogManagerInterface $changelogManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures the show command arguments and options.
     */
    protected function configure(): void
    {
        $this->addJsonOption()
            ->addArgument(
                name: 'version',
                mode: InputArgument::REQUIRED,
                description: 'The released version to render.',
            )
            ->addOption(
                name: 'file',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to the changelog file.',
                default: 'CHANGELOG.md',
            );
    }

    /**
     * Prints the rendered release notes body.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = (string) $input->getArgument('version');
        $file = (string) $input->getOption('file');
        $releaseNotes = $this->changelogManager->renderReleaseNotes(
            $this->filesystem->getAbsolutePath($file),
            $version,
        );

        $this->logger->info(
            $releaseNotes,
            [
                'command' => 'changelog:show',
                'file' => $file,
                'version' => $version,
                'release_notes' => $releaseNotes,
            ],
        );

        return self::SUCCESS;
    }
}

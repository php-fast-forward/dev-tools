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
use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
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
 * Inserts a changelog entry into the managed changelog file.
 */
#[AsCommand(
    name: 'changelog:entry',
    description: 'Adds a changelog entry to Unreleased or a specific version section.',
    help: 'This command appends one categorized changelog entry to the selected changelog file so it can be reused by local authoring flows and skills.'
)]
final class ChangelogEntryCommand extends BaseCommand
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
     * Configures the entry authoring arguments and options.
     */
    protected function configure(): void
    {
        $this->addJsonOption()
            ->addArgument(
                name: 'message',
                mode: InputArgument::REQUIRED,
                description: 'The changelog entry text to append.',
            )
            ->addOption(
                name: 'type',
                shortcut: 't',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The changelog category (added, changed, deprecated, removed, fixed, security).',
                default: 'added',
                suggestedValues: array_map(
                    static fn(ChangelogEntryType $type): string => strtolower($type->value),
                    ChangelogEntryType::ordered()
                ),
            )
            ->addOption(
                name: 'release',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The target release section. Defaults to Unreleased.',
                default: ChangelogDocument::UNRELEASED_VERSION,
            )
            ->addOption(
                name: 'date',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Optional release date for published sections in YYYY-MM-DD format.',
            )
            ->addOption(
                name: 'file',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to the changelog file.',
                default: 'CHANGELOG.md',
            );
    }

    /**
     * Appends a changelog entry to the requested section.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $this->filesystem->getAbsolutePath((string) $input->getOption('file'));
        $type = ChangelogEntryType::fromInput((string) $input->getOption('type'));
        $version = (string) $input->getOption('release');
        $date = $input->getOption('date');
        $message = (string) $input->getArgument('message');

        $this->changelogManager->addEntry($file, $type, $message, $version, \is_string($date) ? $date : null);

        $this->logger->info(
            'Added {type} changelog entry to [{release}] in {absolute_file}.',
            [
                'command' => 'changelog:entry',
                'file' => (string) $input->getOption('file'),
                'absolute_file' => $file,
                'type' => strtolower($type->value),
                'release' => $version,
                'date' => \is_string($date) ? $date : null,
                'message' => $message,
            ],
        );

        return self::SUCCESS;
    }
}

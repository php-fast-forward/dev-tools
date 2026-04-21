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
use FastForward\DevTools\Changelog\Checker\UnreleasedEntryCheckerInterface;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies that the changelog contains pending unreleased notes.
 */
#[AsCommand(
    name: 'changelog:check',
    description: 'Checks whether a changelog file contains meaningful unreleased entries.',
    help: 'This command validates the current Unreleased section and may compare it against a base git reference to enforce pull request changelog updates.'
)]
final class ChangelogCheckCommand extends BaseCommand
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * @param FilesystemInterface $filesystem
     * @param UnreleasedEntryCheckerInterface $unreleasedEntryChecker
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly UnreleasedEntryCheckerInterface $unreleasedEntryChecker,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures changelog verification options.
     */
    protected function configure(): void
    {
        $this->addJsonOption()
            ->addOption(
                name: 'against',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Optional git reference used as the baseline changelog file.',
            )
            ->addOption(
                name: 'file',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to the changelog file.',
                default: 'CHANGELOG.md',
            );
    }

    /**
     * Executes the changelog verification.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->filesystem->getAbsolutePath($input->getOption('file'));
        $against = $input->getOption('against');

        $hasPendingChanges = $this->unreleasedEntryChecker
            ->hasPendingChanges($path, $against);

        if ($hasPendingChanges) {
            return $this->success(
                'The changelog contains unreleased changes ready for review.',
                $input,
                [
                    'has_pending_changes' => true,
                ],
            );
        }

        return $this->failure(
            'The changelog must add a meaningful entry to the Unreleased section.',
            $input,
            [
                'has_pending_changes' => false,
            ],
            (string) $input->getOption('file'),
        );
    }
}

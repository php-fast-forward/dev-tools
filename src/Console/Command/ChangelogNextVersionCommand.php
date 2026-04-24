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
use Throwable;
use Composer\Command\BaseCommand;
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Infers the next semantic version from changelog content.
 */
#[AsCommand(
    name: 'changelog:next-version',
    description: 'Infers the next semantic version from the Unreleased changelog section.'
)]
final class ChangelogNextVersionCommand extends BaseCommand implements LoggerAwareCommandInterface
{
    use HasJsonOption;
    use LogsCommandResults;

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
     * Configures version inference options.
     */
    protected function configure(): void
    {
        $this->setHelp('This command inspects Unreleased changelog categories and prints the next semantic version inferred from the current changelog state.');

        $this->addJsonOption()
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
        try {
            $path = $this->filesystem->getAbsolutePath($input->getOption('file'));
            $currentVersion = $input->getOption('current-version');
            $nextVersion = $this->changelogManager->inferNextVersion($path, $currentVersion);

            // This command is consumed via shell capture in changelog.yml, so
            // plain-text mode MUST keep emitting the raw version string.
            if (! $this->isJsonOutput($input)) {
                $output->writeln($nextVersion);

                return self::SUCCESS;
            }

            return $this->success(
                $nextVersion,
                $input,
                [
                    'current_version' => $currentVersion,
                    'next_version' => $nextVersion,
                ],
            );
        } catch (Throwable $throwable) {
            return $this->failure(
                'Unable to infer the next changelog version.',
                $input,
                [
                    'exception_message' => $throwable->getMessage(),
                ],
                (string) $input->getOption('file'),
            );
        }
    }
}

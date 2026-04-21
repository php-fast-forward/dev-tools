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
    description: 'Infers the next semantic version from the Unreleased changelog section.',
    help: 'This command inspects Unreleased changelog categories and prints the next semantic version inferred from the current changelog state.'
)]
final class ChangelogNextVersionCommand extends BaseCommand
{
    use EmitsGithubActionErrors;
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
     * Configures version inference options.
     */
    protected function configure(): void
    {
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

            $this->logger->info(
                $nextVersion,
                [
                    'input' => $input,
                    'current_version' => $currentVersion,
                    'next_version' => $nextVersion,
                ],
            );

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->logger->error(
                'Unable to infer the next changelog version.',
                [
                    'input' => $input,
                    'exception_message' => $throwable->getMessage(),
                ],
            );
            $this->emitGithubActionError(
                'Unable to infer the next changelog version.',
                (string) $input->getOption('file'),
            );

            return self::FAILURE;
        }
    }
}

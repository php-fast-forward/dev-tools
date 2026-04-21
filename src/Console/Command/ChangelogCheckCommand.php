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
use FastForward\DevTools\Console\Output\CommandResult;
use FastForward\DevTools\Console\Output\CommandResultRendererInterface;
use FastForward\DevTools\Console\Output\OutputFormatResolverInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use InvalidArgumentException;
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
    /**
     * @param FilesystemInterface $filesystem
     * @param UnreleasedEntryCheckerInterface $unreleasedEntryChecker
     * @param OutputFormatResolverInterface $outputFormatResolver
     * @param CommandResultRendererInterface $commandResultRenderer
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly UnreleasedEntryCheckerInterface $unreleasedEntryChecker,
        private readonly OutputFormatResolverInterface $outputFormatResolver,
        private readonly CommandResultRendererInterface $commandResultRenderer,
    ) {
        parent::__construct();
    }

    /**
     * Configures changelog verification options.
     */
    protected function configure(): void
    {
        $this
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
            )
            ->addOption(
                name: 'format',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format for the command result. Supported values: text, json.',
                default: 'text',
                suggestedValues: ['text', 'json'],
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
        try {
            $format = $this->outputFormatResolver->resolve($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $output->writeln('<error>' . $invalidArgumentException->getMessage() . '</error>');

            return self::FAILURE;
        }

        $path = $this->filesystem->getAbsolutePath($input->getOption('file'));
        $against = $input->getOption('against');

        $hasPendingChanges = $this->unreleasedEntryChecker
            ->hasPendingChanges($path, $against);

        $file = (string) $input->getOption('file');

        if ($hasPendingChanges) {
            $this->commandResultRenderer->render(
                $output,
                CommandResult::success(
                    \sprintf('%s contains unreleased changes ready for review.', $file),
                    [
                        'command' => 'changelog:check',
                        'file' => $file,
                        'against' => $against,
                        'has_pending_changes' => true,
                    ],
                ),
                $format,
            );

            return self::SUCCESS;
        }

        $this->commandResultRenderer->render(
            $output,
            CommandResult::failure(
                \sprintf('%s must add a meaningful entry to the Unreleased section.', $file),
                [
                    'command' => 'changelog:check',
                    'file' => $file,
                    'against' => $against,
                    'has_pending_changes' => false,
                ],
            ),
            $format,
        );

        return self::FAILURE;
    }
}

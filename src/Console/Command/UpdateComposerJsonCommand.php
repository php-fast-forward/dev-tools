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
use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\Json\JsonManipulator;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

use function Safe\getcwd;

/**
 * Updates composer.json with the Fast Forward dev-tools integration metadata.
 */
#[AsCommand(
    name: 'update-composer-json',
    description: 'Updates composer.json with Fast Forward dev-tools scripts and metadata.'
)]
final class UpdateComposerJsonCommand extends BaseCommand implements LoggerAwareCommandInterface
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new UpdateComposerJsonCommand instance.
     *
     * @param ComposerJsonInterface $composer the composer.json metadata accessor
     * @param FilesystemInterface $filesystem the filesystem used to read and write composer.json
     * @param FileLocatorInterface $fileLocator the locator used to resolve packaged configuration files
     * @param FileDiffer $fileDiffer
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly ComposerJsonInterface $composer,
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
        private readonly FileDiffer $fileDiffer,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures the composer file option.
     */
    protected function configure(): void
    {
        $this->setHelp('This command adds or updates composer.json scripts and GrumPHP extra configuration required by dev-tools.');
        $this->addJsonOption()
            ->addOption(
                name: 'file',
                shortcut: 'f',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the composer.json file to update.',
                default: Factory::getComposerFile(),
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Preview composer.json synchronization without writing the file.',
            )
            ->addOption(
                name: 'check',
                mode: InputOption::VALUE_NONE,
                description: 'Report composer.json drift and exit non-zero when updates are required.',
            )
            ->addOption(
                name: 'interactive',
                mode: InputOption::VALUE_NONE,
                description: 'Prompt before updating composer.json.',
            );
    }

    /**
     * Updates composer.json when the target file exists.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string) $input->getOption('file');
        $dryRun = (bool) $input->getOption('dry-run');
        $check = (bool) $input->getOption('check');
        $interactive = (bool) $input->getOption('interactive');

        if (! $this->filesystem->exists($file)) {
            return $this->success(
                'Composer file {file} does not exist.',
                $input,
                [
                    'file' => $file,
                ],
                LogLevel::NOTICE,
            );
        }

        $currentContents = $this->filesystem->readFile($file);
        $manipulator = new JsonManipulator($currentContents);
        $grumphpConfig = DevToolsPathResolver::getPackagePath('grumphp.yml');

        foreach ($this->scripts() as $name => $command) {
            $manipulator->addSubNode('scripts', $name, $command);
        }

        if ('' === $this->composer->getReadme() && $this->filesystem->exists('README.md', \dirname($file))) {
            $manipulator->addProperty('readme', 'README.md');
        }

        $manipulator->addSubNode('extra', 'grumphp', [
            'config-default-path' => Path::makeRelative($grumphpConfig, getcwd()),
        ], true);

        $updatedContents = $manipulator->getContents();
        $comparison = $this->fileDiffer->diffContents(
            'generated dev-tools composer.json configuration',
            $file,
            $updatedContents,
            $currentContents,
            \sprintf('Updating managed file %s from generated dev-tools composer.json configuration.', $file),
        );

        $this->notice($comparison->getSummary(), $input, [
            'file' => $file,
        ]);

        if ($comparison->isChanged()) {
            $consoleDiff = $this->fileDiffer->formatForConsole($comparison->getDiff(), $output->isDecorated());

            if (null !== $consoleDiff) {
                $this->notice($consoleDiff, $input, [
                    'file' => $file,
                    'diff' => $comparison->getDiff(),
                ]);
            }
        }

        if ($comparison->isUnchanged()) {
            return self::SUCCESS;
        }

        if ($check) {
            return self::FAILURE;
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        if ($interactive && $input->isInteractive() && ! $this->shouldUpdateComposerJson($file)) {
            return $this->success('Skipped updating {file}.', $input, [
                'file' => $file,
            ], LogLevel::NOTICE,);
        }

        $this->filesystem->dumpFile($file, $updatedContents);

        return $this->success('Updated composer.json dev-tools configuration.', $input, [
            'file' => $file,
        ]);
    }

    /**
     * Prompts whether composer.json should be updated.
     *
     * @param string $file the composer.json path that would be updated
     *
     * @return bool true when the update SHOULD proceed
     */
    private function shouldUpdateComposerJson(string $file): bool
    {
        return $this->getIO()
            ->askConfirmation(\sprintf('Update managed file %s? [y/N] ', $file), false);
    }

    /**
     * Returns the Composer scripts managed by this command.
     *
     * @return array<string, string> the script name to command map
     */
    private function scripts(): array
    {
        return [
            'dev-tools' => 'dev-tools',
            'dev-tools:fix' => '@dev-tools --fix',
        ];
    }
}

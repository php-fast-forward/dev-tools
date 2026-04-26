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
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\getcwd;

/**
 * Updates composer.json with the Fast Forward dev-tools integration metadata.
 */
#[AsCommand(
    name: 'dev-tools:sync:composer',
    description: 'Updates composer.json with Fast Forward dev-tools scripts and metadata.',
    aliases: ['composer.json', 'update-composer-json'],
)]
final class UpdateComposerJsonCommand extends Command
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new UpdateComposerJsonCommand instance.
     *
     * @param ComposerJsonInterface $composer the composer.json metadata accessor
     * @param FilesystemInterface $filesystem the filesystem used to read and write composer.json
     * @param FileLocatorInterface $fileLocator the locator used to resolve packaged configuration files
     * @param FileDiffer $fileDiffer the file differ used to summarize synchronization changes
     * @param LoggerInterface $logger the output-aware logger
     * @param SymfonyStyle $io the input/output service used to interact with the user
     */
    public function __construct(
        private readonly ComposerJsonInterface $composer,
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
        private readonly FileDiffer $fileDiffer,
        private readonly LoggerInterface $logger,
        private readonly SymfonyStyle $io,
    ) {
        parent::__construct();
    }

    /**
     * Configures the composer file option.
     */
    protected function configure(): void
    {
        $this->setHelp(
            'This command adds or updates composer.json scripts and GrumPHP extra configuration required by'
            . ' dev-tools.'
        );

        $this->addJsonOption()
            ->addOption(
                name: 'file',
                shortcut: 'f',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the composer.json file to update.',
                default: 'composer.json',
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
        $updatedContents = $this->updatedComposerJsonContents($currentContents, $file);
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
        $confirmationMessage = \sprintf(
            'composer.json file %s has changes. Do you want to update it with the new dev-tools configuration?',
            $file,
        );

        $confirmation = new ConfirmationQuestion($confirmationMessage, false);

        return $this->io->askQuestion($confirmation);
    }

    /**
     * Builds the managed composer.json payload.
     *
     * @param string $currentContents the current composer.json file contents
     * @param string $file the path being updated, used to resolve local README checks
     *
     * @return string the composer.json payload with managed sections applied
     */
    private function updatedComposerJsonContents(string $currentContents, string $file): string
    {
        $composerJsonData = json_decode($currentContents, true, 512, \JSON_THROW_ON_ERROR);

        if (! \is_array($composerJsonData)) {
            $composerJsonData = [];
        }

        $scripts = $composerJsonData['scripts'] ?? [];
        if (! \is_array($scripts)) {
            $scripts = [];
        }

        foreach ($this->getScripts() as $name => $command) {
            $scripts[$name] = $command;
        }

        $composerJsonData['scripts'] = $scripts;

        if ('' === $this->composer->getReadme() && $this->filesystem->exists(
            'README.md',
            \dirname($file)
        ) && ! isset($composerJsonData['readme'])) {
            $composerJsonData['readme'] = 'README.md';
        }

        $extra = $composerJsonData['extra'] ?? [];
        if (! \is_array($extra)) {
            $extra = [];
        }

        $grumphpConfig = DevToolsPathResolver::getPackagePath('grumphp.yml');
        $grumphpExtra = $extra['grumphp'] ?? [];
        if (! \is_array($grumphpExtra)) {
            $grumphpExtra = [];
        }

        $grumphpExtra['config-default-path'] = Path::makeRelative($grumphpConfig, getcwd());
        $extra['grumphp'] = $grumphpExtra;
        $composerJsonData['extra'] = $extra;

        return json_encode(
            $composerJsonData,
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
        ) . "\n";
    }

    /**
     * Returns the Composer scripts managed by this command.
     *
     * @return array<string, string> the script name to command map
     */
    private function getScripts(): array
    {
        return [
            'dev-tools' => 'dev-tools',
            'dev-tools:fix' => '@dev-tools --fix',
        ];
    }
}

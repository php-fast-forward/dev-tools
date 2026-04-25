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
use FastForward\DevTools\Console\Input\HasCacheOption;
use FastForward\DevTools\Console\Input\HasJsonOption;
use Twig\Environment;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;

/**
 * Generates the package API documentation through phpDocumentor.
 *
 * The command prepares a temporary phpDocumentor configuration from the
 * current package metadata, then delegates execution to the shared process
 * queue so logging and grouped output stay consistent with the rest of the
 * command surface.
 */
#[AsCommand(name: 'docs', description: 'Generates API documentation.')]
final class DocsCommand extends Command implements LoggerAwareCommandInterface
{
    use HasCacheOption;
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Creates a new DocsCommand instance.
     *
     * @param ProcessBuilderInterface $processBuilder the process builder for executing phpDocumentor
     * @param ProcessQueueInterface $processQueue the process queue for managing execution
     * @param Environment $renderer
     * @param FilesystemInterface $filesystem the filesystem for handling file operations
     * @param ComposerJsonInterface $composer the composer.json handler for accessing project metadata
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly Environment $renderer,
        private readonly FilesystemInterface $filesystem,
        private readonly ComposerJsonInterface $composer,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures the command options used to generate API documentation.
     */
    protected function configure(): void
    {
        $this->setHelp('This command generates API documentation using phpDocumentor.');
        $this
            ->addJsonOption()
            ->addCacheOption('Whether to enable phpDocumentor caching.')
            ->addCacheDirOption(
                description: 'Path to the cache directory for phpDocumentor.',
                default: ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPDOC),
            )
            ->addOption(
                name: 'progress',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to enable progress output from phpDocumentor.',
            )
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the output directory for the generated HTML documentation.',
                default: ManagedWorkspace::getOutputDirectory(),
            )
            ->addOption(
                name: 'source',
                shortcut: 's',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the source directory for the generated HTML documentation.',
                default: 'docs',
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the template directory for the generated HTML documentation.',
                default: 'vendor/fast-forward/phpdoc-bootstrap-template',
            );
    }

    /**
     * Generates the HTML API documentation for the configured source tree.
     *
     * @param InputInterface $input the input details for the command
     * @param OutputInterface $output the output mechanism for logging
     *
     * @return int the final execution status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = $this->isJsonOutput($input);
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;
        $progress = ! $jsonOutput && (bool) $input->getOption('progress');
        $cacheEnabled = $this->isCacheEnabled($input);

        $source = $this->filesystem->getAbsolutePath($input->getOption('source'));
        $target = $this->filesystem->getAbsolutePath($input->getOption('target'));
        $cacheDir = $this->filesystem->getAbsolutePath($input->getOption('cache-dir'));

        $this->logger->info('Generating API documentation...', [
            'input' => $input,
        ]);

        if (! $this->filesystem->exists($source)) {
            return $this->failure('Source directory not found: {source}', $input, [
                'source' => $source,
            ]);
        }

        $config = $this->createPhpDocumentorConfig(
            source: $source,
            target: $target,
            template: $input->getOption('template'),
            cacheDir: $cacheEnabled ? $cacheDir : sys_get_temp_dir(),
        );

        $processBuilder = $this->processBuilder
            ->withArgument('--config', $config)
            ->withArgument('--ansi')
            ->withArgument('--markers', 'TODO,FIXME,BUG,HACK');

        if ($cacheEnabled) {
            $processBuilder = $processBuilder->withArgument('--cache-folder', $cacheDir);
        }

        if (! $progress) {
            $processBuilder = $processBuilder->withArgument('--no-progress');
        }

        $phpdoc = $processBuilder->build('vendor/bin/phpdoc');

        $this->processQueue->add(process: $phpdoc, label: 'Generating API Docs with phpDocumentor');

        $result = $this->processQueue->run($processOutput);

        if (self::SUCCESS === $result) {
            return $this->success('API documentation generated successfully.', $input, [
                'output' => $processOutput,
            ]);
        }

        return $this->failure('API documentation generation failed.', $input, [
            'output' => $processOutput,
        ]);
    }

    /**
     * Creates a temporary phpDocumentor configuration for the current project.
     *
     * @param string $source the source directory for the generated documentation
     * @param string $target the output directory for the generated documentation
     * @param string $template the phpDocumentor template name or path
     * @param string $cacheDir the cache directory for phpDocumentor
     *
     * @return string the absolute path to the generated configuration
     */
    private function createPhpDocumentorConfig(
        string $source,
        string $target,
        string $template,
        string $cacheDir
    ): string {
        $workingDirectory = getcwd();
        $autoload = $this->composer->getAutoload('psr-4');
        $guidePath = $this->filesystem->makePathRelative($source);
        $defaultPackageName = array_key_first($autoload) ?: '';

        $content = $this->renderer->render('phpdocumentor.xml', [
            'title' => $this->composer->getName(),
            'template' => $template,
            'target' => $target,
            'cacheDir' => $cacheDir,
            'workingDirectory' => $workingDirectory,
            'paths' => $autoload,
            'guidePath' => $guidePath,
            'defaultPackageName' => rtrim($defaultPackageName, '\\'),
        ]);

        $this->filesystem->dumpFile(filename: 'phpdocumentor.xml', content: $content, path: $cacheDir);

        return $this->filesystem->getAbsolutePath('phpdocumentor.xml', $cacheDir);
    }
}

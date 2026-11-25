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
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Project\ProjectCapabilities;
use FastForward\DevTools\Project\ProjectCapabilitiesResolverInterface;
use Psr\Log\LogLevel;
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
#[AsCommand(
    name: 'reports:docs',
    description: 'Generates API documentation.',
    aliases: ['reports:phpdoc', 'phpDocumentor', 'docs'],
)]
final class DocsCommand extends Command
{
    use HasCacheOption;
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * @var string the default phpDocumentor template path relative to the consumer project
     */
    private const string DEFAULT_TEMPLATE = 'vendor/fast-forward/phpdoc-bootstrap-template';

    /**
     * Creates a new DocsCommand instance.
     *
     * @param ProcessBuilderInterface $processBuilder the process builder for executing phpDocumentor
     * @param ProcessQueueInterface $processQueue the process queue for managing execution
     * @param Environment $renderer renders phpDocumentor configuration templates
     * @param FilesystemInterface $filesystem the filesystem for handling file operations
     * @param ComposerJsonInterface $composer the composer.json handler for accessing project metadata
     * @param ProjectCapabilitiesResolverInterface $projectCapabilitiesResolver the project capability resolver
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly Environment $renderer,
        private readonly FilesystemInterface $filesystem,
        private readonly ComposerJsonInterface $composer,
        private readonly ProjectCapabilitiesResolverInterface $projectCapabilitiesResolver,
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
                default: ProjectCapabilitiesResolverInterface::DEFAULT_GUIDE_DIRECTORY,
            )
            ->addOption(
                name: 'template',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the template directory for the generated HTML documentation.',
                default: self::DEFAULT_TEMPLATE,
            );
    }

    /**
     * Generates API documentation for the configured project surface.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = $this->isJsonOutput($input);
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;
        $progress = ! $jsonOutput && (bool) $input->getOption('progress');
        $cacheEnabled = $this->isCacheEnabled($input);

        $sourceOption = (string) $input->getOption('source');
        $source = $this->filesystem->getAbsolutePath($sourceOption);
        $target = $this->filesystem->getAbsolutePath($input->getOption('target'));
        $cacheDir = $this->filesystem->getAbsolutePath($input->getOption('cache-dir'));
        $template = (string) $input->getOption('template');
        $projectCapabilities = $this->projectCapabilitiesResolver->resolve(guideDirectory: $sourceOption);

        if (self::DEFAULT_TEMPLATE === $template) {
            $template = DevToolsPathResolver::getPreferredVendorPath(self::DEFAULT_TEMPLATE);
        }

        $this->log('Generating API documentation...', $input);

        if (
            ! $projectCapabilities->hasGuideDirectory()
            && ! $this->isDefaultGuideSource($sourceOption)
        ) {
            return $this->failure('Source directory not found: {source}', $input, [
                'source' => $source,
            ]);
        }

        if (! $projectCapabilities->canGenerateDocs()) {
            return $this->success(
                'Skipping API documentation generation because no guide source or autoloaded PHP API directories were detected.',
                $input,
                [],
                LogLevel::WARNING,
            );
        }

        $config = $this->createPhpDocumentorConfig(
            source: $source,
            target: $target,
            template: $template,
            cacheDir: $cacheEnabled ? $cacheDir : sys_get_temp_dir(),
            projectCapabilities: $projectCapabilities,
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

        $phpdoc = $processBuilder->build([DevToolsPathResolver::getPreferredToolBinaryPath('phpdoc')]);

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
     * @param ProjectCapabilities $projectCapabilities the resolved project capability snapshot
     *
     * @return string the absolute path to the generated configuration
     */
    private function createPhpDocumentorConfig(
        string $source,
        string $target,
        string $template,
        string $cacheDir,
        ProjectCapabilities $projectCapabilities,
    ): string {
        $workingDirectory = getcwd();
        $guidePath = $projectCapabilities->hasGuideDirectory()
            ? $this->filesystem->makePathRelative($source)
            : null;

        $content = $this->renderer->render('phpdocumentor.xml', [
            'title' => $this->composer->getName(),
            'template' => $template,
            'target' => $target,
            'cacheDir' => $cacheDir,
            'workingDirectory' => $workingDirectory,
            'apiDirectories' => $projectCapabilities->getApiDirectories(),
            'guidePath' => $guidePath,
            'defaultPackageName' => $projectCapabilities->getDefaultPackageName(),
        ]);

        $this->filesystem->dumpFile(filename: 'phpdocumentor.xml', content: $content, path: $cacheDir);

        return $this->filesystem->getAbsolutePath('phpdocumentor.xml', $cacheDir);
    }

    /**
     * Detects whether a source option still points at the default guide directory.
     *
     * @param string $sourceOption the guide source option received from the CLI
     *
     * @return bool true when the provided path is equivalent to the default guide directory
     */
    private function isDefaultGuideSource(string $sourceOption): bool
    {
        return $this->normalizeProjectRelativePath($sourceOption) === $this->normalizeProjectRelativePath(
            ProjectCapabilitiesResolverInterface::DEFAULT_GUIDE_DIRECTORY
        );
    }

    /**
     * Normalizes a project-relative path for resilient default-option comparisons.
     *
     * @param string $path the project-relative path to normalize
     *
     * @return string the normalized project-relative path
     */
    private function normalizeProjectRelativePath(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);

        while (str_starts_with($normalizedPath, './')) {
            $normalizedPath = substr($normalizedPath, 2);
        }

        return rtrim($normalizedPath, '/');
    }
}

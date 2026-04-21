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

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Console\Input\HasJsonOption;
use Twig\Environment;
use Composer\Command\BaseCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;

/**
 * Handles the generation of API documentation for the project.
 * This class MUST NOT be extended and SHALL utilize phpDocumentor to accomplish its task.
 */
#[AsCommand(
    name: 'docs',
    description: 'Generates API documentation.',
    help: 'This command generates API documentation using phpDocumentor.',
)]
final class DocsCommand extends BaseCommand
{
    use HasJsonOption;

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
     * Configures the command instance.
     *
     * The method MUST set up the name and description. It MAY accept an optional `--target` option
     * pointing to an alternative configuration target path.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addJsonOption()
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the output directory for the generated HTML documentation.',
                default: '.dev-tools',
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
            )
            ->addOption(
                name: 'cache-dir',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the cache directory for phpDocumentor.',
                default: 'tmp/cache/phpdoc',
            );
    }

    /**
     * Executes the generation of the documentation files.
     *
     * This method MUST compile arguments based on PSR-4 namespaces to feed into phpDocumentor.
     * It SHOULD provide feedback on generation progress, and SHALL return `self::SUCCESS` on success.
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

        $source = $this->filesystem->getAbsolutePath($input->getOption('source'));
        $target = $this->filesystem->getAbsolutePath($input->getOption('target'));
        $cacheDir = $this->filesystem->getAbsolutePath($input->getOption('cache-dir'));

        $this->logger->info('Generating API documentation...', [
            'input' => $input,
        ]);

        if (! $this->filesystem->exists($source)) {
            $this->logger->error('Source directory not found: {source}', [
                'input' => $input,
            ],);

            return self::FAILURE;
        }

        $config = $this->createPhpDocumentorConfig(
            source: $source,
            target: $target,
            template: $input->getOption('template'),
            cacheDir: $cacheDir
        );

        $phpdoc = $this->processBuilder
            ->withArgument('--config', $config)
            ->withArgument('--ansi')
            ->withArgument('--no-progress')
            ->withArgument('--markers', 'TODO,FIXME,BUG,HACK')
            ->build('vendor/bin/phpdoc');

        $this->processQueue->add($phpdoc);

        $result = $this->processQueue->run($processOutput);

        $context = [
            'input' => $input,
            'output' => $processOutput,
        ];

        if (self::SUCCESS === $result) {
            $this->logger->info('API documentation generated successfully.', $context);

            return self::SUCCESS;
        }

        $this->logger->error('API documentation generation failed.', $context);

        return self::FAILURE;
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

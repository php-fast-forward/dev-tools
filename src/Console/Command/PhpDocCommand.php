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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Composer\Command\BaseCommand;
use Throwable;
use FastForward\DevTools\Rector\AddMissingMethodPhpDocRector;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides operations to inspect, lint, and repair PHPDoc comments across the project.
 * The class MUST NOT be extended and SHALL coordinate tools like PHP-CS-Fixer and Rector.
 */
#[AsCommand(
    name: 'phpdoc',
    description: 'Checks and fixes PHPDocs.',
    help: 'This command checks and fixes PHPDocs in your PHP files.',
)]
final class PhpDocCommand extends BaseCommand implements LoggerAwareCommandInterface
{
    use HasCacheOption;
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * @var string determines the template filename for docheaders
     */
    public const string FILENAME = '.docheader';

    /**
     * @var string stores the underlying configuration file for PHP-CS-Fixer
     */
    public const string CONFIG = '.php-cs-fixer.dist.php';

    /**
     * @var string defines the cache file name for PHP-CS-Fixer results
     */
    public const string CACHE_FILE = '.php-cs-fixer.cache';

    /**
     * Creates a new PhpDocCommand instance.
     *
     * @param ProcessBuilderInterface
     * @param FileLocatorInterface $fileLocator the locator for template resources
     * @param FilesystemInterface $filesystem the filesystem component
     * @param ProcessBuilderInterface $processBuilder
     * @param ProcessQueueInterface $processQueue
     * @param ComposerJsonInterface $composer
     * @param Environment $renderer
     * @param ClockInterface $clock
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly ComposerJsonInterface $composer,
        private readonly FileLocatorInterface $fileLocator,
        private readonly FilesystemInterface $filesystem,
        private readonly Environment $renderer,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures the PHPDoc command.
     *
     * This method MUST securely configure the expected inputs, such as the `--fix` option.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addJsonOption()
            ->addCacheOption('Whether to enable PHP-CS-Fixer caching.')
            ->addCacheDirOption(
                description: 'Path to the cache directory for PHP-CS-Fixer.',
                default: ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHP_CS_FIXER),
            )
            ->addArgument(
                name: 'path',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the file or directory to check.',
                default: ['.'],
            )
            ->addOption(
                name: 'progress',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to enable progress output from PHPDoc tooling.',
            )
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to fix the PHPDoc issues automatically.',
            );
    }

    /**
     * Executes the PHPDoc checks and rectifications.
     *
     * The method MUST ensure the `.docheader` template is present. It SHALL then invoke
     * PHP-CS-Fixer and Rector. If both succeed, it MUST return `self::SUCCESS`.
     *
     * @param InputInterface $input the command input parameters
     * @param OutputInterface $output the system output handler
     *
     * @return int the success or failure state
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = $this->isJsonOutput($input);
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;
        $fix = (bool) $input->getOption('fix');
        $progress = ! $jsonOutput && (bool) $input->getOption('progress');
        $cacheEnabled = $this->isCacheEnabled($input);

        $this->logger->info('Checking and fixing PHPDocs...', [
            'input' => $input,
        ]);

        $this->ensureDocHeaderExists();

        $processBuilder = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--diff')
            ->withArgument('--config', $this->fileLocator->locate(self::CONFIG));

        if ($cacheEnabled) {
            $processBuilder = $processBuilder->withArgument('--using-cache=yes')
                ->withArgument(
                    '--cache-file',
                    $this->filesystem->getAbsolutePath(self::CACHE_FILE, $input->getOption('cache-dir'))
                );
        } else {
            $processBuilder = $processBuilder->withArgument('--using-cache=no');
        }

        if (! $progress) {
            $processBuilder = $processBuilder->withArgument('--show-progress=none');
        }

        if ($jsonOutput) {
            $processBuilder = $processBuilder
                ->withArgument('--format=json');
        }

        if (! $fix) {
            $processBuilder = $processBuilder->withArgument('--dry-run');
        }

        $phpCsFixer = $processBuilder->build('vendor/bin/php-cs-fixer fix');

        $processBuilder = $this->processBuilder
            ->withArgument('--config', $this->fileLocator->locate(RefactorCommand::CONFIG))
            ->withArgument('--autoload-file', 'vendor/autoload.php')
            ->withArgument('--only', AddMissingMethodPhpDocRector::class);

        if (! $progress) {
            $processBuilder = $processBuilder->withArgument('--no-progress-bar');
        }

        if ($jsonOutput) {
            $processBuilder = $processBuilder
                ->withArgument('--output-format', 'json');
        }

        if (! $fix) {
            $processBuilder = $processBuilder->withArgument('--dry-run');
        }

        $rector = $processBuilder->build('vendor/bin/rector process');

        $this->processQueue->add($phpCsFixer);
        $this->processQueue->add($rector);

        $result = $this->processQueue->run($processOutput);

        if (self::SUCCESS === $result) {
            return $this->success('PHPDoc checks completed successfully.', $input, [
                'output' => $processOutput,
            ]);
        }

        return $this->failure('PHPDoc checks failed.', $input, [
            'output' => $processOutput,
        ]);
    }

    /**
     * Creates the missing document header configuration file if needed.
     *
     * The method MUST query the local filesystem. If the file is missing, it SHOULD copy
     * the tool template into the root folder.
     *
     * @return void
     */
    private function ensureDocHeaderExists(): void
    {
        $support = $this->composer->getSupport();

        $links = array_unique(array_filter([
            'homepage' => $this->composer->getHomepage(),
            'source' => $support->getSource(),
            'issues' => $support->getIssues(),
            'docs' => $support->getDocs() ?? $support->getWiki(),
            'rfc2119' => 'https://datatracker.ietf.org/doc/html/rfc2119',
        ]));

        $docHeader = $this->renderer->render('docblock/.docheader', [
            'package' => $this->composer->getName(),
            'description' => rtrim($this->composer->getDescription(), '.'),
            'year' => $this->clock->now()
                ->format('Y'),
            'copyright_holder' => (string) $this->composer->getAuthors(true),
            'license' => $this->composer->getLicense(),
            'links' => $links,
        ]);

        try {
            $this->filesystem->dumpFile(self::FILENAME, $docHeader);
        } catch (Throwable) {
            $this->logger->warning(
                'Skipping .docheader creation because the destination file could not be written.'
            );

            return;
        }

        $this->logger->info('Created .docheader from repository template.');
    }
}

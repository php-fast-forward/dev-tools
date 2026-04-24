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

namespace FastForward\DevTools\ServiceProvider;

use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Changelog\Manager\ChangelogManager;
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Changelog\Parser\ChangelogParser;
use FastForward\DevTools\Changelog\Parser\ChangelogParserInterface;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\Composer\Json\ComposerJson;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Git\GitClient;
use FastForward\DevTools\Git\GitClientInterface;
use FastForward\DevTools\Changelog\Renderer\MarkdownRenderer;
use FastForward\DevTools\Changelog\Renderer\MarkdownRendererInterface;
use FastForward\DevTools\Changelog\Checker\UnreleasedEntryChecker;
use FastForward\DevTools\Changelog\Checker\UnreleasedEntryCheckerInterface;
use FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader;
use FastForward\DevTools\Console\Formatter\LogLevelOutputFormatter;
use FastForward\DevTools\Console\Logger\OutputFormatLogger;
use FastForward\DevTools\Console\Logger\Processor\CommandInputProcessor;
use FastForward\DevTools\Console\Logger\Processor\CommandOutputProcessor;
use FastForward\DevTools\Console\Logger\Processor\CompositeContextProcessor;
use FastForward\DevTools\Console\Logger\Processor\ContextProcessorInterface;
use FastForward\DevTools\Console\Output\GithubActionOutput;
use FastForward\DevTools\Console\Output\OutputCapabilityDetector;
use FastForward\DevTools\Console\Output\OutputCapabilityDetectorInterface;
use FastForward\DevTools\Environment\Environment;
use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Filesystem\FinderFactory;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\Filesystem;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\GitAttributes\CandidateProvider;
use FastForward\DevTools\GitAttributes\CandidateProviderInterface;
use FastForward\DevTools\GitAttributes\ExistenceChecker;
use FastForward\DevTools\GitAttributes\ExistenceCheckerInterface;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilter;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilterInterface;
use FastForward\DevTools\GitAttributes\Merger as GitAttributesMerger;
use FastForward\DevTools\GitAttributes\MergerInterface as GitAttributesMergerInterface;
use FastForward\DevTools\GitAttributes\Reader as GitAttributesReader;
use FastForward\DevTools\GitAttributes\ReaderInterface as GitAttributesReaderInterface;
use FastForward\DevTools\GitAttributes\Writer as GitAttributesWriter;
use FastForward\DevTools\GitAttributes\WriterInterface as GitAttributesWriterInterface;
use FastForward\DevTools\GitIgnore\Merger;
use FastForward\DevTools\GitIgnore\MergerInterface;
use FastForward\DevTools\GitIgnore\Reader;
use FastForward\DevTools\GitIgnore\ReaderInterface;
use FastForward\DevTools\GitIgnore\Writer;
use FastForward\DevTools\GitIgnore\WriterInterface;
use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\GeneratorInterface;
use FastForward\DevTools\License\Resolver;
use FastForward\DevTools\License\ResolverInterface;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
use FastForward\DevTools\Process\ColorPreservingProcessEnvironmentConfigurator;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessEnvironmentConfiguratorInterface;
use FastForward\DevTools\Process\ProcessQueue;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\Psr\Clock\SystemClock;
use FastForward\DevTools\Resource\DifferInterface;
use FastForward\DevTools\Resource\UnifiedDiffer;
use Interop\Container\ServiceProviderInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SebastianBergmann\Diff\Output\DiffOutputBuilderInterface;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

use function DI\create;
use function DI\get;

/**
 * DevToolsServiceProvider registers the services provided by this package.
 *
 * This class implements the ServiceProviderInterface from the PHP-Interop container package,
 * allowing it to be used with any compatible dependency injection container.
 */
final class DevToolsServiceProvider implements ServiceProviderInterface
{
    /**
     * @return array
     */
    public function getFactories(): array
    {
        return [
            // Process
            EnvironmentInterface::class => get(Environment::class),
            OutputCapabilityDetectorInterface::class => get(OutputCapabilityDetector::class),
            ProcessBuilderInterface::class => get(ProcessBuilder::class),
            ProcessEnvironmentConfiguratorInterface::class => get(ColorPreservingProcessEnvironmentConfigurator::class),
            ProcessQueueInterface::class => get(ProcessQueue::class),

            // Filesystem
            FinderFactoryInterface::class => get(FinderFactory::class),
            FilesystemInterface::class => get(Filesystem::class),

            // Composer
            ComposerJsonInterface::class => get(ComposerJson::class),

            // Changelog
            ChangelogManagerInterface::class => get(ChangelogManager::class),
            ChangelogParserInterface::class => get(ChangelogParser::class),
            MarkdownRendererInterface::class => get(MarkdownRenderer::class),
            UnreleasedEntryCheckerInterface::class => get(UnreleasedEntryChecker::class),

            // Git
            GitClientInterface::class => get(GitClient::class),

            // Symfony Components
            FileLocatorInterface::class => create(FileLocator::class)->constructor([
                WorkingProjectPathResolver::getProjectPath(),
                DevToolsPathResolver::getPackagePath(),
            ]),

            // PSR
            LoggerInterface::class => get(OutputFormatLogger::class),
            ClockInterface::class => get(SystemClock::class),

            // Console
            CommandLoaderInterface::class => get(DevToolsCommandLoader::class),
            CommandProvider::class => get(DevToolsCommandProvider::class),
            ConsoleOutputInterface::class => create(ConsoleOutput::class)
                ->method('setVerbosity', ConsoleOutputInterface::VERBOSITY_VERBOSE)
                ->method('setFormatter', get(LogLevelOutputFormatter::class)),
            GithubActionOutput::class => create(GithubActionOutput::class)->constructor(
                get(ConsoleOutputInterface::class),
                get(EnvironmentInterface::class)
            ),
            ContextProcessorInterface::class => create(CompositeContextProcessor::class)->constructor([
                get(CommandInputProcessor::class),
                get(CommandOutputProcessor::class),
            ]),

            // Coverage
            CoverageSummaryLoaderInterface::class => get(CoverageSummaryLoader::class),

            // Resource
            DiffOutputBuilderInterface::class => get(UnifiedDiffOutputBuilder::class),
            DifferInterface::class => get(UnifiedDiffer::class),

            // GitIgnore
            MergerInterface::class => get(Merger::class),
            ReaderInterface::class => get(Reader::class),
            WriterInterface::class => get(Writer::class),

            // GitAttributes
            CandidateProviderInterface::class => get(CandidateProvider::class),
            ExistenceCheckerInterface::class => get(ExistenceChecker::class),
            ExportIgnoreFilterInterface::class => get(ExportIgnoreFilter::class),
            GitAttributesMergerInterface::class => get(GitAttributesMerger::class),
            GitAttributesReaderInterface::class => get(GitAttributesReader::class),
            GitAttributesWriterInterface::class => get(GitAttributesWriter::class),

            // License
            GeneratorInterface::class => get(Generator::class),
            ResolverInterface::class => get(Resolver::class),

            // Twig
            LoaderInterface::class => create(FilesystemLoader::class)->constructor(
                DevToolsPathResolver::getResourcesPath()
            ),
        ];
    }

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return [];
    }
}

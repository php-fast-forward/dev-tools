<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\ServiceProvider;

use Interop\Container\ServiceProviderInterface;
use FastForward\DevTools\Psr\Clock\SystemClock;
use FastForward\DevTools\Composer\Capability\DevToolsCommandProvider;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader;
use FastForward\DevTools\GitIgnore\MergerInterface;
use FastForward\DevTools\GitIgnore\Merger;
use FastForward\DevTools\GitIgnore\ReaderInterface;
use FastForward\DevTools\GitIgnore\Reader;
use FastForward\DevTools\GitIgnore\WriterInterface;
use FastForward\DevTools\GitIgnore\Writer;
use FastForward\DevTools\GitAttributes\CandidateProviderInterface;
use FastForward\DevTools\GitAttributes\CandidateProvider;
use FastForward\DevTools\GitAttributes\ExistenceCheckerInterface;
use FastForward\DevTools\GitAttributes\ExistenceChecker;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilterInterface;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilter;
use FastForward\DevTools\License\GeneratorInterface;
use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\PlaceholderResolverInterface;
use FastForward\DevTools\License\PlaceholderResolver;
use FastForward\DevTools\License\ResolverInterface;
use FastForward\DevTools\License\Resolver;
use FastForward\DevTools\License\TemplateLoaderInterface;
use FastForward\DevTools\License\TemplateLoader;
use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader;
use FastForward\DevTools\GitAttributes\Merger as GitAttributesMerger;
use FastForward\DevTools\GitAttributes\MergerInterface as GitAttributesMergerInterface;
use FastForward\DevTools\GitAttributes\Reader as GitAttributesReader;
use FastForward\DevTools\GitAttributes\ReaderInterface as GitAttributesReaderInterface;
use FastForward\DevTools\GitAttributes\Writer as GitAttributesWriter;
use FastForward\DevTools\GitAttributes\WriterInterface as GitAttributesWriterInterface;
use FastForward\DevTools\License\Reader as LicenseReader;
use FastForward\DevTools\License\ReaderInterface as LicenseReaderInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Finder\Finder;

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
            // Symfony Components
            Finder::class => create(Finder::class),

            // PSR
            LoggerInterface::class => get(NullLogger::class),
            ClockInterface::class => get(SystemClock::class),

            // Console
            CommandLoaderInterface::class => get(DevToolsCommandLoader::class),
            CommandProvider::class => get(DevToolsCommandProvider::class),

            // Coverage
            CoverageSummaryLoaderInterface::class => get(CoverageSummaryLoader::class),

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
            PlaceholderResolverInterface::class => get(PlaceholderResolver::class),
            LicenseReaderInterface::class => get(LicenseReader::class),
            ResolverInterface::class => get(Resolver::class),
            TemplateLoaderInterface::class => get(TemplateLoader::class),
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

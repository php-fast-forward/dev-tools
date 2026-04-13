<?php

namespace FastForward\DevTools;

use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Finder\Finder;

use function DI\create;
use function DI\get;

return [
    // Symfony Components
    Finder::class => create(Finder::class),

    // PSR
    LoggerInterface::class => get(NullLogger::class),
    ClockInterface::class => get(Psr\Clock\SystemClock::class),

    // Console
    CommandLoaderInterface::class => get(DevToolsCommandLoader::class),
    CommandProvider::class => get(Composer\Capability\DevToolsCommandProvider::class),

    // Coverage
    PhpUnit\Coverage\CoverageSummaryLoaderInterface::class => get(PhpUnit\Coverage\CoverageSummaryLoader::class),

    // GitIgnore
    GitIgnore\MergerInterface::class => get(GitIgnore\Merger::class),
    GitIgnore\ReaderInterface::class => get(GitIgnore\Reader::class),
    GitIgnore\WriterInterface::class => get(GitIgnore\Writer::class),

    // GitAttributes
    GitAttributes\CandidateProviderInterface::class => get(GitAttributes\CandidateProvider::class),
    GitAttributes\ExistenceCheckerInterface::class => get(GitAttributes\ExistenceChecker::class),
    GitAttributes\ExportIgnoreFilterInterface::class => get(GitAttributes\ExportIgnoreFilter::class),
    GitAttributes\MergerInterface::class => get(GitAttributes\Merger::class),
    GitAttributes\ReaderInterface::class => get(GitAttributes\Reader::class),
    GitAttributes\WriterInterface::class => get(GitAttributes\Writer::class),

    // License
    License\GeneratorInterface::class => get(License\Generator::class),
    License\PlaceholderResolverInterface::class => get(License\PlaceholderResolver::class),
    License\ReaderInterface::class => get(License\Reader::class),
    License\ResolverInterface::class => get(License\Resolver::class),
    License\TemplateLoaderInterface::class => get(License\TemplateLoader::class),
];

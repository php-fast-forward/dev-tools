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

namespace FastForward\DevTools\Tests\Metrics;

use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Metrics\Report;
use FastForward\DevTools\Metrics\ReportLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

#[CoversClass(Report::class)]
#[CoversClass(ReportLoader::class)]
final class ReportLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    private ReportLoader $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->loader = new ReportLoader($this->filesystem->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function loadWillAggregateClassAndFunctionMetrics(): void
    {
        $this->filesystem->readFile('/app/tmp/cache/phpmetrics/metrics.json')
            ->willReturn((string) json_encode([
                'App\\Foo' => [
                    '_type' => \Hal\Metric\ClassMetric::class,
                    'ccn' => 3,
                    'mi' => 80,
                ],
                'App\\Bar' => [
                    '_type' => \Hal\Metric\ClassMetric::class,
                    'ccn' => 5,
                    'mi' => 70,
                ],
                'App\\helper' => [
                    '_type' => \Hal\Metric\FunctionMetric::class,
                ],
            ]));

        $report = $this->loader->load('/app/tmp/cache/phpmetrics/metrics.json');

        self::assertSame(4.0, $report->averageCyclomaticComplexityByClass);
        self::assertSame(75.0, $report->averageMaintainabilityIndexByClass);
        self::assertSame(2, $report->classesAnalyzed);
        self::assertSame(1, $report->functionsAnalyzed);
    }

    /**
     * @return void
     */
    #[Test]
    public function loadWillFailWhenJsonCannotBeDecoded(): void
    {
        $this->filesystem->readFile('/app/tmp/cache/phpmetrics/metrics.json')
            ->willReturn('{invalid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The PhpMetrics JSON report could not be decoded.');

        $this->loader->load('/app/tmp/cache/phpmetrics/metrics.json');
    }
}

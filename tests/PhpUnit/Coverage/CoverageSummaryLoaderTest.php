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

namespace FastForward\DevTools\Tests\PhpUnit\Coverage;

use FastForward\DevTools\PhpUnit\Coverage\CoverageSummary;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP as PhpReport;

use function Safe\file_put_contents;
use function Safe\tempnam;
use function Safe\unlink;

#[CoversClass(CoverageSummaryLoader::class)]
#[CoversClass(CoverageSummary::class)]
final class CoverageSummaryLoaderTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    private CoverageSummaryLoader $coverageSummaryLoader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->coverageSummaryLoader = new CoverageSummaryLoader();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (! file_exists($temporaryFile)) {
                continue;
            }

            unlink($temporaryFile);
        }

        parent::tearDown();
    }

    /**
     * @return void
     */
    #[Test]
    public function loadWillReturnLineCoverageSummaryFromPhpUnitCoverageReport(): void
    {
        $coverageReport = $this->createCoverageReport([
            7 => ['test'],
            8 => ['test'],
            11 => [],
        ]);

        $summary = $this->coverageSummaryLoader->load($coverageReport);

        self::assertSame(2, $summary->executedLines());
        self::assertSame(3, $summary->executableLines());
        self::assertEqualsWithDelta(66.67, $summary->percentage(), 0.01);
        self::assertSame('66.67%', $summary->percentageAsString());
    }

    /**
     * @return void
     */
    #[Test]
    public function loadWithMissingCoverageReportWillThrowRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHPUnit coverage report not found');

        $this->coverageSummaryLoader->load('/tmp/dev-tools-missing-coverage.php');
    }

    /**
     * @return void
     */
    #[Test]
    public function loadWithInvalidCoverageReportWillThrowRuntimeException(): void
    {
        $invalidReport = $this->createTemporaryFile("<?php\nreturn new stdClass();\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHPUnit coverage report is invalid');

        $this->coverageSummaryLoader->load($invalidReport);
    }

    /**
     * @param array<int, array<int, string>>|array<int, array{}> $lineCoverage
     *
     * @return string
     */
    private function createCoverageReport(array $lineCoverage): string
    {
        $sourceFile = $this->createTemporaryFile(<<<'PHP'
            <?php

            declare(strict_types=1);

            final class CoverageFixture
            {
                public function run(bool $flag): int
                {
                    if ($flag) {
                        return 1;
                    }

                    return 0;
                }
            }
            PHP);

        $coverage = new CodeCoverage(
            new class extends Driver {
                /**
                 * @return string
                 */
                public function name(): string
                {
                    return 'test-driver';
                }

                /**
                 * @return string
                 */
                public function version(): string
                {
                    return '0.0.0';
                }

                /**
                 * @return string
                 */
                public function nameAndVersion(): string
                {
                    return 'test-driver';
                }

                /**
                 * @return void
                 */
                public function start(): void {}

                /**
                 * @return RawCodeCoverageData
                 *
                 * @throws RuntimeException
                 */
                public function stop(): RawCodeCoverageData
                {
                    throw new RuntimeException('The synthetic driver MUST NOT be executed.');
                }
            },
            new Filter(),
        );

        $data = new ProcessedCodeCoverageData();
        $data->setLineCoverage([
            $sourceFile => $lineCoverage,
        ]);
        $coverage->setData($data);

        $coverageReport = $this->createTemporaryFile();
        (new PhpReport())->process($coverage, $coverageReport);

        return $coverageReport;
    }

    /**
     * @param string $contents
     *
     * @return string
     */
    private function createTemporaryFile(string $contents = ''): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'dev-tools-coverage-');
        $this->temporaryFiles[] = $temporaryFile;

        file_put_contents($temporaryFile, $contents);

        return $temporaryFile;
    }
}

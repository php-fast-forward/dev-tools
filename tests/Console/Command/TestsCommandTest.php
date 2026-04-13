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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Console\Command\TestsCommand;
use FastForward\DevTools\Composer\Json\ComposerJson;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummary;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
use Prophecy\Argument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(TestsCommand::class)]
#[UsesClass(CoverageSummary::class)]
final class TestsCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CoverageSummaryLoaderInterface>
     */
    private ObjectProphecy $coverageSummaryLoader;

    /**
     * @var ObjectProphecy<ComposerJson>
     */
    private ObjectProphecy $composerJson;

    /**
     * @return TestsCommand
     */
    protected function getCommandClass(): TestsCommand
    {
        return new TestsCommand(
            $this->coverageSummaryLoader->reveal(),
            $this->composerJson->reveal(),
            $this->filesystem->reveal()
        );
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'tests';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Runs PHPUnit tests.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command runs PHPUnit to execute your tests.';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->coverageSummaryLoader = $this->prophesize(CoverageSummaryLoaderInterface::class);
        $this->composerJson = $this->prophesize(ComposerJson::class);
        $this->composerJson->getAutoload()
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);

        parent::setUp();

        $this->withConfigFile(TestsCommand::CONFIG);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithLocalConfigWillRunPhpUnitProcessWithDevToolsConfigFile(): void
    {
        $this->withConfigFile(TestsCommand::CONFIG, true);

        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/phpunit')
                && str_contains($commandLine, '--configuration')
                && str_contains($commandLine, getcwd() . '/' . TestsCommand::CONFIG);
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithoutLocalConfigWillRunPhpUnitProcessWithDevToolsConfigFile(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, 'vendor/bin/phpunit')
                && str_contains($commandLine, '--configuration')
                && str_contains($commandLine, getcwd() . '/' . TestsCommand::CONFIG);
        });

        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageWillIncludeCoverageArguments(): void
    {
        $this->willRunProcessWithCallback(function (Process $process): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-text')
                && str_contains($commandLine, '--coverage-html=');
        });

        $this->input->getOption('coverage')
            ->willReturn('public/coverage');
        $this->invokeExecute();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithMinCoverageWillGenerateCoveragePhpAndValidateIt(): void
    {
        $coverageReportPath = getcwd() . '/tmp/cache/phpunit/coverage.php';

        $this->willRunProcessWithCallback(function (Process $process) use ($coverageReportPath): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-php=' . $coverageReportPath)
                && ! str_contains($commandLine, '--coverage-html=');
        });

        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(85, 100));

        $this->input->getOption('min-coverage')
            ->willReturn('80');

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageAndMinCoverageWillValidateGeneratedCoverageReport(): void
    {
        $coverageReportPath = getcwd() . '/public/coverage/coverage.php';

        $this->willRunProcessWithCallback(function (Process $process) use ($coverageReportPath): bool {
            $commandLine = $process->getCommandLine();

            return str_contains($commandLine, '--coverage-html=' . getcwd() . '/public/coverage')
                && str_contains($commandLine, '--coverage-php=' . $coverageReportPath);
        });

        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(90, 100));

        $this->input->getOption('coverage')
            ->willReturn('public/coverage');
        $this->input->getOption('min-coverage')
            ->willReturn('80');

        self::assertSame(TestsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithCoverageBelowMinimumWillReturnFailure(): void
    {
        $coverageReportPath = getcwd() . '/tmp/cache/phpunit/coverage.php';

        $this->willRunProcessWithCallback(static fn(Process $process): bool => str_contains(
            $process->getCommandLine(),
            '--coverage-php=' . getcwd() . '/tmp/cache/phpunit/coverage.php',
        ));

        $this->output->writeln(Argument::type('string'))
            ->will(static function (): void {});
        $this->output->writeln(Argument::containingString('Minimum line coverage'))
            ->shouldBeCalled();

        $this->coverageSummaryLoader->load($coverageReportPath)
            ->willReturn(new CoverageSummary(75, 100));

        $this->input->getOption('min-coverage')
            ->willReturn('80');

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithInvalidMinCoverageWillReturnFailure(): void
    {
        $this->output->writeln(Argument::type('string'))
            ->will(static function (): void {});
        $this->output->writeln(Argument::containingString('--min-coverage'))
            ->shouldBeCalled();

        $this->input->getOption('min-coverage')
            ->willReturn('abc');

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureIfProcessFails(): void
    {
        $this->willRunProcessWithCallback(static fn(): bool => true, false);

        self::assertSame(TestsCommand::FAILURE, $this->invokeExecute());
    }
}

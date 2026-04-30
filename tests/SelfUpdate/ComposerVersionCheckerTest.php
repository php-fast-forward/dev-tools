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

namespace FastForward\DevTools\Tests\SelfUpdate;

use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\SelfUpdate\ComposerVersionChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Process\Process;

#[CoversClass(ComposerVersionChecker::class)]
final class ComposerVersionCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ProcessBuilderInterface>
     */
    private ObjectProphecy $processBuilder;

    private ComposerVersionChecker $checker;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->checker = new ComposerVersionChecker($this->processBuilder->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveLatestStableVersionWillReturnFirstStableComposerVersion(): void
    {
        $process = new Process([
            'php',
            '-r',
            'echo json_encode(["versions" => ["1.x-dev", "v1.24.3", "v1.24.2"]]);',
        ]);

        $this->expectComposerShowLookup($process);

        self::assertSame('v1.24.3', $this->invokeResolveLatestStableVersion($this->checker));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveLatestStableVersionWillReturnNullWhenComposerShowFails(): void
    {
        $process = new Process(['php', '-r', 'fwrite(STDERR, "lookup failed"); exit(1);']);

        $this->expectComposerShowLookup($process);

        self::assertNull($this->invokeResolveLatestStableVersion($this->checker));
    }

    /**
     * @param Process $process
     *
     * @return void
     */
    private function expectComposerShowLookup(Process $process): void
    {
        $builder = $this->processBuilder->reveal();

        $this->processBuilder->withArgument('fast-forward/dev-tools')
            ->willReturn($builder)
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--available')
            ->willReturn($builder)
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--format=json')
            ->willReturn($builder)
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--no-interaction')
            ->willReturn($builder)
            ->shouldBeCalledOnce();
        $this->processBuilder->build('composer show')
            ->willReturn($process)
            ->shouldBeCalledOnce();
    }

    /**
     * @param ComposerVersionChecker $checker
     *
     * @return ?string
     */
    private function invokeResolveLatestStableVersion(ComposerVersionChecker $checker): ?string
    {
        return (new ReflectionMethod($checker, 'resolveLatestStableVersion'))->invoke($checker);
    }
}

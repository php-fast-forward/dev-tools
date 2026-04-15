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

namespace FastForward\DevTools\Tests\Process;

use FastForward\DevTools\Process\ProcessBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessBuilder::class)]
final class ProcessBuilderTest extends TestCase
{
    use ProphecyTrait;

    private ProcessBuilder $builder;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->builder = new ProcessBuilder();
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWithDefaultWillCreateEmptyArguments(): void
    {
        self::assertInstanceOf(ProcessBuilder::class, $this->builder);
    }

    /**
     * @return void
     */
    #[Test]
    public function buildWithSimpleCommandWillReturnProcessInstance(): void
    {
        $process = $this->builder->build('echo hello');

        self::assertInstanceOf(Process::class, $process);
        self::assertSame("'echo' 'hello'", $process->getCommandLine());
    }

    /**
     * @return void
     */
    #[Test]
    public function withArgumentWithoutValueWillAddArgumentToList(): void
    {
        $result = $this->builder->withArgument('--verbose');

        self::assertInstanceOf(ProcessBuilder::class, $result);
        self::assertSame(['--verbose'], $result->getArguments());
    }

    /**
     * @return void
     */
    #[Test]
    public function withArgumentWithValueWillFormatAsKeyValuePair(): void
    {
        $result = $this->builder->withArgument('--env', 'dev');

        self::assertInstanceOf(ProcessBuilder::class, $result);
        self::assertSame(['--env=dev'], $result->getArguments());
    }

    /**
     * @return void
     */
    #[Test]
    public function withArgumentChainingWillAccumulateAllArguments(): void
    {
        $builder = new ProcessBuilder();

        $result = $builder
            ->withArgument('--verbose')
            ->withArgument('--env', 'prod')
            ->withArgument('--timeout', '30');

        self::assertInstanceOf(ProcessBuilder::class, $result);
        self::assertSame(['--verbose', '--env=prod', '--timeout=30'], $result->getArguments());
    }

    /**
     * @return void
     */
    #[Test]
    public function buildWillReturnProcessInstanceWithArguments(): void
    {
        $builder = new ProcessBuilder();

        $process = $builder
            ->withArgument('--verbose')
            ->withArgument('--env', 'dev')
            ->build('php artisan serve');

        self::assertInstanceOf(Process::class, $process);
        self::assertSame("'php' 'artisan' 'serve' '--verbose' '--env=dev'", $process->getCommandLine());
    }
}

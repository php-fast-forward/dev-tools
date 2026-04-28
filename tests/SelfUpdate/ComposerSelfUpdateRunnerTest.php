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
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\SelfUpdate\ComposerSelfUpdateRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(ComposerSelfUpdateRunner::class)]
final class ComposerSelfUpdateRunnerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ProcessBuilderInterface>
     */
    private ObjectProphecy $processBuilder;

    /**
     * @var ObjectProphecy<ProcessQueueInterface>
     */
    private ObjectProphecy $processQueue;

    /**
     * @var ObjectProphecy<Process>
     */
    private ObjectProphecy $process;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    private ComposerSelfUpdateRunner $runner;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->processBuilder->withArgument('fast-forward/dev-tools')
            ->willReturn($this->processBuilder->reveal());
        $this->runner = new ComposerSelfUpdateRunner(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function updateWillRunProjectComposerUpdate(): void
    {
        $this->processBuilder->build('composer update')
            ->willReturn($this->process->reveal());
        $this->processQueue->add(
            $this->process->reveal(),
            false,
            false,
            'Updating project DevTools installation',
        )->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS);

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->runner->update(false, $this->output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function updateWillRunGlobalComposerUpdate(): void
    {
        $this->processBuilder->build('composer global update')
            ->willReturn($this->process->reveal());
        $this->processQueue->add(
            $this->process->reveal(),
            false,
            false,
            'Updating global DevTools installation',
        )->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS);

        self::assertSame(ProcessQueueInterface::SUCCESS, $this->runner->update(true, $this->output->reveal()));
    }
}

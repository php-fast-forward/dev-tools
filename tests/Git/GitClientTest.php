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

namespace FastForward\DevTools\Tests\Git;

use FastForward\DevTools\Git\GitClient;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\Process\Process;

#[CoversClass(GitClient::class)]
final class GitClientTest extends TestCase
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

    private GitClient $client;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->client = new GitClient($this->processBuilder->reveal(), $this->processQueue->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function getConfigWillBuildAndRunTheExpectedGitCommand(): void
    {
        $this->processBuilder->withArgument('config')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--get')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('remote.origin.url')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->build('git')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->process->setWorkingDirectory('/repo')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->process->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();
        $this->process->getOutput()
            ->willReturn(" git@github.com:php-fast-forward/dev-tools.git \n")
            ->shouldBeCalledOnce();

        self::assertSame(
            'git@github.com:php-fast-forward/dev-tools.git',
            $this->client->getConfig('remote.origin.url', '/repo'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function showWillRelativizeAbsolutePathsWithinTheRepository(): void
    {
        $this->processBuilder->withArgument('show')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('origin/main:docs/CHANGELOG.md')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->build('git')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->process->setWorkingDirectory('/repo')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->process->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();
        $this->process->getOutput()
            ->willReturn("baseline contents\n")
            ->shouldBeCalledOnce();

        self::assertSame(
            'baseline contents',
            $this->client->show('origin/main', '/repo/docs/CHANGELOG.md', '/repo'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function showWillKeepPathsThatDoNotBelongToTheRepositoryUnchanged(): void
    {
        $this->processBuilder->withArgument('show')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('origin/main:/external/CHANGELOG.md')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->build('git')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->process->setWorkingDirectory('/repo')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->process->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();
        $this->process->getOutput()
            ->willReturn("baseline contents\n")
            ->shouldBeCalledOnce();

        self::assertSame(
            'baseline contents',
            $this->client->show('origin/main', '/external/CHANGELOG.md', '/repo'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function getConfigWillThrowTheTrimmedErrorOutputWhenTheQueueFails(): void
    {
        $this->processBuilder->withArgument('config')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--get')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('remote.origin.url')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->build('git')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->process->setWorkingDirectory('/repo')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->process->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::FAILURE)
            ->shouldBeCalledOnce();
        $this->process->getErrorOutput()
            ->willReturn(" git failed \n")
            ->shouldBeCalledOnce();
        $this->process->getOutput()
            ->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('git failed');

        $this->client->getConfig('remote.origin.url', '/repo');
    }
}
